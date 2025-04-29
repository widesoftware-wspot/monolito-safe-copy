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

class SmsBillingService
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

    /**
     * Get all clients to close their SMS accounts
     */
    public function execute()
    {
        $day = date('d');

        $clients = $this->em
            ->getRepository('DomainBundle:Client')
            ->getClientsByClosingDate($day);

        $mailBody = [];

        foreach ($clients as $client) {
            $dateFrom   = date_format(new \DateTime('now-1month'), 'Y-m-');
            $dateFrom   = $dateFrom.DateTimeHelper::formatHour($client->getClosingDate()).' 00:00:00';
            $dateTo     = date_format(new \DateTime('now-1day'), 'Y-m-d 23:59:59');

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

                $email = (array_key_exists('email', $guest['properties'])) ? $guest['properties']['email'] : '';

                $data = array_slice($rowBilling, 0, 2, true) +
                    ["email" => $email] +
                    array_slice($rowBilling, 2, count($rowBilling)-1, true)
                ;

                $data['id'] = (string)$guest["_id"];

                $smsBilling[$key] = $data;
            }

            $clientDomain = StringHelper::slugDomain($client->getDomain());
            $fileName = $this->generateFileName($client->getCompany(), $clientDomain);
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

        $this->sendMail($day, $mailBody);
    }

    /**
     * Generate name to CSV file
     *
     * @param string $company
     * @return string
     */
    private function generateFileName($company, $domain)
    {
        return strtoupper(str_replace(" ", "", $company)) . "_" . $domain . "_" . date('Ydm_His') . ".csv";
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
            unlink("/tmp/" . $file);
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

        foreach ($finder->files()->in('/tmp/')->name($fileName) as $file) {
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
        $fileResource = fopen("/tmp/" . $fileName, "wb");

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
     * Send mail to 'financeiro' with client accounts
     *
     * @param array $content
     */
    private function sendMail($day, array $content)
    {
        $builder = new MailMessageBuilder();
        $message = $builder
            ->subject("Fechamento de Cobranca de SMS - Dia {$day}")
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
