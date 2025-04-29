<?php

namespace Wideti\AdminBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Wideti\AdminBundle\Form\TemplateType;
use Wideti\DomainBundle\Entity\Plan;
use Wideti\DomainBundle\Entity\Template;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Service\Analytics\AnalyticsService;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Plan\PlanAssert;
use Wideti\DomainBundle\Service\Template\LandscapeImage;
use Wideti\DomainBundle\Service\Template\PartnerLogo;
use Wideti\DomainBundle\Service\Template\PortraitImage;
use Wideti\DomainBundle\Service\Template\TemplateAware;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class TemplateController
{
    use EntityManagerAware;
    use TwigAware;
    use TemplateAware;
    use FlashMessageAware;

    /**
     * @var FileUpload
     */
    protected $fileUpload;
    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var AnalyticsService
     */
    private $analyticsService;

    /**
     * @param AdminControllerHelper $controllerHelper
     * @param ConfigurationService $configurationService
     * @param AnalyticsService $analyticsService
     */
    public function __construct(
        AdminControllerHelper $controllerHelper,
        ConfigurationService $configurationService,
        AnalyticsService $analyticsService
    ) {
        $this->controllerHelper = $controllerHelper;
        $this->configurationService = $configurationService;
        $this->analyticsService = $analyticsService;
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

        $entities = $this->em->getRepository('DomainBundle:Template')->findByClient($this->getLoggedClient());
        $defaultTemplate = $this->em->getRepository('DomainBundle:Template')->defaultTemplate($this->getLoggedClient());

        return $this->render(
            'AdminBundle:Template:index.html.twig',
            [
                'entities'      => $entities,
                'firstTemplate' => $defaultTemplate->getId()
            ]
        );
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
     * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
     */
    public function newAction(Request $request)
    {
        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $entity = new Template();
        $form   = $this->controllerHelper->createForm(TemplateType::class, $entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->templateService->create($entity);

            $files = $request->files->get('wideti_AdminBundle_template');
            $this->setBackgroundImageHash($files, $entity);
            $this->uploadImageAction($files, $entity);

            $this->setCreatedFlashMessage();

            $this->analyticsService->handler($request, true);

            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('template'));
        }

        return $this->render('AdminBundle:Template:form.html.twig', [
            'entity' => $entity,
            'domain' => $this->getLoggedClient()->getDomain(),
            'form'   => $form->createView(),
        ]);
    }

    /**
     * @ParamConverter(
     *      "template",
     *      class       = "DomainBundle:Template",
     *      converter   = "client",
     *      options     = {"message" = "Template não encontrado."}
     * )
     * @param Request $request
     * @param Template $template
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
     * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
     */
    public function editAction(Request $request, Template $template)
    {
        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $form   = $this->controllerHelper->createForm(TemplateType::class, $template);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $files = $request->files->get('wideti_AdminBundle_template');

            $this->deleteOldFilesOnS3($files, $template);

            $this->templateService->update($template);

            $this->setBackgroundImageHash($files, $template);
            $this->uploadImageAction($files, $template);

            $this->setUpdatedFlashMessage();

            $this->analyticsService->handler($request, true);

            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('template'));
        }

        return $this->render('AdminBundle:Template:form.html.twig', [
            'entity' => $template,
            'domain' => $template->getClient()->getDomain(),
            'form'   => $form->createView(),
        ]);
    }

    /**
     * @ParamConverter(
     *      "template",
     *      class       = "DomainBundle:Template",
     *      converter   = "client",
     *      options     = {"message" = "Template não encontrado."}
     * )
     * @param Request $request
     * @param Template $template
     * @return JsonResponse
     * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
     * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
     */
    public function deleteAction(Request $request, Template $template)
    {
        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        try {
            $this->templateService->delete($template);

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'type'    => 'success',
                    'message' => 'Registro removido com sucesso'
                ]);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'type'    => 'error',
                'message' => 'Não é possível excluir um template que possua ' .
                    '<strong>"Grupos de Pontos de Acessos"</strong>, <strong>"Pontos de Acessos"</strong> ou ' .
                    '<strong>"Campanhas"</strong> relacionados.'
            ]);
        }
    }

    public function uploadImageAction($files, Template $template)
    {
        try {
            if (isset($files['filePartnerLogo']) && $files['filePartnerLogo']) {
                $this->templateService->uploadImage(new PartnerLogo($template, $files['filePartnerLogo']));
            }

            if (isset($files['fileBackgroundImage']) && $files['fileBackgroundImage']) {
                $this->templateService->uploadImage(new LandscapeImage($template, $files['fileBackgroundImage']));
            }

            if (isset($files['fileBackgroundPortraitImage']) && $files['fileBackgroundPortraitImage']) {
                $this->templateService->uploadImage(new PortraitImage($template, $files['fileBackgroundPortraitImage']));
            }
        } catch (HttpException $error) {
            return new JsonResponse([ 'message' => $error->getMessage(), 'error' => true ]);
        }
    }

    public function removeLogoPartnerAction(Template $template)
    {
        $this->templateService->deleteImage(new PartnerLogo($template));
        return new JsonResponse([ 'message' => "Logotipo removido com sucesso", ]);
    }

    public function removeBackgroundImageAction(Template $template)
    {
        $this->templateService->deleteImage(new LandscapeImage($template));
        return new JsonResponse([ 'message' => "Imagem do background (horizontal) removida com sucesso" ]);
    }

    public function removeBackgroundPortraitImageAction(Template $template)
    {
        $this->templateService->deleteImage(new PortraitImage($template));
        return new JsonResponse([ 'message' => "Imagem do background (vertical) removida com sucesso" ]);
    }

    public function setFileUpload(FileUpload $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }

    public function previewAction(Template $template)
    {
        $config     = $this->configurationService->getDefaultConfiguration($this->getLoggedClient());
        $indexUrl   = $this->controllerHelper->generateUrl('frontend_preview_admin', ['template_id' => $template->getId()]);

        return $this->render('@Admin/Template/preview.html.twig', [
            'config'    => $config,
            'indexUrl'  => $indexUrl,
            'entity'    => $template
        ]);
    }

    public function renderTemplateAction(Template $template)
    {
        $config = $this->configurationService->getDefaultConfiguration($this->getLoggedClient());

        return $this->render('AdminBundle:Template:previewTemplate.html.twig', [
            'config'      => $config,
            'template'    => $template,
            'templateCSS' => $template->getBackgroundCSSConfiguration()
        ]);
    }

    private function setBackgroundImageHash($files, Template $template)
    {
        if (isset($files['fileBackgroundImage'])) {
            $template->setBackgroundImageHash($template->getUpdatedTimestamp());
        }

        if (isset($files['fileBackgroundPortraitImage'])) {
            $template->setBackgroundPortraitImageHash($template->getUpdatedTimestamp());
        }

        $this->em->persist($template);
    }

    private function deleteOldFilesOnS3($files, Template $template)
    {
        if (isset($files['fileBackgroundImage']) && $files['fileBackgroundImage']) {
            $this->removeBackgroundImageAction($template);
        }

        if (isset($files['fileBackgroundPortraitImage']) && $files['fileBackgroundPortraitImage']) {
            $this->removeBackgroundPortraitImageAction($template);
        }
    }
}
