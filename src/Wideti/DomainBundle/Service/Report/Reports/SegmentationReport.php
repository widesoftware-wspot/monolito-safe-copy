<?php

namespace Wideti\DomainBundle\Service\Report\Reports;

use Wideti\DomainBundle\Document\Group\Group;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Dto\ReportDto;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Helpers\SegmentationHelper;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Repository\SegmentationRepository;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Report\ReportFormat;
use Wideti\DomainBundle\Service\Report\ReportType;
use Wideti\DomainBundle\Service\ReportFileBuilder\ReportFileBuilder;
use Wideti\DomainBundle\Service\Segmentation\Resolver\FilterResolver;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class SegmentationReport implements Report
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
    /**
     * @var SegmentationRepository
     */
    private $segmentationRepository;
    /**
     * @var FilterResolver
     */
    private $filterResolver;
    private $bucket;

    /**
     * GuestReport constructor.
     * @param $bucket
     * @param FileUpload $fileUpload
     * @param $tempFileFolder
     * @param ConfigurationService $configurationService
     * @param SegmentationRepository $segmentationRepository
     * @param FilterResolver $filterResolver
     */
    public function __construct(
        $bucket,
        FileUpload $fileUpload,
        $tempFileFolder,
        ConfigurationService $configurationService,
        SegmentationRepository $segmentationRepository,
        FilterResolver $filterResolver
    ) {
        $this->bucket = $bucket;
        $this->fileUpload = $fileUpload;
        $this->tempFileFolder = $tempFileFolder;
        $this->configurationService = $configurationService;
        $this->segmentationRepository = $segmentationRepository;
        $this->filterResolver = $filterResolver;
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
            ->countByFilter($filters);

        return $count;
    }

    /**
     * @param $charset
     * @param array $filters
     * @param Client $client
     * @param Users $user
     * @param bool $isBatch
     * @param string $format
     * @return mixed|ReportDto
     * @throws \Exception
     */
    public function getReport($charset, array $filters, Client $client, Users $user, $isBatch = false, $format = ReportFormat::CSV)
    {
        $this->charset = $charset;

        $content = [];

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

        $fileBuilder    = new ReportFileBuilder($this->fileUpload, $this->tempFileFolder, $format);
        $reportBatchDto = new ReportDto();
        $reportBatchDto->setColumns($columns);
        $fileBuilder->addContent($reportBatchDto);
        $reportBatchDto->clearColumns();

        $allAps = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->getAccessPointsListToGuestReport($client->getId());

        $segmentationId = $filters[0];
        $segmentation   = $this->segmentationRepository->find($segmentationId);
        $filterDto      = SegmentationHelper::convertToFilterDto($client->getId(), $segmentation);

        $query          = $this->filterResolver->resolve($filterDto);
        $count          = count($query);

        for ($skip = 0; $skip < $count; $skip++) {
            $params = [
                'download' => true,
                'skip' => $skip
            ];

            $iterableResult = $this->filterResolver->resolve($filterDto, false, $params);

            foreach ($iterableResult as $entity) {
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

                    $group = $group->getName();
                }

                array_push($row, $this->utf8Fix($group));
                array_push($row, $this->utf8Fix($signupOrigin));
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
                        if (count($content) == 1000 || end($content)) {
                            $reportBatchDto->setContent($content);
                            $fileBuilder->addContent($reportBatchDto);
                            $reportBatchDto->clearContent();
                            unset($content);
                            $content = [];
                        }
                    } catch (\Exception $e) {
                        $this->logger->addCritical(
                            "Erro ao criar o batch de segmentacao: " . $e->getMessage()
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

            $skip = $skip + 1000;
        }

        $report = new ReportDto();

        $finalFile  = $fileBuilder->build();
        $result     = $this->fileUpload->uploadReports(
            $this->bucket,
            $client,
            ReportType::SEGMENTATION,
            $finalFile,
            $format
        );
        unlink($finalFile);
        $report->setExpireDate($result['delete_date']);
        $report->setFilePath($result['ObjectURL']);
        $report->setIsBatch(true);

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
