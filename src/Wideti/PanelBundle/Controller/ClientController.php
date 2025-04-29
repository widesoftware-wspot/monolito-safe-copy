<?php

namespace Wideti\PanelBundle\Controller;

use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wideti\DomainBundle\Document\Repository\Fields;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\LegalKinds;
use Wideti\DomainBundle\Entity\SmartLocationCredentials;
use Wideti\DomainBundle\Helpers\ClientHelper;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Service\Client\ClientServiceAware;
use Wideti\DomainBundle\Service\ClientLogs\ClientLogsService;
use Wideti\DomainBundle\Service\ClientLogs\Dto\ClientOptionsGetLogDto;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsAware;
use Wideti\DomainBundle\Service\DataProtect\DataController\DataControllerServiceInterface;
use Wideti\DomainBundle\Service\DataProtect\DataController\Exceptions\DataControllerNotFoundRuntimeException;
use Wideti\DomainBundle\Service\EmailConfigNas\EmailConfigNasAware;
use Wideti\DomainBundle\Service\EntityLogger\EntityLoggerService;
use Wideti\DomainBundle\Service\FirstConfig\FirstConfigService;
use Wideti\DomainBundle\Service\Jaeger\TracerHeaders;
use Wideti\DomainBundle\Service\LegalBaseManager\LegalBaseManagerService;
use Wideti\DomainBundle\Service\Mailchimp\MailchimpServiceAware;
use Wideti\DomainBundle\Service\Mikrotik\MikrotikServiceAware;
use Wideti\DomainBundle\Service\SmsBilling\SmsBillingService;
use Wideti\DomainBundle\Service\Unifi\UnifiService;
use Wideti\DomainBundle\Service\User\UserService;
use Wideti\PanelBundle\Form\Type\ClientFilterType;
use Wideti\PanelBundle\Form\Type\ClientType;
use Wideti\PanelBundle\Service\MongoDatabaseService;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\FormAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\PaginatorAware;
use Wideti\WebFrameworkBundle\Aware\RouterAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class ClientController
{
    use EntityManagerAware;
    use MongoAware;
    use ClientServiceAware;
    use TwigAware;
    use FormAware;
    use RouterAware;
    use PaginatorAware;
    use MikrotikServiceAware;
    use EmailConfigNasAware;
    use MailchimpServiceAware;
    use CustomFieldsAware;
    use LoggerAware;
    use FlashMessageAware;

    private $bucket;
    /**
     * @var FrontendControllerHelper
     */
    private $controllerHelper;
    /**
     * @var FileUpload
     */
    private $fileUpload;
    /**
     * @var EntityLoggerService
     */
    private $entityLoggerService;
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var UserService
     */
    private $userService;
    /**
     * @var ClientLogsService
     */
    private $clientLogsService;
    /**
     * @var SmsBillingService
     */
    private $smsBillingService;
    /**
     * @var ClientHelper
     */
    private $clientHelper;
    /**
     * @var DataControllerServiceInterface
     */
    private $dataControllerService;

    /**
     * @var UnifiService
     */
    private $unifiService;

    /**
     * @var MongoDatabaseService
     */
    private $mongoDatabaseService;

    /**
     * @var FirstConfigService
     */
    private $firstConfigService;

    /**
     * @var LegalBaseManagerService
     */
    private $legalBaseManagerService;

    /**
     * ClientController constructor.
     * @param $bucket
     * @param ConfigurationService $configurationService
     * @param FrontendControllerHelper $controllerHelper
     * @param FileUpload $fileUpload
     * @param EntityLoggerService $entityLoggerService
     * @param UserService $userService
     * @param ClientLogsService $clientLogsService
     * @param SmsBillingService $smsBillingService
     * @param ClientHelper $clientHelper
     * @param UnifiService $unifiService
     * @param MongoDatabaseService $mongoDatabaseService
     */
    public function __construct(
        $bucket,
        ConfigurationService $configurationService,
        FrontendControllerHelper $controllerHelper,
        FileUpload $fileUpload,
        EntityLoggerService $entityLoggerService,
        UserService $userService,
        ClientLogsService $clientLogsService,
        SmsBillingService $smsBillingService,
        ClientHelper $clientHelper,
        DataControllerServiceInterface $dataControllerService,
        UnifiService $unifiService,
        MongoDatabaseService $mongoDatabaseService,
        FirstConfigService $firstConfigService,
        LegalBaseManagerService $legalBaseManagerService
    ) {
        $this->bucket = $bucket;
        $this->configurationService  = $configurationService;
        $this->controllerHelper      = $controllerHelper;
        $this->fileUpload            = $fileUpload;
        $this->entityLoggerService   = $entityLoggerService;
        $this->userService           = $userService;
        $this->clientLogsService     = $clientLogsService;
        $this->smsBillingService     = $smsBillingService;
        $this->clientHelper          = $clientHelper;
        $this->dataControllerService = $dataControllerService;
        $this->unifiService          = $unifiService;
        $this->mongoDatabaseService  = $mongoDatabaseService;
        $this->firstConfigService = $firstConfigService;
        $this->legalBaseManagerService = $legalBaseManagerService;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $filterForm = $this->controllerHelper->createForm(ClientFilterType::class);
        $option     = null;
        $value      = null;
        $plan       = null;
        $status     = null;

        $filterForm->handleRequest($request);

        $page       = $request->query->getInt('page', '1');

        if ($filterForm->isValid()) {
            $option = $filterForm->get('option')->getData();
            $value  = $filterForm->get('value')->getData();
            $plan   = $filterForm->get('plan')->getData();
            $status = $filterForm->get('status')->getData();
        }

        $query = $this->em
            ->getRepository('DomainBundle:Client')
            ->filterClient($option, $value, $plan, $status);

        $pagination = $this->paginator->paginate($query, $page, 20);

        $numInactiveClients = $this->em
            ->getRepository('DomainBundle:Client')
            ->countAllInactiveClients();

        $numActiveClients = $this->em
            ->getRepository('DomainBundle:Client')
            ->countAllActiveClients();

        $numPocClients = $this->em
            ->getRepository('DomainBundle:Client')
            ->countAllPocClients();

        return $this->render(
            'PanelBundle:Client:index.html.twig',
            [
                'entities'  => $pagination,
                'numInactiveClients' => $numInactiveClients,
                'numActiveClients' => $numActiveClients,
                'numPocClients' => $numPocClients,
                'filter'    => $filterForm->createView()
            ]
        );
    }

    public function manualBillingSmsAction(Request $request)
    {
        $this->smsBillingService->execute([
            'clientId' => $request->get('id')
        ]);

        return new JsonResponse(['msg' => 'Cobrança de SMS manual realizada com sucesso']);
    }

    /**
     * @param $id
     * @param $userEmail
     * @return JsonResponse
     */
    public function sendConfigurationMailAction($id, $userEmail)
    {
        $entity = $this->em
            ->getRepository('DomainBundle:Client')
            ->find($id);

        if ($this->emailConfigService->sendConfig($entity, $userEmail)) {
            $message = 'Email enviado com sucesso';
        } else {
            $message = 'Erro ao enviar email';
        }

        return new JsonResponse(['Message' => $message]);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \MongoCursorException
     * @throws \MongoCursorTimeoutException
     * @throws \MongoException
     * @throws \Wideti\DomainBundle\Exception\SendEmailFailException
     */
    public function newAction(Request $request)
    {
        $client = new Client();
        $client->setCreatedBy(Client::CREATED_BY_PANEL);
        $client->__set('hostUrl', $request->getHost());
        $form   = $this->controllerHelper->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        $smartLocationEntity = $this->em
            ->getRepository('DomainBundle:SmartLocationCredentials')
            ->findOneBy([
            	'client' => $client
            ]);

        $smartLocationModule = $this->em
            ->getRepository('DomainBundle:Module')
            ->findOneBy([
                'shortCode' => 'smart_location'
            ]);

        if (!$smartLocationEntity) {
            $smartLocationEntity = new SmartLocationCredentials();
            $smartLocationEntity->setClient($client);
        }

        if ($form->isValid()) {
            if (ClientHelper::panelDomainIsValid($client->getDomain()) &&
                !$this->clientHelper->checkIfIsReservedDomain($client->getDomain())
            ) {
                $additionalInfo = [
                    'from_email'            => $request->get('admin_email_sender'),
                    'redirect_url'          => $request->get('admin_redirect_url'),
                    'partner_name'          => $request->get('admin_company_name'),
                    'email_sender_default'  => $request->get('client_email_sender_default'),
                ];
                $traceHeaders = TracerHeaders::from($request);
                $authenticationType = $request->request->get($form->getName())['authenticationType'];
                $client->setAuthenticationType($authenticationType);
                $this->clientService->create($client, $additionalInfo, $traceHeaders);
                $this->userService->createAdminUserByBluePanel($request, $form);
                $clientDatabaseName = $client->getMongoDatabaseName();
                $this->mongoDatabaseService->setDefaultDatabaseOnMongo($clientDatabaseName);

                if ($client->getNoRegisterFields()) {
                    $this->firstConfigService->firstConfigurationNoRegisterFields($client);
                }
                if ($client->getModules()->contains($smartLocationModule)) {
                    $smartLocationAccountName = $request->get('smart_location_accountname');
                    $smartLocationCustomerId = $request->get('smart_location_customerid');
                    $smartLocationPassword = $request->get('smart_location_password');

                    $smartLocationEntity->setAccountName($smartLocationAccountName);
                    $smartLocationEntity->setCustomerId($smartLocationCustomerId);
                    $smartLocationEntity->setPassword($smartLocationPassword);
                    $this->em->persist($smartLocationEntity);
                    $this->em->flush();
                }
                return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('panel_client_list'));
            }

            $form->get('domain')
                ->addError(
                    new FormError('Domínio não permitido')
                );
        }
        return $this->render(
            'PanelBundle:Client:new.html.twig',
            [
                'entity'              => $client,
                'smartLocationEntity' => $smartLocationEntity,
                'form'                => $form->createView(),
                'error'               => ''
            ]
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function checkNewAdminDataAction(Request $request)
    {
        $email = $request->get('email');
        $emailExists = $this->userService->checkUserEmailExists($email);
        if (!$emailExists) {
            return new JsonResponse([
                "message" => "O email '{$email}' já existe na base de dados.",
                "found" => true
            ]);
        } else {
            return new JsonResponse([
                "message" => "O email '{$email}' já existe na base de dados.",
                "found" => false
            ]);
        }
    }

    /**
     * Generate default files to be placed into Mikrotik Device
     * Check if you have zip library in your system. To install:
     *  - pecl install zip
     *  - Add /usr/lib/php5/20121212/zip.so in you php.ini
     * @param $domain
     * @return BinaryFileResponse
     */
    public function downloadMikrotikFilesAction($domain)
    {
        return $this->mikrotikService->generateConfigFiles($domain);
    }

    /**
     * Generate default files to be placed into Ubiquiti/UNIFI Device
     * Check if you have zip library in your system. To install:
     *  - pecl install zip
     *  - Add /usr/lib/php5/20121212/zip.so in you php.ini
     * @param $domain
     * @return BinaryFileResponse
     */
    public function downloadUnifiFilesAction($domain)
    {
        return $this->unifiService->generateConfigFiles($domain);
    }

    /**
     * @param Client $client
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction(Client $client, Request $request)
    {
        $client->__set('hostUrl', $request->getHost());

        $clientDatabaseName             = $client->getMongoDatabaseName();
        $this->mongoDatabaseService->setDefaultDatabaseOnMongo($clientDatabaseName);
        $isEnablePasswordAuthentication = $client->isEnablePasswordAuthentication();
        $noRegisterFields               = $client->getNoRegisterFields();
        $editPasswordAuthentication     = $this->mongoDatabaseService->evaluatePasswordActivation($isEnablePasswordAuthentication);
        $guests                         = $this->mongoDatabaseService->getGuests();
        $registerFields                 = $this->customFieldsService->getFieldsToLogin()->getFields();
        $canEnableNoRegisterFields      = !$guests || $noRegisterFields;
        $canDisableNoRegisterFields     = !$guests || !$noRegisterFields;

        $smartLocationEntity = $this->em
            ->getRepository('DomainBundle:SmartLocationCredentials')
            ->findOneBy([
            	'client' => $client
            ]);

        $smartLocationModule = $this->em
        ->getRepository('DomainBundle:Module')
        ->findOneBy([
            'shortCode' => 'smart_location'
        ]);

        if (!$smartLocationEntity) {
            $smartLocationEntity = new SmartLocationCredentials();
            $smartLocationEntity->setClient($client);
        }
        
        $editForm = $this->controllerHelper->createForm(
            ClientType::class,
            $client,
            [
                'validation_groups' => ['onUpdate'],
                'editPasswordAuthentication'    => $editPasswordAuthentication,
                'canEnableNoRegisterFields'     => $canEnableNoRegisterFields,
                'canDisableNoRegisterFields'    => $canDisableNoRegisterFields,
            ]
        );
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            try {
                $clientEmailSender = $request->get('client_email_sender_default');
                $authenticationType = $request->request->get($editForm->getName())['authenticationType'];
                if ($authenticationType == "disable_password_authentication") {
                    //verifica se os campos alem do login são unicos, se forem é setado como não unico
                    $this->mongo->getRepository('DomainBundle:CustomFields\Field');
                    foreach ($registerFields as $registerField) {
                        $identifier = $registerField->getIdentifier();
                        $field = $this->customFieldsService->getFieldByNameType($identifier);
                        if (!$field->getIsLogin() && $field->getIsUnique()) {
                            $field->setIsUnique(false);
                            $this->mongo->persist($field);
                        }
                    }
                    $this->mongo->flush();
                    $this->configurationService->updateKey('login_form', false, $client);
                }
                $oldNoRegisterFieldsValue = $client->getNoRegisterFields();
                $client->setAuthenticationType($authenticationType);
                $newNoRegisterFieldsValue = $client->getNoRegisterFields();
                # Verifica se a autenticação sem campos foi ativada ou desativada
                if ($newNoRegisterFieldsValue != $oldNoRegisterFieldsValue) {
                    $this->changeNoRegisterFieldConfigurations($newNoRegisterFieldsValue, $client);
                }

                # Verifica se o email do remetente do painel foi alteado
                if ($clientEmailSender != $client->getEmailSenderDefault()) {
                    $this->clientService->verifyEmailAddress($clientEmailSender);
                }

                if ($client->getModules()->contains($smartLocationModule)) {
                    $smartLocationAccountName = $request->get('smart_location_accountname');
                    $smartLocationCustomerId = $request->get('smart_location_customerid');
                    $smartLocationPassword = $request->get('smart_location_password');

                    $smartLocationEntity->setAccountName($smartLocationAccountName);
                    $smartLocationEntity->setCustomerId($smartLocationCustomerId);
                    $smartLocationEntity->setPassword($smartLocationPassword);
                    $this->em->persist($smartLocationEntity);
                }

                $this->clientService->update($client);
                $this->setUpdatedFlashMessage();
            } catch (\Exception $e) {
                die($e);
            }
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('panel_client_list'));
        }

        return $this->render(
            'PanelBundle:Client:edit.html.twig',
            [
                'entity'                        => $client,
                'smartLocationEntity'           => $smartLocationEntity,
                'editPasswordAuthentication'    => $editPasswordAuthentication,
                'canEnableNoRegisterFields'     => $canEnableNoRegisterFields,
                'canDisableNoRegisterFields'    => $canDisableNoRegisterFields,
                'edit_form'                     => $editForm->createView(),
                "fields_to_login"               => count($registerFields)
            ]
        );
    }

    private function changeNoRegisterFieldConfigurations($newNoRegisterFieldsValue, Client $client) {
        if ($newNoRegisterFieldsValue) {
            $this->firstConfigService->firstConfigurationNoRegisterFields($client);
            $this->legalBaseManagerService->defineLegalBase($client, LegalKinds::LEGITIMO_INTERESSE);
            $this->configurationService->updateNoRegisterFieldsConfigKeys($client);
        } else {
            $this->firstConfigService->clearFirstConfiguration($client);
            $this->legalBaseManagerService->defineLegalBase($client, LegalKinds::TERMO_CONSENTIMENTO);
            if ($client->isEnablePasswordAuthentication()) {
                $this->configurationService->updateKey('login_form', true, $client);
            }
        }
    }

	/**
	 * @param Request $request
	 * @return Response
	 * @throws \Exception
	 */
    public function showAction(Request $request)
    {
        $id = $request->get("id");
        $request->get("page") ? $page  = (int)$request->get("page") : (int)$page = 0;
        if ($page < 0) {
            return $this->controllerHelper->redirectToRoute('panel_client_show', ['id'  => $id]);
        }

        $entity = $this->em
            ->getRepository('DomainBundle:Client')
            ->listAllClientsAndUsersInPoc($id);

        $pocEnded = false;

        if ($entity->getPocEndDate() < new \DateTime('now')) {
            $pocEnded = true;
        }

        $clientOptionsGetLogDto = new ClientOptionsGetLogDto();
        $clientOptionsGetLogDto->setClientId($id);
        $clientOptionsGetLogDto->setPage($page);
        $clientOptionsGetLogDto->setSize(12);
        $logs = $this->clientLogsService->getLogsBy($clientOptionsGetLogDto);

        try {
            $dataController = $this->dataControllerService->getDataControllerAgent($entity);
        }catch (DataControllerNotFoundRuntimeException $e){
            $dataController = null;
        }

        return $this->render(
            'PanelBundle:Client:show.html.twig',
            [
                'entity'         => $entity,
                'pocEnded'       => $pocEnded,
                'page'           => $page,
                'logs'           => $logs,
                'dataController' => $dataController
            ]
        );
    }

    /**
     * @return JsonResponse
     */
    public function adminUsersListAction()
    {
        $sync = $this->mailchimpService->syncAdminUsersList();
        return new JsonResponse($sync, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function changeDomainAction(Request $request)
    {
	    $clientId    = $request->request->get('id');
	    $client      = $this->em
            ->getRepository('DomainBundle:Client')
            ->findOneBy(['id' => $clientId]);

        $oldDomain   = $client->getDomain();
        $newDomain   = $request->request->get('newDomain');

        try {
            $this->customMongoDBCopy($oldDomain, $newDomain);
        } catch (\Exception $ex) {
            $this->logger->addCritical("Fail to change client domain (1): {$ex->getMessage()}");
            return new JsonResponse(['msg' => 'Ocorreu um erro ao alterar o domínio do cliente.']);
        }

        try {
        	$this->configurationService->updateKey('aws_folder_name', $newDomain, $client);
            $this->fileUpload->moveBetweenFolders($this->bucket, $oldDomain, $newDomain);
            $client->setDomain($newDomain);
            $client->setMongoDatabaseName(StringHelper::slugDomain($newDomain));
            if(strpos($newDomain, '.') !== false){
                $client->setIsWhiteLabel(true);
            }else {
                $client->setIsWhiteLabel(false);
            }
            $this->em->persist($client);
            $this->em->flush();
        } catch (\Exception $ex) {
            $this->logger->addCritical("Fail to change client domain (2): {$ex->getMessage()}");
            return new JsonResponse(['msg' => 'Ocorreu um erro ao alterar o domínio do cliente.']);
        }


        // Log
        $this->entityLoggerService->log([
            'module'    => 'Client',
            'action'    => 'update',
            'changeset' => [
                'id' => $client->getId(),
                'changes' => [
                    'value' => [
                        $oldDomain,
                        $newDomain
                    ]
                ],
                'field' => 'domain'
            ]
        ]);

        return new JsonResponse(['msg' => 'O domínio do cliente foi alterado.']);
    }

	/**
	 * @param $oldDomain
	 * @param $newDomain
	 * @throws \MongoCursorException
	 * @throws \MongoException
	 */
    private function customMongoDBCopy($oldDomain, $newDomain)
    {
        $mongoClient    = $this->mongo->getConnection()->getMongoClient();
        $oldDomain      = StringHelper::slugDomain($oldDomain);
        $oldDb          = $mongoClient->$oldDomain;
        $collections    = $oldDb->getCollectionNames();
        $newDomain      = StringHelper::slugDomain($newDomain);
        $newDb          = $mongoClient->$newDomain;

        foreach ($collections as $collectionName) {
            $newCollection  = $newDb->$collectionName;
            $collection     = $oldDb->$collectionName;
            $results        = $collection->find();

            $toInsert       = [];

            foreach ($results as $result) {
                array_push($toInsert, $result);

                if (count($toInsert) == 10000) {
                    $newCollection->batchInsert($toInsert);
                    unset($toInsert);
                    $toInsert = [];
                }
            }

            if (!empty($toInsert)) {
                $newCollection->batchInsert($toInsert);
            }

            if ($collectionName == 'guests') {
                foreach ($collection->getIndexInfo() as $index) {
                    if ($index['name'] == '_id_') {
                        continue;
                    }

                    $newCollection->ensureIndex(
                        [
                            key($index['key']) => 1
                        ],
                        [
                            'unique'   => true,
                            'name'     => $index['name'],
                            'sparse'   => true
                        ]
                    );
                }
            }
        }

        $oldstats = $oldDb->command(["dbStats" => 1]);
        $newstats = $newDb->command(["dbStats" => 1]);

        if ($oldstats["dataSize"] === $newstats["dataSize"]) {
            $oldDb->drop();
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function enableWSpotAction(Request $request)
    {
        $clientsIds = $request->get('clientsIds');
        if (gettype($clientsIds) == 'array') {
            foreach ($clientsIds as $clientId) {
                $client = $this->clientService->getClientById($clientId);
                $this->clientService->setClientStatusActive($client);
            }
        } else {
            $client = $this->clientService->getClientById($clientsIds);
            $this->clientService->setClientStatusActive($client);
            return $this->controllerHelper->redirectToRoute('panel_client_list');
        }
        return new JsonResponse(['msg' => 'Domínio ativado com sucesso']);
    }

	/**
	 * @param Request $request
	 * @return JsonResponse
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
    public function disableWSpotAction(Request $request)
    {
        $clientsIds = $request->get('clientsIds');
        if (gettype($clientsIds) == 'array') {
            foreach ($clientsIds as $clientId) {
                $client = $this->clientService->getClientById($clientId);
                $this->clientService->setClientStatusInactive($client);
            }
        } else {
            $client = $this->clientService->getClientById($clientsIds);
            $this->clientService->setClientStatusInactive($client);
            return $this->controllerHelper->redirectToRoute('panel_client_list');
        }

        return new JsonResponse(['msg' => 'Domínio inativado com sucesso']);
    }
}
