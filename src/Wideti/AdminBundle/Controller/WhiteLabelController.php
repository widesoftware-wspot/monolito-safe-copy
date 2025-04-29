<?php

namespace Wideti\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Wideti\AdminBundle\Form\WhiteLabelType;
use Wideti\DomainBundle\Entity\Plan;
use Wideti\DomainBundle\Entity\WhiteLabel;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Service\Module\ModuleAware;
use Wideti\DomainBundle\Service\Plan\PlanAssert;
use Wideti\DomainBundle\Service\WhiteLabel\WhiteLabelService;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class WhiteLabelController
{
    use EntityManagerAware;
    use TwigAware;
    use ModuleAware;
    use SecurityAware;
    use SessionAware;

    /**
     * @var FileUpload
     */
    protected $fileUpload;
    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;
	/**
	 * @var WhiteLabelService
	 */
	private $whiteLabelService;

	/**
	 * WhiteLabelController constructor.
	 * @param AdminControllerHelper $controllerHelper
	 * @param WhiteLabelService $whiteLabelService
	 */
	public function __construct(AdminControllerHelper $controllerHelper, WhiteLabelService $whiteLabelService)
    {
        $this->controllerHelper = $controllerHelper;
	    $this->whiteLabelService = $whiteLabelService;
    }

	/**
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
	 * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
	 * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
	 */
	public function editAction(Request $request)
    {
        if (!$this->moduleService->modulePermission('white_label')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $entity = $this->em
            ->getRepository('DomainBundle:WhiteLabel')
            ->findBy([
            	'client' => $client
            ]);

        if (empty($entity)) {
            $entity = new WhiteLabel();
        } else {
            $entity = $entity[0];
        }

        $options['attr']['client']  = $client->getId();
        $options['attr']['id']      = $entity->getId();

        $form = $this->controllerHelper->createForm(
            WhiteLabelType::class,
            $entity,
            $options
        );

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->whiteLabelService->update($entity);
            $files = $request->files->get('wspot_white_label');
            $this->uploadImageAction($files, $entity);
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('white_label_edit'));
        }

        return $this->render(
            'AdminBundle:WhiteLabel:edit.html.twig',
            [
                'form'        => $form->createView(),
                'entity'      => $entity,
                'defaultLogo' => (strpos($entity->getLogotipo(), '/bundles/admin/') !== false) ? true : false
            ]
        );
    }

    public function uploadImageAction($files, WhiteLabel $entity)
    {
        try {
            if ($files['fileLogotipo']) {
                $this->whiteLabelService->uploadImage($files['fileLogotipo'], $entity);
            }
        } catch (\HttpException $error) {
            return new JsonResponse(
                [
                    'message' => $error->getMessage(),
                    'error'   => true
                ]
            );
        }
    }

    public function setFileUpload(FileUpload $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }
}
