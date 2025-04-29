<?php

namespace Wideti\AdminBundle\Controller;

use Facebook\Facebook;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Wideti\DomainBundle\Entity\Contract;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Service\AccessPointsGroups\AccessPointsGroupsService;
use Wideti\DomainBundle\Service\AccessPointsGroups\GetAccessPointsGroupsService;
use Wideti\DomainBundle\Service\Analytics\AnalyticsService;
use Wideti\DomainBundle\Service\Client\ClientService;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Contract\ContractServiceAware;
use Wideti\DomainBundle\Service\Contract\GetContractByTypeService;
use Wideti\DomainBundle\Service\Contract\GetUserThatConfirmedSmsCostContractService;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsService;
use Wideti\DomainBundle\Service\CustomFields\Field;
use Wideti\DomainBundle\Service\GuestNotification\Senders\SmsService;
use Wideti\AdminBundle\Form\SetupType;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Zend\Validator\StringLength;

/**
 * Class ConfigurationsController
 *
 * @package Wideti\AdminBundle\Controller
 */
class ConfigurationsController
{
    use EntityManagerAware;
    use MongoAware;
    use TwigAware;
    use ContractServiceAware;
    use SecurityAware;
    use FlashMessageAware;

    /**
     * @var string
     */
    protected $appSecret;
    /**
     * @var string
     */
    protected $appId;
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;
    /**
     * @var SmsService
     */
    private $smsService;
    /**
     * @var integer
     */
    private $maxNumberSmsSendingPoc;
    /**
     * @var AccessPointsGroupsService
     */
    private $accessPointsGroupService;
    /**
     * @var GetAccessPointsGroupsService
     */
    private $getAccessPointsGroupService;
    /**
     * @var GetContractByTypeService
     */
    private $getContractByTypeService;
    /**
     * @var GetUserThatConfirmedSmsCostContractService
     */
    private $getUserThatConfirmedSmsCostContractService;
    /**
     * @var ClientService
     */
    private $clientService;
    /**
     * @var AnalyticsService
     */
    private $analyticsService;
    /**
     * @var AuthorizationChecker
     */
    protected $authorizationChecker;

    /**
     * ConfigurationsController constructor.
     *
     * @param ConfigurationService $configurationService
     * @param AdminControllerHelper $controllerHelper
     * @param SmsService $smsService
     * @param $maxNumberSmsSendingPoc
     * @param AccessPointsGroupsService $accessPointsGroupService
     * @param GetAccessPointsGroupsService $getAccessPointsGroupsService
     * @param GetContractByTypeService $getContractByTypeService
     * @param GetUserThatConfirmedSmsCostContractService $getUserThatConfirmedSmsCostContractService
     * @param ClientService $clientService
     * @param AnalyticsService $analyticsService
    * @param CustomFieldsService $customFieldsService
     */
    public function __construct
    (
        ConfigurationService $configurationService,
        AdminControllerHelper $controllerHelper,
        SmsService $smsService,
        $maxNumberSmsSendingPoc,
        AccessPointsGroupsService $accessPointsGroupService,
        GetAccessPointsGroupsService $getAccessPointsGroupsService,
        GetContractByTypeService $getContractByTypeService,
        GetUserThatConfirmedSmsCostContractService $getUserThatConfirmedSmsCostContractService,
        ClientService $clientService,
        AnalyticsService $analyticsService,
        CustomFieldsService $customFieldsService

    ) {
        $this->configurationService = $configurationService;
        $this->controllerHelper = $controllerHelper;
        $this->smsService = $smsService;
        $this->maxNumberSmsSendingPoc = $maxNumberSmsSendingPoc;
        $this->accessPointsGroupService = $accessPointsGroupService;
        $this->getAccessPointsGroupService = $getAccessPointsGroupsService;
        $this->getContractByTypeService = $getContractByTypeService;
        $this->getUserThatConfirmedSmsCostContractService = $getUserThatConfirmedSmsCostContractService;
        $this->clientService = $clientService;
        $this->analyticsService = $analyticsService;
        $this->customFieldsService = $customFieldsService;
    }

