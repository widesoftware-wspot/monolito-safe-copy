<?php

namespace Wideti\AdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Entity\Plan;
use Wideti\DomainBundle\Exception\NonUniqueFieldOnGuestsException;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Service\Analytics\AnalyticsService;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsService;
use Wideti\DomainBundle\Service\CustomFields\Helper\CustomFieldMapper;
use Wideti\DomainBundle\Service\Plan\PlanAssert;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Doctrine\ORM\EntityManager;

/**
 * Class CustomFieldsController
 * @package Wideti\AdminBundle\Controller
 */
class CustomFieldsController
{
    use SecurityAware;
    use TwigAware;
    use SessionAware;

    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;
	/**
	 * @var CacheServiceImp
	 */
	private $cacheService;
    /**
     * @var CustomFieldsService
     */
	private $customFieldsService;
    /**
     * @var DocumentManager
     */
	private $mongo;
	/**
	 * @var AnalyticsService
	 */
	private $analyticsService;
    /**
     * @var EntityManager
     */
    private $em;

	/**
	 * CustomFieldsController constructor.
	 * @param AdminControllerHelper $controllerHelper
	 * @param CacheServiceImp $cacheService
	 * @param CustomFieldsService $customFieldsService
	 * @param DocumentManager $mongo
	 * @param AnalyticsService $analyticsService
     * @param EntityManager $em
	 */
	public function __construct
    (
	    AdminControllerHelper $controllerHelper,
        CacheServiceImp $cacheService,
        CustomFieldsService $customFieldsService,
        DocumentManager $mongo,
		AnalyticsService $analyticsService,
        EntityManager $em
    )
    {
        $this->controllerHelper     = $controllerHelper;
	    $this->cacheService         = $cacheService;
	    $this->customFieldsService  = $customFieldsService;
	    $this->mongo                = $mongo;
	    $this->analyticsService     = $analyticsService;
        $this->em               = $em;
    }

	/**
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
	 * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
	 */
    public function indexAction()
    {
        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);
        $existField = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->checkExistsPropertiesFieldInAllBase('email');
        $client = $this->em->getRepository('DomainBundle:Client')->findOneBy([
            'id' => $client->getId()
        ]);
        $captiveType = $client->getAuthenticationType();

        return $this->render("@Admin/CustomFields/index.html.twig", [
            'security'      => $client->getGuestPasswordRecoverySecurity(),
            'retroactive'   => $client->getAskRetroactiveGuestFields(),
            'allow_guest_password_recovery_email'   => $existField,
            'email'         => $client->getGuestPasswordRecoveryEmail(),
            'captiveType' => $captiveType,
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function ajaxAllTemplateFields()
    {
        $fieldView = $this->customFieldsService->getOnlyAvailableTemplates();
        return new JsonResponse($fieldView);
    }

    /**
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function ajaxSaveFields(Request $request)
    {
	    if ($request->getMethod() !== "POST") {
		    return $this->controllerHelper->redirect($this->controllerHelper->generateUrl("frontend_index"));
	    }

	    $this->analyticsService->handler($request, []);

	    return $this->customFieldsService->ajaxSaveFields($request, $this->getLoggedClient());
    }

    /**
     * @return JsonResponse
     */
    public function ajaxFieldsToLogin()
    {
        $fields = $this->customFieldsService->getFieldsToLogin();
        return new JsonResponse($fields);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function ajaxSaveFieldToLogin(Request $request)
    {
    	$this->analyticsService->handler($request, []);

    	$identifier = json_decode($request->getContent(), true)['identifier'];
        try {
            $this->customFieldsService->setFieldToLogin($identifier);
            return new JsonResponse(['message' => 'Campo selecionado para login com sucesso']);
        } catch (NonUniqueFieldOnGuestsException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => "Ocorreu um erro, tente novamente mais tarde ou contate o suporte para fazer a troca do campo de login"], 500);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function changeAskRetroactiveFieldsAction(Request $request) {
        $client = $this->em->getRepository('DomainBundle:Client')->findOneBy([
            'id' => $this->getLoggedClient()->getId()
        ]);
        PlanAssert::checkOrThrow($client, Plan::PRO);
        $requestContent = json_decode($request->getContent(), true);
        if (empty($requestContent) ||
            !array_key_exists('value', $requestContent)) {
            return new JsonResponse(['message' => 'Request body is empty or invalid. Keys (name) and (value) is required'], 400);
        } 
        $value  = $requestContent['value'];
        
        $client->setAskRetroactiveGuestFields($value);

        $this->em->persist($client);
        $this->em->flush();

        return new JsonResponse(['status' => '200'], 200);

    }
}