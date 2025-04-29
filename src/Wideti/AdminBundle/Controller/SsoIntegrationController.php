<?php

namespace Wideti\AdminBundle\Controller;

use Wideti\DomainBundle\Service\Module\ModuleAware;

use Symfony\Component\HttpFoundation\Request;
use Rhumsaa\Uuid\Uuid;

use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Entity\OAuthLogin;
use Wideti\DomainBundle\Service\OAuthLogin\OAuthLoginService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Wideti\AdminBundle\Form\SsoIntegrationType;


class SsoIntegrationController
{
    use EntityManagerAware;
    use TwigAware;
    use SecurityAware;
    use FlashMessageAware;
    use ModuleAware;

    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;

    /**
     * @var OAuthLoginService
     */
    private $oauthLoginService;

    /**
     * SsoIntegrationController constructor.
     * @param ConfigurationService $configurationService
     * @param AdminControllerHelper $controllerHelper
     * @param CacheServiceImp $cacheService
     * @param AnalyticsService $analyticsService
     */
    public function __construct(
        AdminControllerHelper $controllerHelper,
        OAuthLoginService     $oauthLoginService
    ) {
        $this->controllerHelper     = $controllerHelper;
        $this->oauthLoginService    = $oauthLoginService;
        $this->ssoTypesSuboptions = array(
            'google_workspace' => 'Google Workspace',
            'google_education' => 'Google Education',
            'ixc' => 'Provedor IXC Soft',
            'database' => 'Banco de Dados',
            'default' => 'Genérico',
            'ad' => 'Azure AD',
            'adfs' => 'Active Directory Federation Services(ADFS)',
        );
        $this->ssoTypes = array(
            'Google' => array(
                'Google Workspace' => 'google_workspace',
                'Google Education' => 'google_education',
            ),
            'Outras Soluções' => array(
                // 'ixc' => 'Provedor IXC Soft',
                // 'database' => 'Banco de Dados',
                'Genérico' => 'default'
            ),
            'Microsoft Identity Platform' => array(
                'Azure AD' => 'ad',
                'Active Directory Federation Services(ADFS)' => 'adfs',
            ),
        );
    }

    /**
     * @param $page
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        if (!$this->moduleService->modulePermission('sso_integration')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        $clientDomain = $client->getDomain();
        
        $queryBuilder = $this->em->getRepository("DomainBundle:OAuthLogin")->createQueryBuilder('o');
        $queryBuilder->where('o.domain = :clientDomain')
             ->andWhere('o.ssoType != :emptyString')
             ->setParameter('clientDomain', $clientDomain)
             ->setParameter('emptyString', '');

        $oauths = $queryBuilder->getQuery()->getResult();
        
        return $this->render(
            'AdminBundle:OAuth:index.html.twig',
            [
                'oauths'                => $oauths,
                'ssoTypes'              => $this->ssoTypesSuboptions
            ]
        );
    }


    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function newAction(Request $request)
    {
        if (!$this->moduleService->modulePermission('sso_integration')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }
        $client = $this->getLoggedClient();
        $options = ['ssoTypes' => $this->ssoTypes];
        $options['attr']['client'] = $client->getId();
        $options['attr']['actionForm'] = 'create';
        $options['attr']['clientDomain'] = $client->getDomain();


        $entity = new OAuthLogin();
        $entity->setScope('openid');

        $form   = $this->controllerHelper->createForm(SsoIntegrationType::class, $entity, $options);
        $form->handleRequest($request);
        $uuidClientSecret = Uuid::uuid4();

        if ($form->isSubmitted() && $form->isValid()) {
            $this->oauthLoginService->create($entity);
            $this->setCreatedFlashMessage();
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('sso_integration'));
        }

        $formView = $form->createView();

        return $this->render(
            'AdminBundle:OAuth:form.html.twig',
            [
                'entity'                    => $entity,
                'form'                      => $formView,
                'client'                    => $client,
                'uuidClientSecret'          => $uuidClientSecret,
                'actionForm'                => 'create'
            ]
        );
    }

    /**
     * @param Request $request
     * @param OAuthLogin $oAuthLogin
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function editAction(Request $request, OAuthLogin $oAuthLogin) {
        if (!$this->moduleService->modulePermission('sso_integration')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }
        $client = $this->getLoggedClient();
        $options = [];
        $options['attr']['client'] = $client->getId();
        $options['attr']['clientDomain'] = $client->getDomain();
        $options['attr']['actionForm'] = 'update';
        $form   = $this->controllerHelper->createForm(SsoIntegrationType::class, $oAuthLogin, $options);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->oauthLoginService->update($oAuthLogin);
            $this->setUpdatedFlashMessage();
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('sso_integration'));
        }

        $formView = $form->createView();

        return $this->render(
            'AdminBundle:OAuth:form.html.twig',
            [
                'entity'                    => $oAuthLogin,
                'form'                      => $formView,
                'client'                    => $client,
                'uuidClientSecret'          => '',
                'actionForm'                => 'update'
            ]
        );
    }

    /**
     * @param Request $request
     * @param OAuthLogin $oAuthLogin
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function deleteAction(Request $request, OAuthLogin $oAuthLogin) {
        if (!$this->moduleService->modulePermission('sso_integration')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }
        $client = $this->getLoggedClient();

        if ($client->getDomain() !== $oAuthLogin->getDomain()) {
            return new JsonResponse(['message' => 'Exclusão de OAuth não permitida.']);
        }

        $this->oauthLoginService->delete($oAuthLogin);
        return new JsonResponse(['message' => 'Registro removido com sucesso']);
    }
}