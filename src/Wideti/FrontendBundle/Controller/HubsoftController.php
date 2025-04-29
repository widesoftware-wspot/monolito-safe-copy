<?php
namespace Wideti\FrontendBundle\Controller;

use Exception;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use League\OAuth2\Client\Provider\GenericProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wideti\DomainBundle\Document\Guest\Social;
use Wideti\DomainBundle\Entity\OAuthLogin;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsService;
use Wideti\FrontendBundle\Listener\NasSessionVerify\NasControllerHandler;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Service\Router\RouterServiceAware;
use Wideti\DomainBundle\Service\Template\TemplateAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\DomainBundle\Service\Hubsoft\HubsoftService;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\FrontendBundle\Form\HubsoftType;


class HubsoftController implements NasControllerHandler
{
    use RouterServiceAware;
    use SessionAware;
    use MongoAware;
    use LoggerAware;
    use EntityManagerAware;
    use TemplateAware;
    use TwigAware;

    /**
     * @var FrontendControllerHelper
     */
    private $controllerHelper;
    private $fieldLogin;
    /**
     * @var GenericProvider
     */
    private $provider;

    /**
     * @var CustomFieldsService
     */
    private $customFieldsService;

    /**
     * @var HubsoftService
     */
    private $hubsoftService;

    /**
     * @var CacheServiceImp
     */
    private $cacheService;

    public function __construct(
        FrontendControllerHelper $controllerHelper, 
        CustomFieldsService $customFieldsService,
        HubsoftService $hubsoftService,
        CacheServiceImp $cacheService
        )
    {
        $this->controllerHelper = $controllerHelper;
        $this->customFieldsService = $customFieldsService;
        $this->hubsoftService = $hubsoftService;
        $this->cacheService = $cacheService;
    }

    /**
     * @return mixed
     */
    private function getCustomFields()
    {
        $fields = $this->mongo
            ->getRepository('DomainBundle:CustomFields\Field')
            ->findSignUpFields();

        $fields = $fields->toArray();

        return $fields;
    }

    public function authAction(Request $request)
    {
        $client = $this->getLoggedClient();

        $form = $this->controllerHelper->createForm(
            HubsoftType::class,
            null
        );

        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            $authResponse = $this->hubsoftService->authAction($request->get('wspot_hubsoft_auth'));
            if ($authResponse["status"] == "success") {
                $guestInfo = $authResponse["cliente"];
                $guestData = [
                   'document' => $guestInfo["cpf_cnpj"],
                   'data_nascimento' => $guestInfo["data_nascimento"],
                   'phone' => $guestInfo["telefone_primario"],
                   'email' => $guestInfo["email_principal"],
                   'name' => $guestInfo["nome_razaosocial"]
                ];
                $fields = [];
                try {
                    if (!$this->cacheService->isActive()) {
                        $fields = $this->getCustomFields();
                    } elseif ($this->cacheService->exists(CacheServiceImp::CUSTOM_FIELDS) !== 1) {
                        $fields = $this->getCustomFields();
                        $this->cacheService->set(CacheServiceImp::CUSTOM_FIELDS, $fields, CacheServiceImp::TTL_CUSTOM_FIELDS);
                    } else {
                        $fields = $this->cacheService->get(CacheServiceImp::CUSTOM_FIELDS);
                    }
                } catch (\Exception $e) {
                    $fields = $this->getCustomFields();
                }
                $loginFieldIdentifier = null;
                $loginFieldValue = null;
                $guestFields = [];
                foreach ($fields as $field) {
                    $identifier = $field->getIdentifier();
                    if ($field->getIsLogin() && array_key_exists($identifier, $guestData)) {
                        $loginFieldIdentifier = $identifier;
                        $loginFieldValue = $guestData[$identifier];
                    } elseif (!$field->getIsLogin() && array_key_exists($identifier, $guestData)) {
                        if ($guestData[$identifier]) {
                            $guestFields[$identifier] = $guestData[$identifier];
                        }
                    }
                }

                if (!$loginFieldIdentifier || !$loginFieldValue) {
                    $this->logger->addCritical('Campo de login incompatível com os campos recebidos da hubsoft');
                    return ;
                }
                $guestGroupID = $this->hubsoftService->getAuthenticatedClientGroup($client);
                $locale = $this->session->get('locale') ? $this->session->get('locale') : 'pt_br';
                $this->session->set(
                        'guest',
                        [
                            'data' =>
                                [
                                    'id'        => null,
                                    'locale'    => $locale,
                                    $loginFieldIdentifier => $loginFieldValue,
                                    'field_login' =>  $loginFieldIdentifier
                                ],
                            'social' =>
                                [
                                    'type' => Social::HUBSOFT
                                ],
                            'fields' => $guestFields,
                            'group_id'  => $guestGroupID
                        ]
                    );
                    return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('complete_registration_integrate'));
            } else {
                if (!$authResponse) {
                    return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_hubsoft_auth', [
                        'error' => 'Ocorreu um erro, tente novamente mais tarde'
                   ]));
                } else {
                    return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_hubsoft_auth', [
                        'error' => 'CPF ou senha inválidos'
                   ]));
                }
            }

        }

        $template = $this->templateService->templateSettings($this->session->get('campaignId'));

        $textAuth = $this->hubsoftService->getTitleText($client);
        $subtextAuth = $this->hubsoftService->getSubtitleText($client);

        return $this->render(
            'FrontendBundle:Social:hubsoftLogin.html.twig',
            [
                'template'          => $template,
                'error'             => $request->get('error'),
                'form'              => $form->createView(),
                'subTextAuth'       => $subtextAuth ? $subtextAuth : " ",
                'textAuth'          => $textAuth,
            ]
        );
    }

    /**
     * @param $cpf
     * @return array|string|string[]|null
     *
     */
    function cleanDocument($cpf) {
       return preg_replace('/[^0-9]/', '', $cpf);
    }

    function hasCPFFormat($texto) {
        return preg_match('/^\d{3}\.\d{3}\.\d{3}-\d{2}$/', $texto) === 1;
    }

    function validateCPF($cpf) {

        // Extrai somente os números
        $cpf = preg_replace( '/[^0-9]/is', '', $cpf );

        // Verifica se foi informado todos os digitos corretamente
        if (strlen($cpf) != 11) {
            return false;
        }

        // Verifica se foi informada uma sequência de digitos repetidos. Ex: 111.111.111-11
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Faz o calculo para validar o CPF
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        return true;

    }
}
