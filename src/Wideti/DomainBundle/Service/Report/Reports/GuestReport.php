<?php

namespace Wideti\DomainBundle\Service\Report\Reports;

use Exception;
use Wideti\DomainBundle\Document\Group\Group;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Dto\ReportDto;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Service\AuditLogs\AuditEvent;
use Wideti\DomainBundle\Service\AuditLogs\Auditor;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Report\ReportFormat;
use Wideti\DomainBundle\Service\Report\ReportType;
use Wideti\DomainBundle\Service\ReportFileBuilder\ReportFileBuilder;
use Wideti\DomainBundle\Twig\GuestGroup;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class GuestReport implements Report
{
    use MongoAware;
    use EntityManagerAware;
    use SessionAware;
    use LoggerAware;

    private $charset;

    /**
     * @var FileUpload
     */
    private $fileUpload;
    /**
     * @var string
     */
    private $tempFileFolder;
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    private $bucket;

    /**
     * @var Auditor
     */
    private $auditor;

    /**
     * GuestReport constructor.
     * @param $bucket
     * @param FileUpload $fileUpload
     * @param $tempFileFolder
     * @param ConfigurationService $configurationService
     * @param Auditor $auditor
     */
    public function __construct(
        $bucket,
        FileUpload $fileUpload,
        $tempFileFolder,
        ConfigurationService $configurationService,
        Auditor $auditor
    ) {
        $this->bucket = $bucket;
        $this->fileUpload = $fileUpload;
        $this->tempFileFolder = $tempFileFolder;
        $this->configurationService = $configurationService;
        $this->auditor = $auditor;
    }

    public function countResult(array $filters, Client $client)
    {
        $domain = $client->getDomain();

        if($client->isWhiteLabel()) {
            $domain = StringHelper::slugDomain($domain);
        }


        $this->setDefaultDatabaseOnMongo($domain);

        $count = $this
            ->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->countByFilter($filters, null, true);

        return $count;
    }

    /**
     * @param $charset
     * @param array $filters
     * @param Client $client
     * @param bool $isBatch
     * @param string $format
     * @param Users $user
     * @return ReportDto
     * @throws Exception
     */
    public function getReport(
        $charset,
        array $filters,
        Client $client,
        Users $user,
        $isBatch = false,
        $format = ReportFormat::CSV
    ) {
        $this->charset = $charset;

        $content = [];

        $params = $filters;

        $domain = $client->getDomain();

        if($client->isWhiteLabel()) {
            $domain = StringHelper::slugDomain($domain);
        }

        $this->setDefaultDatabaseOnMongo($domain);

        $columns = [];

        $signupFields = $this->mongo
            ->getRepository('DomainBundle:CustomFields\Field')
            ->findSignUpFields()
        ;

        $signupFields   = $signupFields->toArray();
        $fields         = $this->orderFields($signupFields);
        $hasEmail       = false;
        $hasDocument    = false;

        foreach ($fields as $field) {
            $columns[] = $field->getNames()['pt_br'];
            if ($field->getIdentifier() == 'email') {
                $hasEmail = true;
            }

            if ($field->getIdentifier() == 'document') {
                $hasDocument = true;
            }
        }

        if ($hasEmail) {
            $columns[] = $this->utf8Fix('E-mail válido?');
        }

        if ($hasDocument) {
            $columns[] = 'Tipo Documento';
        }

        $hasOptInActive = true;

        $apGroups = $this->em
            ->getRepository('DomainBundle:AccessPointsGroups')
            ->findBy([
                'client' => $client
            ]);

        if (count($apGroups) == 1) {
            $config = $this->configurationService->getDefaultConfiguration($client);
            $hasOptInActive = boolval($config['authorize_email']);
        }

        $columns[] = 'Idioma';
        $columns[] = 'Data cadastro';
        $columns[] = 'Grupo';
        $columns[] = 'Cadastro via';
        $columns[] = 'Recorrencia';
        $columns[] = 'Status';
        if ($hasOptInActive) {
            $columns[] = 'Aceita receber novidades';
        }
        $columns[] = 'Visitante recorrente';
        $columns[] = 'Ponto de Acesso';

        $facebookFields = [
            'id'            => 'Facebook ID',
            'first_name'    => 'Facebook Nome',
            'last_name'     => 'Facebook Sobrenome',
            'gender'        => 'Facebook Sexo',
            'age_range'     => 'Facebook Faixa de Idade',
        ];

        foreach ($facebookFields as $key => $value) {
            $columns[] = $value;
        }

        $fileBuilder    = null;
        $reportBatchDto = null;

        if ($isBatch) {
            $fileBuilder    = new ReportFileBuilder($this->fileUpload, $this->tempFileFolder, $format);
            $reportBatchDto = new ReportDto();
            $reportBatchDto->setColumns($columns);
            $fileBuilder->addContent($reportBatchDto);
            $reportBatchDto->clearColumns();
        }

        $allAps = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->getAccessPointsListToGuestReport($client->getId());

        $query = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->search($params, null, true)
        ;

        $skip  = 0;
        $count = count($query);
        $skipIncrement = 1000;

        while ($skip < $count) {
            $params['skip'] = $skip;

            $iterableResult = $this->mongo
                ->getRepository('DomainBundle:Guest\Guest')
                ->search($params, null, true)
            ;

            foreach ($iterableResult as $entity) {
                // Audit exportation
                $event = $this->auditor
                    ->newEvent()
                    ->withClient($client->getId())
                    ->withSource(Kinds::userAdmin(), $user->getId())
                    ->onTarget(Kinds::guest(), $entity->getMysql())
                    ->withType(Events::export())
                    ->addDescription(AuditEvent::PT_BR, 'Usuário exportou visitante')
                    ->addDescription(AuditEvent::EN_US, 'User exported guest')
                    ->addDescription(AuditEvent::ES_ES, 'Visitante exportado por el usuario');
                $this->auditor->push($event);

                /** @var Guest $guest */
                $guest          = $entity;
                $authorizeEmail = ($guest->getAuthorizeEmail() == 1) ? 'Sim' : (($guest->getAuthorizeEmail() === null) ? '' : 'Não');
                $returningGuest = ($guest->getReturning() == 1) ? 'Sim' : 'Não';
                $emailIsValid   = ($guest->getEmailIsValid()) ? 'Sim' : 'Não';
                $signupOrigin   = 'Formulário';
                $guestSocial    = $guest->getSocial();

                if ($guestSocial->count() > 0) {
                    $signupOrigin = $this->mongo
                        ->getRepository('DomainBundle:Guest\Guest')
                        ->convertSocialTypeInString($guestSocial->first()->getType());
                }

                $accessPointName = isset($allAps[$guest->getRegistrationMacAddress()])
                    ? $allAps[$guest->getRegistrationMacAddress()] : $guest->getRegistrationMacAddress();

                $row             = [];
                $choiceFields    = [];

                // Custom Fields
                foreach ($fields as $field) {
                    if ($field->getChoices()) {
                        foreach ($field->getChoices()['pt_br'] as $key => $value) {
                            if ($value) {
                                $choiceFields[$field->getIdentifier()][$value] = $key;
                            }
                        }
                    }
                }

                unset($field);

                foreach ($fields as $field) {
                    if (array_key_exists($field->getIdentifier(), $guest->getProperties())) {
                        $value = $guest->getProperties()[$field->getIdentifier()];

                        if ($value instanceof \MongoDate) {
                            $dt    = new \DateTime(date('Y-m-d H:i:s', $value->sec));
                            $value = $dt->format("d/m/Y");
                        }

                        if ($field->getType() == 'choice' && (is_int($value) || strlen($value) == 1)) {
                            if (array_key_exists($field->getIdentifier(), $choiceFields)) {
                                $option = $choiceFields[$field->getIdentifier()];
                                $value  = $option[$guest->getProperties()[$field->getIdentifier()]];
                            }
                        }

                        array_push($row, $this->utf8Fix($value));
                    } else {
                        array_push($row, "");
                    }
                }

                // Default Fields
                if ($hasEmail) {
                    array_push($row, $this->utf8Fix($emailIsValid));
                }

                if ($hasDocument) {
                    array_push($row, $guest->getDocumentType());
                }

                array_push($row, $guest->getLocale());
                array_push(
                    $row,
                    ($guest->getCreated() instanceof \DateTime) ? $guest->getCreated()->format('d/m/Y') : null
                );

                $group = 'Visitantes';

                if ($guest->getGroup()) {
                    /**
                     * @var Group $group
                     */
                    $group = $this->mongo
                        ->getRepository('DomainBundle:Group\Group')
                        ->findOneByShortcode($guest->getGroup());

                    $group = ($group) ? $group->getName() : 'Visitantes';
                }

                array_push($row, $this->utf8Fix($group));
                array_push($row, $this->utf8Fix($signupOrigin));
                array_push($row, $this->utf8Fix($guest->getReturning() ? 'Recorrente' : 'Único'));
                array_push($row, $this->utf8Fix($guest->getStatusAsString()));
                if ($hasOptInActive) {
                    array_push($row, $this->utf8Fix($authorizeEmail));
                }
                array_push($row, $this->utf8Fix($returningGuest));
                array_push($row, $this->utf8Fix($accessPointName));

                // Facebook Fields
                if ($guest->getFacebookFields()) {
                    foreach ($guest->getFacebookFields() as $key => $value) {
                        if (array_key_exists($key, $facebookFields)) {
                            array_push($row, $this->utf8Fix($value));
                        }
                    }
                }

                unset($field);

                array_push($content, $row);

                if ($isBatch) {
                    try {
                        if (count($content) == $skipIncrement) {
                            $reportBatchDto->setContent($content);
                            $fileBuilder->addContent($reportBatchDto);
                            $reportBatchDto->clearContent();
                            unset($content);
                            $content = [];
                        }
                    } catch (Exception $e) {
                        $this->logger->addCritical(
                            "Erro ao criar o batch de relatorio de visitantes: " . $e->getMessage()
                        );
                    }
                }

                unset($accessPoint);
                unset($guest);
                unset($signupOrigin);

                $this->em->clear();
                $this->mongo->clear();
            }

            unset($iterableResult);
            $this->mongo->clear();

            $skip = $skip + $skipIncrement;
        }

        if (($isBatch) && (count($content) > 0)) {
            $reportBatchDto->setContent($content);
            $fileBuilder->addContent($reportBatchDto);
            $reportBatchDto->clearContent();
            unset($content);
            $content = [];
        }

        $report = new ReportDto();

        if ($isBatch) {
            $finalFile  = $fileBuilder->build();
            $result     = $this->fileUpload->uploadReports(
                $this->bucket,
                $client,
                ReportType::GUEST,
                $finalFile,
                $format
            );
            unlink($finalFile);
            $report->setExpireDate($result['delete_date']);
            $report->setFilePath($result['ObjectURL']);
            $report->setIsBatch(true);
            return $report;
        } else {
            $report->setColumns($columns);
            $report->setContent($content);
        }

        return $report;
    }

    private function setDefaultDatabaseOnMongo($domain)
    {
        $manager = $this->mongo;

        $manager
            ->getConfiguration()
            ->setDefaultDB($domain)
        ;

        $this->mongo->create(
            $manager->getConnection(),
            $manager->getConfiguration(),
            $manager->getEventManager()
        );
    }

    public function utf8Fix($string)
    {
        if ($this->charset == 'windows' && $_REQUEST['fileFormat'] == ReportFormat::CSV) {
            return mb_convert_encoding($string, 'Windows-1252', 'UTF-8');
        }

        return $string;
    }

    public function orderFields($fields)
    {
        $firstFields    = [];
        $secondFields   = [];

        foreach ($fields as $field) {
            if (substr($field->getIdentifier(), 0, 3) == 'ddd' || substr($field->getIdentifier(), 0, 3) == 'phone') {
                array_push($secondFields, $field);
            } else {
                array_push($firstFields, $field);
            }
        }

        return array_merge($firstFields, $secondFields);
    }
}