    /**
     * @param Request $request
     * @param         $groupId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $groupId)
    {
        if ($this->authorizationChecker->isGranted('ROLE_USER_BASIC'))
        {
            throw new AccessDeniedException(('Unauthorized access!'));
        }
        $client = $this->getLoggedClient();
        $group = $this->getAccessPointsGroupService->get($groupId, $client);

        if (!$group) {
            return $this->render("AdminBundle:Admin:notFound.html.twig", [
                "message" => "Configuração não encontrada."
            ]);
        }

        if ($group->getParentConfigurations()) {
            return $this->render("AdminBundle:Admin:notFound.html.twig", [
                "message" => "Edição permitida apenas para grupo que não herda configurações."
            ]);
        }

        try {
            $groupIdParent = $this->accessPointsGroupService
                ->getParentConfigurationGroupId($group);
        } catch (\Exception $e) {
            return $this->render("AdminBundle:Admin:notFound.html.twig", [
                "message" => $e->getMessage()
            ]);
        }

        $options['attr']['groupId'] = $groupIdParent;
        $form = $this->controllerHelper->createForm(SetupType::class, null, $options);
        $form->handleRequest($request);

        if (!is_null($form->getData()['from_email'])) {
            $fromEmail = $form->get('from_email')->getData()->getValue();
            if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
                $form->addError(new FormError('Informe um email no formato válido. Ex: teste@seudominio.com.br'));
            }
        }

        $configuration = $this->configurationService->getByGroupId((int)$groupIdParent);
        $configMap = $this->configurationService->getConfigAsMap($configuration, $this->getLoggedClient()->getDomain());

        $partnerNameSize = $this->getPartNameSize($form);

        if ($form->isValid()) {
            $groupParent = $this->getAccessPointsGroupService->get($groupIdParent, $client);
            $this->configurationService->saveConfiguration($form->getData(), $groupParent);

            if ($form->has('age_restriction') && $form->get('age_restriction')->getData()->getValue() === true) {
                $this->customFieldsService->saveAgeRestrictionField((int) $groupIdParent);
            } else {
                $this->customFieldsService->removeGroupIdFromField('age_restriction', $groupIdParent);
            }
            $this->setFlashMessage('notice', 'Configurações definidas com sucesso');
            $group_email_sender = $form->get('from_email')->getData()->getValue();
            $this->clientService->verifyEmailAddress($group_email_sender);

            $this->analyticsService->handler($request, true);

            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl(
                    'configurations_edit', [
                        'groupId' => $groupId
                    ]
                )
            );
        }

        $smsCostContract = $this->getContractByTypeService->get(Contract::SMS_COST);
        $userConfirmSmsCostContract = $this->getUserThatConfirmedSmsCostContractService->get($this->getUser());
        $message = null;

        if ($smsCostContract && !$userConfirmSmsCostContract) {
            $text    = $smsCostContract->getText();
            $client  = $this->clientService->getClientById($this->getLoggedClient());
            $message = $this->contractService->replaceMessage(
                $text,
                [
                    'client' => $client,
                    'user' => $this->getUser()
                ]
            );
        }

        $tab = $request->request->get('selectedTab');

        return $this->render("AdminBundle:Configurations:edit.html.twig", [
            'group' => $group,
            'config' => $configMap,
            'form' => $form->createView(),
            'user' => $this->getUser(),
            'userContract' => $userConfirmSmsCostContract,
            'contract' => $smsCostContract,
            'smsCostContract' => $message,
            'groups' => $this->configurationService->getGroups($groupIdParent),
            'tab' => $tab,
            'isSmsPocLimited' => $this->smsService->checkLimitSendSms(),
            'maxNumberSmsSendingPoc' => $this->maxNumberSmsSendingPoc,
            'clientStatus' => $group->getClient()->getStatus(),
            'partnerNameSize' => $partnerNameSize,
            'client' => $client
        ]);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function validateFacebookIdAction(Request $request)
    {
        $url = $request->request->get('url');

        if (!$url) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Insira o link de uma página válida do Facebook'
            ]);
        }

        try {
            $fb = new Facebook([
                'app_id' => $this->appId,
                'app_secret' => $this->appSecret,
                'default_graph_version' => 'v2.5'
            ]);

            $arrayUrl = explode('/', $url);

            $request = $fb->get(
                '/' . end($arrayUrl),
                $this->appId . '|' . $this->appSecret
            );

            $response = $request->getDecodedBody();

            return new JsonResponse([
                'success' => true,
                'page_id' => $response['id']
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => "Não foi possível encontrar essa página do Facebook"
            ]);
        }
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function mikrotikScriptGeneratorAction(Request $request)
    {
        $ssid = $request->request->get('ssid');
        $from = $request->request->get('network');
        $domain = $request->request->get('domain');
        $adminAccessPassword = $request->request->get('admin_access_password');
        $to = (int) $from + 15;

        if ($ssid) {
            $url = 'https://mambowifi.com/configuration-scripts/mikrotik/mambo.php\?domain='.urlencode($domain).'&ssid='.urlencode($ssid).'&rede='.urlencode($from).'&password='.urlencode($adminAccessPassword);
            $firstStep ='/system script
add name=update-routerOS owner=admin policy=ftp,reboot,read,write,policy,test,password,sniff,sensitive,romon \
source="tool fetch url=\"'.$url.'\
\" dst-path=/flash/mambowifi.rsc"

';

            $secondStep ='run update-routerOS
            
';

            $thridStep = '..
..
:if ([:len [/file find name=flash/mambowifi.rsc]] > 0) do={/system 
reset-configuration run-after-reset=flash/mambowifi.rsc no-defaults=yes}

y

';
        }

        $script = [
            'first-step' => $firstStep,
            'second-step'=> $secondStep,
            'third-step' => $thridStep
        ];

        return new JsonResponse($script);
    }

    /**
     * @param $appId
     *
     * @return $this
     */
    public function setFacebookAppId($appId)
    {
        $this->appId = $appId;
        return $this;
    }

