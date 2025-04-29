<?php

namespace Wideti\DomainBundle\Service\SmsBilling;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Wideti\DomainBundle\Helpers\DateTimeHelper;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Service\Mail\MailHeaderServiceAware;
use Wideti\DomainBundle\Service\Mailer\MailerServiceAware;
use Wideti\DomainBundle\Service\Mailer\Message\MailMessageBuilder;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\DomainBundle\Helpers\FileUpload;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;


class SmsBillingByMonthService extends ContainerAwareCommand
{
    use EntityManagerAware;
    use MailerServiceAware;
    use MailHeaderServiceAware;
    use TwigAware;

    /**
     * @var FileUpload
     */
    protected $fileUpload;

    /**
     * @var DocumentManager
     */
    protected $dm;

    private $month;

    private $helperQuestion;

    protected function configure()
    {
        $this->setName('sms:report_by_month')
            ->setDescription('SMS report');

        $this->setHelperQuestion(new QuestionHelper());
    }

    /**
     * Get all clients to close their SMS accounts
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<question> ################################################################ </question>");
        $output->writeln("<question> ###   Relatório de SMS de todos os clientes através do mês   ### </question>");
        $output->writeln("<question> ################################################################ </question>");

        $question = new Question('<info>Informe o mês do relatório: </info>', null);

        $this->setMonth($this->getHelperQuestion()->ask($input, $output, $question));

        $month = (int)$this->getMonth();

        $clients = $this->em->getRepository('DomainBundle:Client')
            ->findAll();

        $mailBody = [];

        $output->writeln("<info>Gerando relatório</info>");

        foreach ($clients as $client) {

            $dateTo = "2018-" . $month . "-" . $client->getClosingDate() . " 23:59:59";
            $time = strtotime($dateTo);
            $dateTo = date("Y-m-d 23:59:59", strtotime($dateTo));
            $dateFrom = date("Y-m-d", strtotime("-1 month", $time)) . " 00:00:00";

            $smsBilling = $this->em
                ->getRepository('DomainBundle:SmsHistoric')
                ->getSmsBillingByClient($client, $dateFrom, $dateTo);



            if ($smsBilling == null) {
                array_push(
                    $mailBody,
                    [
                        $client->getCompany(),
                        $client->getDomain(),
                        0,
                        0,
                        0,
                        '#'
                    ]
                );
                continue;
            }

            $mongoClient    = $this->dm->getConnection()->getMongoClient();
            $clientDatabase = StringHelper::slugDomain($client->getDomain());
            $database       = $mongoClient->$clientDatabase;
            $collection     = $database->guests;

            foreach ($smsBilling as $key => $rowBilling) {
                $query     = $collection->find(["mysql" => (int)$rowBilling["id"]], ["properties.email"]);
                $arrayData = iterator_to_array($query);
                $guest     = reset($arrayData);

                if ($guest['properties'] !== null) {
                    $email = (array_key_exists('email', $guest['properties'])) ? $guest['properties']['email'] : '';
                } else {
                    $email = "";
                }

                $data = array_slice($rowBilling, 0, 2, true) +
                    ["email" => $email] +
                    array_slice($rowBilling, 2, count($rowBilling)-1, true)
                ;

                $data['id'] = (string)$guest["_id"];

                $smsBilling[$key] = $data;
            }

            $fileName = $this->generateFileName($client->getCompany(), StringHelper::slugDomain($client->getDomain()));
            $fileLink = $this->handleCsv($smsBilling, $fileName);

            $smsCost = str_replace(',', '.', $client->getSmsCost());
            $totalSmsCost = $smsCost * count($smsBilling);

            array_push(
                $mailBody,
                [
                    $client->getCompany(),
                    $client->getDomain(),
                    count($smsBilling),
                    $smsCost,
                    $totalSmsCost,
                    $fileLink
                ]
            );
        }

        if (count($mailBody) == 0) {
            return false;
        }

        $output->writeln("<info>Foi enviado um e-mail para 'financeiro@mambowifi.com', com os dados do relatório </info>");
        $this->sendMail($month, $mailBody);
    }

    public function setHelperQuestion($helperQuestion)
    {
        $this->helperQuestion = $helperQuestion;
    }

    public function setMonth($month)
    {
        $this->month = $month;
    }

    public function getMonth()
    {
        return $this->month;
    }

    public function getHelperQuestion()
    {
        return $this->helperQuestion;
    }

    /**
     * Generate name to CSV file
     *
     * @param string $company
     * @return string
     */
    private function generateFileName($company, $domain)
    {
        return strtoupper($domain . "_" . date('Ydm_His') . ".csv");
    }

    /**
     * Get csv columns to attach on csv
     *
     * @return array
     */
    private function getColumnsNames()
    {
        $columnTitle = [];

        $columnTitle[] = 'Empresa';
        $columnTitle[] = 'ID Visitante';
        $columnTitle[] = 'E-mail Visitante';
        $columnTitle[] = 'ID SMS';
        $columnTitle[] = 'Mensagem';
        $columnTitle[] = 'Numero do Visitante';
        $columnTitle[] = 'Data de Envio';

        return $columnTitle;
    }

    /**
     * Remove local file after uploaded to S3
     *
     * @param string $file name
     */
    private function deleteLocalFile($file)
    {
        try {
            unlink("/tmp/sms/" . $file);
        } catch (\Exception $e) {
            throw new NotFoundResourceException('Unable to find local file ' . $file . ' to delete');
        }
    }

    /**
     * Handle the upload to S3
     *
     * @param string $fileName
     * @return string file url
     */
    private function handleUpload($fileName)
    {
        $finder = new Finder();

        foreach ($finder->files()->in('/tmp/sms/')->name($fileName) as $file) {
            $file = new UploadedFile(
                $file->getPathName(),
                $file->getFileName()
            );

            $object = $this->fileUpload->uploadFile($file, $fileName, 'uploads.wspot.com.br', 'sms_billing');
            $this->deleteLocalFile($fileName);

            return $object->get('ObjectURL');
        }

        throw new FileNotFoundException('Unable to find file ' . $fileName . ' to upload');
    }

    /**
     * Create the CSV file based on $billing array and save it locally
     *
     * @param array $billing
     * @param string $fileName
     * @return string file url
     */
    private function handleCsv(array $billing, $fileName)
    {
        $fileResource = fopen("/tmp/sms/" . $fileName, "wb");

        fputcsv($fileResource, $this->getColumnsNames());

        foreach ($billing as $row) {
            array_push($row, date_format($row['sentDate'], 'd/m/Y H:i:s'));

            unset($row['sentDate']);

            fputcsv(
                $fileResource,
                $row,
                ','
            );
        }
        fclose($fileResource);

        return $this->handleUpload($fileName);
    }

    /**
     *
     * Send mail to 'financeiro' with client accounts
     *
     * @param array $content
     */
    private function sendMail($month, array $content)
    {

        $builder = new MailMessageBuilder();
        $message = $builder
            ->subject("Fechamento de Cobranca de SMS. Período: mês {$month}")
            ->from(['Mambo WiFi Automatico' => $this->emailHeader->getSender()])
            ->to($this->emailHeader->getFinancialRecipient())
            ->htmlMessage(
                $this->renderView(
                    'AdminBundle:SmsHistoric:smsBilling.html.twig',
                    [
                        'fields' => $content
                    ]
                )
            )
            ->build()
        ;

        $this->mailerService->send($message);
    }

    /**
     * @param FileUpload $fileUpload
     */
    public function setFileUpload(FileUpload $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }

    public function setMongoDocumentManager(DocumentManager $dm)
    {
        $this->dm = $dm;
    }
}
