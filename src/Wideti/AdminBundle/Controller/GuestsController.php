<?php
namespace Wideti\AdminBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\DeviceEntry;
use Wideti\DomainBundle\Entity\Guests;
use Wideti\DomainBundle\Entity\LegalKinds;
use Wideti\DomainBundle\Exception\EmptyRouterModeException;
use Wideti\DomainBundle\Exception\UniqueFieldException;
use Wideti\DomainBundle\Gateways\Consents\GetConsentGateway;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Helpers\FacebookFieldsHelper;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Helpers\WifiMode;
use Wideti\DomainBundle\Service\Analytics\AnalyticsService;
use Wideti\DomainBundle\Service\AuditLogInternal\AuditLogService;
use Wideti\DomainBundle\Service\AuditLogs\AuditEvent;
use Wideti\DomainBundle\Service\AuditLogs\AuditException;
use Wideti\DomainBundle\Service\AuditLogs\Auditor;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\EventCreate;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\EventUpdate;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\EventView;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kind;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\KindGuest;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\KindUserAdmin;
use Wideti\DomainBundle\Service\Blacklist\BlacklistServiceAware;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsAware;
use Wideti\DomainBundle\Service\EntityLogger\EntityLoggerService;
use Wideti\DomainBundle\Service\Guest\GuestServiceAware;
use Wideti\DomainBundle\Service\GuestDevices\GuestDevices;
use Wideti\DomainBundle\Service\Jaeger\TracerHeaders;
use Wideti\DomainBundle\Service\LegalBaseManager\LegalBaseManagerService;
use Wideti\DomainBundle\Service\Radacct\RadacctServiceAware;
use Wideti\DomainBundle\Service\Radcheck\RadcheckAware;
use Wideti\DomainBundle\Service\Report\ReportService;
use Wideti\DomainBundle\Service\Report\ReportServiceAware;
use Wideti\DomainBundle\Service\Report\ReportType;
use Wideti\DomainBundle\Service\User\CredentialCheckService;
use Wideti\AdminBundle\Form\GuestType;
use Wideti\AdminBundle\Form\Type\Guest\GuestFilterType;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\PaginatorAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class GuestsController
{
    use EntityManagerAware;
    use MongoAware;
    use TwigAware;
    use GuestServiceAware;
    use FlashMessageAware;
    use PaginatorAware;
    use RadcheckAware;
    use BlacklistServiceAware;
    use ReportServiceAware;
    use RadacctServiceAware;
    use CustomFieldsAware;
    use LoggerAware;

    /**
     * @var WifiMode
     */
    protected $wifiMode;

    /**
     * @var AuthorizationChecker
     */
    protected $authorizationChecker;

    /**
     * @var FileUpload
     */
    private $fileUpload;

    private $maxDownload;
    private $bounceValidatorActive;
    private $maxReportLinesPoc;
    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var CacheServiceImp
     */
    private $cacheService;
	/**
	 * @var CredentialCheckService
	 */
	private $credentialCheckService;
    /**
     * @var EntityLoggerService
     */
	private $entityLoggerService;
    /**
     * @var AnalyticsService
     */
    private $analyticsService;
    /**
     * @var GuestDevices
     */
    private $guestDevices;

    /**
     * @var Auditor
     */
    private $auditor;

    /**
     * @var GetConsentGateway
     */
    private $getConsentGateway;

    /**
     * @var LegalBaseManagerService
     */
    private $legalBaseManager;

    /**
     * @var auditLogService
     */
    private $auditLogService;

    /**
     * GuestsController constructor.
     * @param ConfigurationService $configurationService
     * @param FileUpload $fileUpload
     * @param $maxDownload
     * @param $bounceValidatorActive
     * @param $maxReportLinesPoc
     * @param AdminControllerHelper $controllerHelper
     * @param CacheServiceImp $cacheService
     * @param CredentialCheckService $credentialCheckService
     * @param EntityLoggerService $entityLoggerService
     * @param AnalyticsService $analyticsService
     * @param GuestDevices $guestDevices
     * @param Auditor $auditor
     * @param GetConsentGateway $getConsentGateway
     */
    public function __construct(
		ConfigurationService $configurationService,
		FileUpload $fileUpload,
		$maxDownload,
		$bounceValidatorActive,
		$maxReportLinesPoc,
		AdminControllerHelper $controllerHelper,
		CacheServiceImp $cacheService,
		CredentialCheckService $credentialCheckService,
		EntityLoggerService $entityLoggerService,
		AnalyticsService $analyticsService,
		GuestDevices $guestDevices,
		Auditor $auditor,
		GetConsentGateway $getConsentGateway,
        LegalBaseManagerService $legalBaseManagerService,
        AuditLogService $auditLogService
    ) {
        $this->fileUpload               = $fileUpload;
        $this->maxDownload              = $maxDownload;
        $this->bounceValidatorActive    = $bounceValidatorActive;
        $this->maxReportLinesPoc        = $maxReportLinesPoc;
        $this->controllerHelper         = $controllerHelper;
        $this->configurationService     = $configurationService;
        $this->cacheService             = $cacheService;
	    $this->credentialCheckService   = $credentialCheckService;
	    $this->entityLoggerService      = $entityLoggerService;
        $this->analyticsService         = $analyticsService;
        $this->guestDevices             = $guestDevices;
        $this->auditor                  = $auditor;
        $this->getConsentGateway        = $getConsentGateway;
        $this->legalBaseManager         = $legalBaseManagerService;
        $this->auditLogService          = $auditLogService;
    }

    /**
     * @param $page
     * @param Request $request
     * @return Response
     * @throws AuditException
     */
	public function indexAction($page, Request $request)
    {
        if (
            $this->authorizationChecker->isGranted('ROLE_USER_LIMITED') ||
            $this->authorizationChecker->isGranted('ROLE_MARKETING_LIMITED')
        ) {
            throw new AccessDeniedException(('Unauthorized access!'));
        }
        $client     = $this->getLoggedClient();
        $user       = $this->controllerHelper->getUser();
        $btnCancel  = false;
        $guestFilter  = $this->controllerHelper->createForm(
            GuestFilterType::class,
            null,
            [ 'action' => $this->controllerHelper->generateUrl('admin_visitantes') ]
        );

        $guestFilter->handleRequest($request);

        $filters = [
            'maxReportLinesPoc' => ($client->getStatus() == Client::STATUS_POC) ? $this->maxReportLinesPoc : null,
            'filters'           => $guestFilter->getData()
        ];

        if (isset($guestFilter->getData()['dateFrom'])) {
            $filters['filters']['dateFrom'] = date_format($guestFilter->getData()['dateFrom'], 'd/m/Y');
        }

        if (isset($guestFilter->getData()['dateTo'])) {
            $filters['filters']['dateTo'] = date_format($guestFilter->getData()['dateTo'], 'd/m/Y');
        }

        if ($request->get('period')) {
            $filters['filters'] = [
                'dateFrom' => date('d/m/Y', strtotime($request->get('period'))),
                'dateTo' => date('d/m/Y', strtotime($request->get('period')))
            ];
        }

        $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($client);

        if ($activeLegalBase->getLegalKind()->getKey() == LegalKinds::TERMO_CONSENTIMENTO) {
            $filters["filters"]["hasConsentRevoke"] = true;
        }

        $guests = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->searchQuery($filters, null, true);

        if ($client->getStatus() == Client::STATUS_POC && $this->maxReportLinesPoc) {
            $guests = $guests->toArray();
        }

        $pagination     = $this->paginator->paginate($guests, $page, 10);
        $loginField     = $this->customFieldsService->getLoginField()[0];
        $phoneField     = ($this->mongo->getRepository('DomainBundle:CustomFields\Field')->hasField('phone') ?:
            $this->mongo->getRepository('DomainBundle:CustomFields\Field')->hasField('mobile'));

        $defaultGroup   = $this->configurationService->getByIdentifierOrDefault(null, $client);

        /**
         * @var $g Guest
         * Envia os dados para auditoria, uma evento por visitante listado, somente os da página
         */
        foreach ($pagination as $g) {
            $event = $this->auditor
                ->newEvent()
                ->withClient($client->getId())
                ->withSource(Kinds::userAdmin(), $user->getId())
                ->onTarget(Kinds::guest(), $g->getMysql())
                ->withType(Events::view())
                ->addDescription(AuditEvent::PT_BR, 'Visualizou o visitante na listagem de visitantes')
                ->addDescription(AuditEvent::EN_US, 'View guest at guests list')
                ->addDescription(AuditEvent::ES_ES, 'Ha visto al visitante en la lista de visitantes');
            $this->auditor->push($event);
        }

        $traceHeader = TracerHeaders::from($request);
        $consent = $this->getConsentGateway->get($client, 'pt_BR', $traceHeader);

        return $this->render(
            'AdminBundle:Guests:index.html.twig',
            [
                'client'            => $client,
                'maxReportLines'    => $this->maxReportLinesPoc,
                'loginField'        => $loginField,
                'entities'          => $guests,
                'pagination'        => $pagination,
                'form'              => $guestFilter->createView(),
                'count_entities'    => count($guests),
                'maxDownload'       => $this->maxDownload,
                'btnCancel'         => $btnCancel,
                'phoneField'        => $phoneField,
                'reportType'        => ReportType::GUEST,
                'config'            => $defaultGroup,
                'showName'          => $this->mongo->getRepository('DomainBundle:CustomFields\Field')->hasField('name'),
                'consent'           => $consent
            ]
        );
    }

    /**
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request)
    {
        $nas    = $this->session->get(Nas::NAS_SESSION_KEY);
        $client = $this->getLoggedClient();
        $user = $this->controllerHelper->getUser();

        $guest  = new Guest();
        $form   = $this->controllerHelper->createForm(
            GuestType::class,
            $guest,
            [
                'client'         => $this->getLoggedClient()->getId(),
                'authorizeEmail' => (bool) $this->configurationService->get($nas, $client, 'authorize_email')
            ]
        );

        $form->handleRequest($request);

        if ($form->isValid()) {
            $emailValidate  = $form->get('emailValidate')->getData();

            try {
                $this->guestService->createByAdmin($guest, $emailValidate);

                // Dispara evento de auditoria na criacao de visitante
                $event = $this->auditor
                    ->newEvent()
                    ->withClient($client->getId())
                    ->withSource(Kinds::userAdmin(), $user->getId())
                    ->onTarget(Kinds::guest(), $guest->getMysql())
                    ->withType(Events::create())
                    ->addDescription(AuditEvent::PT_BR, 'Criou visitante')
                    ->addDescription(AuditEvent::EN_US, 'Guest created')
                    ->addDescription(AuditEvent::ES_ES, 'Visitante creado')
                    ->addContext("data", json_encode($guest));
                $this->auditor->push($event);

                $this->setCreatedFlashMessage();
                $this->analyticsService->handler($request, true);
                return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('admin_visitantes'));
            } catch (UniqueFieldException $e) {
                $field = $this->customFieldsService->getFieldByNameType($e->getMessage());
                $form->get('properties')[$field->getIdentifier()]
                    ->addError(new FormError($field->getNames()['pt_br'] . ' já existe na base de dados'));
            } catch (\Exception $e) {
                return $this->getServerSideErrorResponse($form, $guest, $e->getMessage());
            }
        }

        return $this->render(
            'AdminBundle:Guests:new.html.twig',
            [
                'entity'            => $guest,
                'form'              => $form->createView(),
                'error'             => '',
                'bounceValidator'   => (int) $this->bounceValidatorActive
            ]
        );
    }

    /**
     * @ParamConverter(
     *      "guest",
     *      class="DomainBundle:Guests",
     *      converter="client",
     *      options={
     *          "message"="Visitante não encontrado"
     *      }
     * )
     * @param Guest $guest
     * @param int $page
     * @return Response
     * @throws AuditException
     */
    public function showAction(Guest $guest, $page = 1)
    {
        $downloadUpload     = $this->radacctService->getTotalDownloadUploadByGuest($guest);
        $accountings        = $this->radacctService->getClosedAccountingsByGuest($guest);
        $firstAccess        = $guest->getCreated();
        $lastAccess         = $guest->getLastAccess();
        $averageTime        = $this->radacctService->getAverageTimeAccessByGuest($guest);
        $pagination         = $this->paginator->paginate($accountings, $page, 10);
        $facebookPicture    = null;
        $customFields       = null;
        $client             = $this->getLoggedClient();
        $blockedDevices     = $this->blacklistService->getGuestBlockedDevices($guest, $client);

        $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($client);

        if ($activeLegalBase->getLegalKind()->getKey() == LegalKinds::TERMO_CONSENTIMENTO && $guest->isHasConsentRevoke()) {
            return $this->render(
                'TwigBundle:Exception:error403.html.twig',
                ['status_code' => 403]
            );
        }

        foreach ($guest->getProperties() as $key => $value) {
            $field = $this->mongo->getRepository('DomainBundle:CustomFields\Field')->findOneBy([
                'identifier' => $key
            ]);

            if ($field && $field->getType() == 'date') {
                $dt    = new \DateTime(date('Y-m-d H:i:s', $value->sec));
                $value = $dt->format("d/m/Y");
            }

            $fieldName = ($field) ? $field->getNames()['pt_br'] : 'Campo adicional';

            $customFields[$fieldName] = $value;
        }

        $facebookFieldsHelper = new FacebookFieldsHelper();
        $facebookFields = $facebookFieldsHelper->converter($guest->getFacebookFields());

        if ($facebookFields) {
            $facebookId      = $guest->getFacebookFields()['id'];
            $facebookPicture = 'https://graph.facebook.com/'.$facebookId.'/picture?width=100&height=100';
        }

        $guestMysql = $this->em->getRepository("DomainBundle:Guests")->findOneBy(['id' => $guest->getMysql()]);
        $devices = $this->guestDevices->getDevices($guestMysql);

        // Dispara evento de auditoria
        $user = $this->controllerHelper->getUser();
        $event = $this->auditor
            ->newEvent()
            ->withClient($client->getId())
            ->withSource(Kinds::userAdmin(), $user->getId())
            ->onTarget(Kinds::guest(), $guest->getMysql())
            ->withType(Events::view())
            ->addDescription(AuditEvent::PT_BR, 'Visualizou o visitante no detalhe de visitantes')
            ->addDescription(AuditEvent::EN_US, 'View guest at guests detail')
            ->addDescription(AuditEvent::ES_ES, 'Ha visto al visitante en la detalle de visitantes');
        $this->auditor->push($event);

        return $this->render(
            'AdminBundle:Guests:show.html.twig',
            [
                'entity'              => $guest,
                'devices'             => $devices,
                'facebookPicture'     => $facebookPicture,
                'customFields'        => $customFields,
                'facebookFields'      => $facebookFields,
                'download_upload'     => $downloadUpload,
                'first_access'        => $firstAccess,
                'last_access'         => $lastAccess,
                'average_time_access' => $averageTime,
                'pagination'          => $pagination,
                'blockedDevices'      => $blockedDevices
            ]
        );
    }

    /**
     * @param Request $request
     * @param Guest $guest
     * @return RedirectResponse|Response
     * @throws AuditException
     */
    public function editAction(Request $request, Guest $guest)
    {

        $nas    = $this->session->get(Nas::NAS_SESSION_KEY);
        $client = $this->getLoggedClient();
        $user = $this->controllerHelper->getUser();
        $auditOldValue = json_encode($guest);

        if ($this->authorizationChecker->isGranted('ROLE_MARKETING')) {
            throw new AccessDeniedException(('Unauthorized access!'));
        }


        $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($client);

        if ($activeLegalBase->getLegalKind()->getKey() == LegalKinds::TERMO_CONSENTIMENTO && $guest->isHasConsentRevoke()) {
            return $this->render(
                'TwigBundle:Exception:error403.html.twig',
                ['status_code' => 403]
            );
        }
        $actionUrl = $this->controllerHelper->generateUrl('admin_visitantes_edit', ['id' => $guest->getId()]);

        $this->guestService->formatMultipleChoiceToArray($guest);
        $form = $this->controllerHelper->createForm(
            GuestType::class,
            $guest,
            [
                'client'                 => $this->getLoggedClient()->getId(),
                'action'                 => $actionUrl,
                'authorizeEmail'         => (bool) $this->configurationService->get($nas, $client, 'authorize_email'),
                'registrationMacAddress' => $guest->getRegistrationMacAddress()
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (array_key_exists('email', $form->getData()->getProperties())) {
                $emailValidate = $this->guestService->validate('email', $form->getData()->getProperties()['email']);

                if ($emailValidate === false) {
                    $form->get('properties')['email']
                        ->addError(
                            new FormError('Domínio de e-mail não permitido')
                        );
                }
            }
        }

        if ($form->isValid()) {
            if ($form->getData()->getStatus() === Guests::STATUS_ACTIVE) {
                $this->radcheckService->removeExpirationTimeByGuest($this->getLoggedClient(), $guest);
            }

            try {
                $auditUpdatedValue = json_encode($guest);
                $this->guestService->update($guest);
                $this->setUpdatedFlashMessage();

                // Envia auditoria na atualizacao do formulario de edicao
                $event = $this->auditor
                    ->newEvent()
                    ->withClient($client->getId())
                    ->withSource(Kinds::userAdmin(), $user->getId())
                    ->onTarget(Kinds::guest(), $guest->getMysql())
                    ->withType(Events::update())
                    ->addDescription(AuditEvent::PT_BR, 'Atualizou visitante')
                    ->addDescription(AuditEvent::EN_US, 'Update guest')
                    ->addDescription(AuditEvent::ES_ES, 'Visitante actualizado')
                    ->addContext("before", $auditOldValue)
                    ->addContext("new", $auditUpdatedValue);
                $this->auditor->push($event);

                return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('admin_visitantes'));
            } catch (UniqueFieldException $e) {
                $field = $this->customFieldsService->getFieldByNameType($e->getMessage());
                $form->get('properties')[$field->getIdentifier()]
                    ->addError(new FormError($field->getNames()['pt_br'] . ' já existe na base de dados'));
            } catch (\Exception $e) {
                return $this->getServerSideErrorResponse($form, $guest, $e->getMessage());
            }

        }

        // Envia auditoria na visualizacao do formulario de edicao
        $event = $this->auditor
            ->newEvent()
            ->withClient($client->getId())
            ->withSource(Kinds::userAdmin(), $user->getId())
            ->onTarget(Kinds::guest(), $guest->getMysql())
            ->withType(Events::view())
            ->addDescription(AuditEvent::PT_BR, 'Visualizou o visitante no formulário de edição de visitantes')
            ->addDescription(AuditEvent::EN_US, 'View guest at edit form')
            ->addDescription(AuditEvent::ES_ES, 'Ha visto al visitante en la formulario de edición')
            ->addContext("data", json_encode($guest));
        $this->auditor->push($event);

        return $this->render(
            'AdminBundle:Guests:edit.html.twig',
            [
                'entity'            => $guest,
                'edit_form'         => $form->createView(),
                'bounceValidator'   => (int) $this->bounceValidatorActive
            ]
        );
    }

    public function detailAction($accessId)
    {
        try {
            return $this->render(
                'AdminBundle:Guests:accessDetail.html.twig',
                $this->getAccountingByAcctuniqueid($accessId)
            );
        } catch (EmptyRouterModeException $e) {
            $this->logger->addCritical("Erro ao detalhar ultimos acessos:
             {$e->getMessage()} com a stack - {$e->getTraceAsString()}");

            return $this->render('AdminBundle:Guests:error.html.twig',
                [
                    'message' => 'Não foi possivel encontrar informaçoẽs do seu ponto de acesso!'
                ]
            );
        }
    }

    public function printAction(Guest $guest)
    {
        $guestAccessInfo = [];
        $firstAccess     = $guest->getCreated();
        $lastAccess      = $guest->getLastAccess();

        $guestAccessInfo['first'] = $firstAccess;
        $guestAccessInfo['last']  = $lastAccess;

        $accountings    = $this->radacctService->getClosedAccountingsByGuest($guest);
        $customFields   = $this->getGuestCustomFields($guest);

        return $this->render(
            'AdminBundle:Guests:print.html.twig',
            [
                'entity'          => $guest,
                'access_list'     => $accountings,
                'guestAccessInfo' => $guestAccessInfo,
                'customFields'    => $customFields,
                'pdf'             => false
            ]
        );
    }

    public function printDetailAction($accessId)
    {
        return $this->render(
            'AdminBundle:Guests:printDetail.html.twig',
            $this->getAccountingByAcctuniqueid($accessId)
        );
    }

    public function getAccountingByAcctuniqueid($accessId)
    {
        $client         = $this->getLoggedClient();
        $access         = $this->radacctService->getAccessByUniqueId($accessId, $this->getLoggedClient()->getId());
        $accessPoint    = $access['calledstationid'];

        $vendorRouterMode = $this->wifiMode
            ->getDownloadUploadBasedOnVendorAndClient($accessPoint, $this->getLoggedClient());

	    $download = ($vendorRouterMode == WifiMode::ROUTER_MODE)
		    ? $access['acctoutputoctets']
		    : $access['acctinputoctets'];

	    $upload = ($vendorRouterMode == WifiMode::ROUTER_MODE)
		    ? $access['acctinputoctets']
		    : $access['acctoutputoctets'];

	    $guest  = $this->mongo
		    ->getRepository('DomainBundle:Guest\Guest')
		    ->findOneBy([
			    'mysql' => (int) $access['username']
		    ])
	    ;

	    $fingerprint = [];

        /**
         * @var DeviceEntry $device
         */
	    $device = $this->guestDevices->getLastAccessWithSpecificDevice($client, $access['callingstationid']);

        if ($device) {
            $fingerprint['platform'] = $device->getDevice()->getPlatform();
            $fingerprint['os']       = $device->getDevice()->getOs();
        }

        $ipHistoric = $this->radacctService->getAcctIpHistoric($accessId);
        $ipHistoricList = [];

        foreach ($ipHistoric as $data) {
            $item = $data['_source'];
            array_push(
                $ipHistoricList,
                [
                    'ip'        => $item['ip'],
                    'dateTime'  => date('d/m/Y H:i:s', strtotime($item['datetime']))
                ]
            );
        }

        $customFields = $this->getGuestCustomFields($guest);

        return [
            'status'          => 'closed',
            'guest'           => $guest,
            'customFields'    => $customFields,
            'access'          => $access,
            'fingerprint'     => $fingerprint,
            'access_point'    => $accessPoint,
            'download'        => $download,
            'upload'          => $upload,
            'ipHistoric'      => $ipHistoricList,
            'pdf'             => false
        ];
    }

    /**
     * @param Request $request
     * @param Guest $entity
     * @return JsonResponse
     * @throws AuditException
     */
    public function resetPasswordAction(Request $request, Guest $entity)
    {
        $client = $this->getLoggedClient();
        $user = $this->controllerHelper->getUser();

        if ($this->authorizationChecker->isGranted('ROLE_MARKETING')) {
            throw new AccessDeniedException(('Unauthorised access!'));
        }

        $nas = $this->session->get(Nas::NAS_SESSION_KEY);
        $this->guestService->changePassword($nas, $entity, $request->get('password'), true);

        if ($request->get('sms') == 'true') {
            $this->guestService->sendPasswordSms($entity, $nas);
        }

        $event = $this->auditor
            ->newEvent()
            ->withClient($client->getId())
            ->withSource(Kinds::userAdmin(), $user->getId())
            ->onTarget(Kinds::guest(), $entity->getMysql())
            ->withType(Events::update())
            ->addDescription(AuditEvent::PT_BR, 'Password atualizado')
            ->addDescription(AuditEvent::EN_US, 'Password updated')
            ->addDescription(AuditEvent::ES_ES, 'Contraseña actualiza');
        $this->auditor->push($event);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['msg' => 'Nova senha gerada e enviada para o visitante.']);
        } else {
            return $entity->getPassword();
        }
    }

    public function resendConfirmationUrlAction(Request $request, Guest $guest)
    {
        $code   = $this->em
            ->getRepository('DomainBundle:GuestAuthCode')
            ->findOneBy([
                'guest' => $guest->getMysql()
            ]);

        if ($code !== null) {
            $resendConfirmationUrl = $this->guestService->resendConfirmationUrl($guest);

            if ($resendConfirmationUrl) {
                return new JsonResponse([
                    'msg' => 'Um e-mail foi enviado para o visitante com a URL de confirmação de cadastro.'
                ]);
            }
        }

        return new JsonResponse([
            'msg' => 'Falha ao reenviar a URL de confirmação de cadastro.'
        ]);
    }

    public function confirmationByAdminAction(Guest $guest)
    {

        $client = $this->getLoggedClient();
        $user = $this->controllerHelper->getUser();

        try {
            $guest->setStatus(Guest::STATUS_ACTIVE);
            $this->mongo->persist($guest);
            $this->mongo->flush();

            $this->radcheckService->removeExpirationTimeByGuest($this->getLoggedClient(), $guest);

            // Dispara evento de auditoria na confirmacao do guest pelo painel
            $event = $this->auditor
                ->newEvent()
                ->withClient($client->getId())
                ->withSource(Kinds::userAdmin(), $user->getId())
                ->onTarget(Kinds::guest(), $guest->getMysql())
                ->withType(Events::confirm())
                ->addDescription(AuditEvent::PT_BR, 'Confirmou o visitante')
                ->addDescription(AuditEvent::EN_US, 'Confirmed the guest')
                ->addDescription(AuditEvent::ES_ES, 'Confirmed visitor');
            $this->auditor->push($event);

            return new JsonResponse([
                'status' => 'success',
                'msg'    => 'Cadastro de visitante confirmado com sucesso'
            ]);
        } catch (\Exception $ex) {
            return new JsonResponse([
                'status' => 'error',
                'msg'    => 'Erro ao confirmar manualmente o cadastro do visitante'
            ]);
        }
    }

    /**
     * @param Request $request
     * @return string|RedirectResponse|Response
     * @throws AuditException
     */
    public function exportGuestsAction(Request $request)
    {
        $client = $this->getLoggedClient();
        $user = $this->controllerHelper->getUser();
        $traceHeaders = TracerHeaders::from($request);
        $consent = $this->getConsentGateway->get($client, 'pt_BR', $traceHeaders);

        // Audit
        $event = $this->auditor->newEvent();
        $event
            ->withClient($client->getId())
            ->withSource(Kinds::userAdmin(), $user->getId())
            ->withType(Events::accept())
            ->onTarget(Kinds::guestListReport(), "monolith_guest_list_export")
            ->addDescription(AuditEvent::PT_BR, "Usuário aceitou os consentimentos ao exportar listagem de visitantes")
            ->addDescription(AuditEvent::EN_US, "User accepted the consents on exporting guests list")
            ->addDescription(AuditEvent::ES_ES, "El usuario aceptó los consentimientos para exportar la lista de guests");
        if ($consent->getHasError()) {
            $event->addContext('consent', 'Error on retrieve consent information on monolith');
        } else {
            $event->addContext('consent_id', $consent->getId());
            $event->addContext('consent_version', $consent->getVersion());
        }
        $this->auditor->push($event);

        $params         = [];
        $requestParams  = null;

        $charset = null;
        if ($request->isMethod('GET')) {
            $requestParams = $request->query->all();
            $filterParams  = [];

            $charset = $requestParams['charset'];

            if (array_key_exists('filters', $requestParams)) {
                parse_str($requestParams['filters'], $filterParams);

                $filterParams['visitantes']['dateFrom'] = $requestParams['startDate'];
                $filterParams['visitantes']['dateTo']   = $requestParams['endDate'];
            }

            if ($filterParams && array_key_exists('visitantes', $filterParams)) {
                if (!strpos($requestParams['filters'], 'period') && reset($filterParams) != '' && array_key_exists('filtro', reset($filterParams))) {
                    $params['filtro'] = reset($filterParams)['filtro'];
                }

                foreach ($filterParams['visitantes'] as $key => $value) {
                    $params[$key] = $value;
                }
            }
        }

        $client = $this->session->get('wspotClient');

        $reportResponse = $this
            ->reportService
            ->processReport(ReportType::GUEST, $params, $client, $requestParams['fileFormat'], $charset);

        $url = $this->controllerHelper->generateUrl('admin_visitantes');

        if ($reportResponse == 'batch') {
            $this->session->getFlashBag()->add('export', true);
            $urlParams = ReportService::generateUrlParams($params, "visitantes");
            $url = $this->controllerHelper->generateUrl('admin_visitantes',$urlParams);
            return new RedirectResponse($url);
        } elseif ($reportResponse == 'empty') {
            $this->session->getFlashBag()->add(
                'notice',
                'Não existem informações à serem exportadas no período selecionado.'
            );
            return new RedirectResponse($url);
        }

        $this->auditLogService->createAuditLog(
            'export-guest',
            Events::create()->getValue(),
            null,
            true
        );

        return $reportResponse;
    }

    public function setWifiMode(WifiMode $mode)
    {
        $this->wifiMode = $mode;
    }

    public function setAuthorizationChecker(AuthorizationChecker $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    private function getGuestCustomFields($guest)
    {
        $customFields = [];

        foreach ($guest->getProperties() as $key => $value) {
            $fieldName = '';

            $field = $this->mongo->getRepository('DomainBundle:CustomFields\Field')->findOneBy([
                'identifier' => $key
            ]);

            if (!$field) {
                continue;
            }

            if ($field->getType() == 'date') {
                $dt    = new \DateTime(date('Y-m-d H:i:s', $value->sec));
                $value = $dt->format("d/m/Y");
            }

            $fieldName = ($field) ? $field->getNames()['pt_br'] : $fieldName;

            $customFields[$fieldName] = $value;
        }

        return $customFields;
    }

    private function getServerSideErrorResponse(FormInterface $form, $guest, $message)
    {
        if ($message == 'invalid_document') {
            $form->get('properties')['document']->addError(new FormError('Digite um CPF válido'));
        } elseif ($message == 'invalid_phone') {
            if (array_key_exists('phone', $guest->getProperties())) {
                $form->get('properties')['phone']->addError(new FormError('Telefone/Celular deve ter DDD + telefone'));
            } else {
                $form->get('properties')['mobile']->addError(new FormError('Celular deve ter DDD + telefone'));
            }
        } else {
            $form->addError(new FormError('Ocorreu um erro ao efetuar o seu cadastro.'));
        }

        return $this->render(
            'AdminBundle:Guests:new.html.twig',
            [
                'entity'            => $guest,
                'form'              => $form->createView(),
                'error'             => '',
                'bounceValidator'   => (int) $this->bounceValidatorActive
            ]
        );
    }

    public function campaignsViewedByGuests(Request $request)
    {
        $client = $this->getLoggedClient();

        try {
            $identificationField = $this->mongo
                ->getRepository('DomainBundle:CustomFields\Field')
                ->findOneBy([ "isLogin" => true ]);

            /**
             * @var DeviceEntry $lastAccessWithSpecificDevice
             */
            $lastAccessWithSpecificDevice = $this->guestDevices
                ->getLastAccessWithSpecificDevice($client, $request->get('guestMacAddress'));

            $guestMySqlId = $lastAccessWithSpecificDevice->getGuest()->getId();

            $guest = $this->mongo
                ->getRepository('DomainBundle:Guest\Guest')
                ->findOneBy([
                    'mysql' => $guestMySqlId
                ]);

            $campaignViewsData = $this->em
                ->getRepository('DomainBundle:CampaignViews')
                ->campaignViewedByGuest($request->get('guestMacAddress'));

        } catch (\Exception $exception) {
            throw new \Exception($exception->getTrace());
        }

        $campaignsViewed = [];

        foreach ($campaignViewsData as $data) {
            $campaignsViewed[$data['type']][] = [
                'name'        => $data['name'],
                'viewDate'    => $data['view_time'],
                'accessPoint' => $data['access_point'],
                'quantity'    => $data['quantity']
            ];
        }

        return $this->render('AdminBundle:Report:campaignsViewedByGuests.html.twig',
            [
                'guest'        => $guest ?
                                     $guest->getProperties()[$identificationField->getIdentifier()] :
                                     "Mac Address: {$request->get('guestMacAddress')}",
                'macAdress'    => $request->get('guestMacAddress'),
                'campaignData' => $campaignsViewed,
                'goBack'       => [
                    'campaignType' => $request->get('campaignType'),
                    'campaign'     => $request->get('searchCampaign')
                ]
            ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function deleteAction(Request $request)
    {
        $guestId  = $request->get('id');
        $guest = $this->guestService->findById($guestId);
        $response = new Response();

        $logged = $this->credentialCheckService->check($request);

        $response->headers->set('Content-Type', 'text/plain');

        if ($guest && $logged) {
            $this->guestService->deleteGuestInAllBases($this->getLoggedClient(), $guest);
            $response->setStatusCode(Response::HTTP_ACCEPTED);
            $response->setContent("Visitante excluído com sucesso!");
            $this->entityLoggerService->log([
                'module'    => 'Guest',
                'action'    => 'delete',
                'changeset' => [
                    'id' => $this->getLoggedClient()->getId(),
                    'changes' => [
                        'value' => [
                            $guest,
                        ]
                    ],
                    'field' => 'guest'
                ]

            ]);

        } elseif ($logged == false) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setContent("Usuário ou senha inválido");
        } else {
            $response->setContent("Visitante não encontrado");
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
        }

        return $response;
    }
}