    /**
     * @param $appSecret
     *
     * @return $this
     */
    public function setFacebookAppSecret($appSecret)
    {
        $this->appSecret = $appSecret;
        return $this;
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function confirmationSMSAllowed(Request $request)
    {
        $return = false;

        $customFields
            = $this->mongo->getRepository('DomainBundle:CustomFields\Field');

        foreach ($customFields->findAll() as $customField) {
            if (($customField->getType() == 'mobile') || ($customField->getType() == 'phone')) {
                $return = true;
                break;
            }
        }

        return new JsonResponse([
            'allowed' => $return
        ]);
    }

    private function getPartNameSize(\Symfony\Component\Form\FormInterface $form)
    {
        $partnerName = $form->get('partner_name')->getData()->getValue();

        $fieldPt = $form->get('content_confirmation_sms_pt')->getData()->getValue();
        $fieldEn = $form->get('content_confirmation_sms_en')->getData()->getValue();
        $fieldEs = $form->get('content_confirmation_sms_es')->getData()->getValue();

        $lengthPt = strpos($fieldPt, 'nome_da_empresa') &&  strlen($partnerName) > 19 ? strlen($partnerName) - 19 : 0;
        $lengthEn = strpos($fieldEn, 'nome_da_empresa') &&  strlen($partnerName) > 19 ? strlen($partnerName) - 19 : 0;
        $lengthEs = strpos($fieldEs, 'nome_da_empresa') &&  strlen($partnerName) > 19 ? strlen($partnerName) - 19 : 0;

        return [
            'pt' => $lengthPt,
            'en' => $lengthEn,
            'es' => $lengthEs
        ];
    }

    public function setAuthorizationChecker(AuthorizationChecker $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }
}