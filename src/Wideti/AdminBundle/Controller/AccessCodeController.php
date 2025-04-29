<?php

namespace Wideti\AdminBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wideti\AdminBundle\Form\AccessCodeType;
use Wideti\AdminBundle\Form\AccessCodeSettingsType;
use Wideti\DomainBundle\Asserts\EntitiesExistsAssert;
use Wideti\DomainBundle\Dto\AccessCodeExportDto;
use Wideti\DomainBundle\Entity\AccessCode;
use Wideti\DomainBundle\Entity\AccessCodeCodes;
use Wideti\DomainBundle\Entity\AccessCodeSettings;
use Wideti\DomainBundle\Entity\Plan;
use Wideti\DomainBundle\Exception\EmptyEntityException;
use Wideti\DomainBundle\Exception\NotExistsAccessCodeLotException;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Service\AccessCode\AccessCodeService;
use Wideti\DomainBundle\Service\AccessCode\AccessCodeServiceImp;
use Wideti\DomainBundle\Service\AccessCode\Assert\AssertEnableAccessCode;
use Wideti\DomainBundle\Service\Analytics\AnalyticsService;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsAware;
use Wideti\DomainBundle\Service\Group\GroupServiceAware;
use Wideti\DomainBundle\Service\Module\ModuleAware;
use Wideti\DomainBundle\Service\Plan\PlanAssert;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class AccessCodeController
{
    use EntityManagerAware;
    use MongoAware;
    use TwigAware;
    use FlashMessageAware;
    use ModuleAware;
    use GroupServiceAware;
    use CustomFieldsAware;
    use ContainerAwareTrait;

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
     * @var FileUpload
     */
    protected $fileUpload;
    /**
     * @var AccessCodeService
     */
    private $accessCodeService;
    /**
     * @var AnalyticsService
     */
    private $analyticsService;

    /**
     * AccessCodeController constructor.
     * @param ConfigurationService $configurationService
     * @param AdminControllerHelper $controllerHelper
     * @param CacheServiceImp $cacheService
     * @param AccessCodeServiceImp $accessCodeService
     * @param AnalyticsService $analyticsService
     */
    public function __construct(
        ConfigurationService $configurationService,
        AdminControllerHelper $controllerHelper,
        CacheServiceImp $cacheService,
        AccessCodeServiceImp $accessCodeService,
        AnalyticsService $analyticsService
    ) {
        $this->controllerHelper     = $controllerHelper;
        $this->configurationService = $configurationService;
        $this->cacheService         = $cacheService;
        $this->accessCodeService    = $accessCodeService;
        $this->analyticsService = $analyticsService;
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
     * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
     */
	public function indexAction(Request $request)
    {
        if (!$this->moduleService->modulePermission('access_code')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $moduleStatus = $this->moduleService->checkModuleIsActive('access_code');

        $entities = $this->em
            ->getRepository('DomainBundle:AccessCode')
            ->findBy([
                'client' => $client
            ]);

        $arrayEntities = [];

        foreach ($entities as $value) {
            $codesUsed = null;

            if ($value->getType() == AccessCode::TYPE_RANDOM) {
                $codesUsed = $this->accessCodeService->countUsed($value);
            }

            $periodFrom = $value->getPeriodFrom();
            $periodTo   = $value->getPeriodTo();

            array_push(
                $arrayEntities,
                [
                    'id'                => $value->getId(),
                    'type'              => $value->getType(),
                    'step'              => $value->getStep(),
                    'period_from'       => ($periodFrom) ? date_format($periodFrom, 'd/m/Y') : '-',
                    'period_to'         => ($periodTo) ? date_format($periodTo, 'd/m/Y') : '-',
                    'connection_time'   => ($value->getConnectionTime()) ?: '-',
                    'codes'             => ($value->getCodes()) ?: '-',
                    'quantity'          => ($value->getQuantity()) ?: '-',
                    'used'              => ($codesUsed) ?: '-',
                    'status'            => ($value->getEnable()) ? 'Ativo' : 'Inativo'
                ]
            );
        }

        $settings = $this->em
            ->getRepository('DomainBundle:AccessCodeSettings')
            ->findOneBy([
                'client' => $client
            ]);

        try {
            EntitiesExistsAssert::exists($entities);
        } catch (EmptyEntityException $e) {
            return $this->render(
                'AdminBundle:AccessCode:index.html.twig',
                [
                    'entities'      => $arrayEntities,
                    'settings'      => $settings->getId(),
                    'moduleStatus'  => $moduleStatus,
                    'block'         => $request->get('block'),
                    'enableActive'  => false
                ]
            );
        }

        return $this->render(
            'AdminBundle:AccessCode:index.html.twig',
            [
                'entities'      => $arrayEntities,
                'settings'      => $settings->getId(),
                'moduleStatus'  => $moduleStatus,
                'block'         => $request->get('block'),
                'enableActive'  => true
            ]
        );
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
     * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
     */
    public function newAction(Request $request)
    {
        if (!$this->moduleService->modulePermission('access_code')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $client     = $this->getLoggedClient();
        $accessCode = new AccessCode();

        $options['attr']['client']  = $client->getId();
        $options['attr']['id']      = null;

        $form = $this->controllerHelper->createForm(
            AccessCodeType::class,
            $accessCode,
            $options
        );

        $form->handleRequest($request);

        if ($form->isValid()) {
            try {
                $extraParams = [
                    'code' => $form->get('code')->getData()
                ];
                $this->accessCodeService->create($accessCode, $extraParams);
                $files = $request->files->get('wspot_access_code');
                $this->uploadImageAction($files, $accessCode);
                $this->setCreatedFlashMessage();
                $this->analyticsService->handler($request, true);
                return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('access_code'));
            } catch (\Exception $e) {
                if ($e->getMessage() == 'accessCodeAlreadyExists') {
                    $form->addError(new FormError('Existe(m) outro(s) cadastro(s) para este mesmo Ponto de Acesso com tipo de solicitação (Primeira tela/Após o cadastro) diferente.'));
                    $form->addError(new FormError('Lembre-se que para cada Ponto de acesso só é permitido escolher um tipo de solicitação (Primeira tela/Após o cadastro).'));
                } else {
                    $form->addError(new FormError($e->getMessage()));
                }
            }
        }

        return $this->render(
            'AdminBundle:AccessCode:form.html.twig',
            [
                'entity' => $accessCode,
                'form'   => $form->createView()
            ]
        );
    }

    /**
     * @param Request $request
     * @param AccessCode $accessCode
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
     * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
     */
    public function editAction(Request $request, AccessCode $accessCode)
    {
        if (!$this->moduleService->modulePermission('access_code')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $preDefinedCode = '';
        if ($accessCode->getType() === AccessCode::TYPE_PREDEFINED) {
            foreach ($accessCode->getCodes() as $codes) {
                $preDefinedCode = $codes->getCode();
            }
        }

        $options['attr']['client']  = $client->getId();
        $options['attr']['id']      = $accessCode->getId();
        $options['attr']['code']    = $preDefinedCode;
        $type                       = $accessCode->getType();

        $form = $this->controllerHelper->createForm(
            AccessCodeType::class,
            $accessCode,
            $options
        );

        $form->handleRequest($request);

        if ($form->isValid()) {
            try {
                $accessCode->setType($type);
                if ($accessCode->getType() === AccessCode::TYPE_PREDEFINED) {
                    $this->accessCodeService->updatePreDefinedCode($accessCode, $preDefinedCode, $form['code']->getData());
                }
                $this->accessCodeService->update($accessCode);
                $files = $request->files->get('wspot_access_code');
                $this->uploadImageAction($files, $accessCode);
                $this->setUpdatedFlashMessage();
                return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('access_code'));
            } catch (\Exception $e) {
                if ($e->getMessage() == 'accessCodeAlreadyExists') {
                    $form->addError(new FormError('Existe(m) outro(s) cadastro(s) para este mesmo Ponto de Acesso com tipo de solicitação (Primeira tela/Após o cadastro) diferente.'));
                    $form->addError(new FormError('Lembre-se que para cada Ponto de acesso só é permitido escolher um tipo de solicitação (Primeira tela/Após o cadastro).'));
                } else {
                    $form->addError(new FormError($e->getMessage()));
                }
            }
        }

        return $this->render(
            'AdminBundle:AccessCode:form.html.twig',
            [
                'entity' => $accessCode,
                'form'   => $form->createView()
            ]
        );
    }

    /**
     * @param AccessCode $accessCode
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
     * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
     */
    public function deleteAction(AccessCode $accessCode, Request $request)
    {
        if (!$this->moduleService->modulePermission('access_code')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        try {
            $this->accessCodeService->delete($accessCode);

            $entities = $this->em
                ->getRepository('DomainBundle:AccessCode')
                ->findBy([
                    'client' => $client
                ]);

            if (!$entities) {
                $module = $this->em
                    ->getRepository('DomainBundle:ModuleConfigurationValue')
                    ->findByModuleConfigurationKey($this->getLoggedClient(), 'enable_access_code');

                $this->moduleService->enableOrDisableModule($module, false);
            }

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(
                    [
                        'type'    => 'msg',
                        'message' => 'Registro removido com sucesso'
                    ]
                );
            }
        } catch (\Exception $e) {
            return new JsonResponse(
                [
                    'type'    => 'msg',
                    'message' => 'Exclusão não permitida'
                ]
            );
        }

        return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('access_code'));
    }

    /**
     * @param Request $request
     * @param AccessCodeSettings $settings
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
     * @throws \Wideti\DomainBundle\Exception\NotAuthorizedPlanException
     */
    public function preferencesAction(Request $request, AccessCodeSettings $settings)
    {
        if (!$this->moduleService->modulePermission('access_code')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $options['attr']['client']  = $client->getId();
        $options['attr']['id']      = $settings->getId();

        $form = $this->controllerHelper->createForm(
            AccessCodeSettingsType::class,
            $settings,
            $options
        );

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->accessCodeService->preferences($settings);
            $this->setFlashMessage('notice', 'Preferências alteradas com sucesso');
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('access_code'));
        }

        return $this->render(
            'AdminBundle:AccessCode:preferences.html.twig',
            [
                'entity' => $settings,
                'form'   => $form->createView()
            ]
        );
    }

    public function exportCodesCSVAction(AccessCode $lot)
    {
        $codes  = $lot->getCodes();

        $handle        = fopen('php://memory', 'r+');
        $columnTitle   = [];
        $loginField    = $this->customFieldsService->getLoginField()[0]->getNames()['pt_br'];

        $columnTitle[] = 'Lote';
        $columnTitle[] = 'Código de acesso';
        $columnTitle[] = 'Tempo de conexão';
        $columnTitle[] = 'Utilizado?';
        $columnTitle[] = 'Data Utilização';
        $columnTitle[] = 'Utilizado por ('.$loginField.')';

        fputcsv($handle, $columnTitle);

        /**
         * @var AccessCodeCodes $code
         */
        foreach ($codes as $code) {

            $usedBy = $code->getGuest()
                ? $this->getUsedBy($code->getGuest()->getId())
                : ''
            ;

            $dto = new AccessCodeExportDto();
            $dto->setLotNumber($lot->getLotNumber());
            $dto->setAccessCode($this->utf8Fix($code->getCode()));
            $dto->setConnectionTime($lot->getConnectionTime());
            $dto->setUsed($this->utf8Fix(($code->getUsed()) ? 'Sim' : 'Não'));
            $dto->setUsedDate(($code->getUsedTime()) ? date_format($code->getUsedTime(), 'd/m/Y H:i:s') : '');
            $dto->setUsedBy($usedBy);

            fputcsv(
                $handle,
                $dto->getValuesAsArray(),
                ','
            );
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return new Response(
            $content,
            200,
            [
                'Content-Type'          => 'application/force-download; charset=UTF-8',
                'Content-Disposition'   => 'attachment; filename="export.csv"',
                'Content-Encoding'      => 'UTF-8'
            ]
        );
    }

    public function printAction(AccessCode $lot)
    {
        $client         = $this->getLoggedClient();
        $awsBucketName  = $this->container->getParameter('aws_bucket_name');
        $awsFolderName  = $client->getDomain();

        $general = [
            'logotipo'          => ($lot->getLogotipo()) ? "https://{$awsBucketName}/{$awsFolderName}/{$lot->getLogotipo()}" : '',
            'text'              => $lot->getText(),
            'period_from'       => ($lot->getPeriodFrom()) ? date_format($lot->getPeriodFrom(), 'd/m/y') : '',
            'period_to'         => ($lot->getPeriodTo()) ? date_format($lot->getPeriodTo(), 'd/m/y') : '',
            'time'              => $lot->getConnectionTime(),
            'backgroundColor'   => $lot->getBackgroundColor(),
            'fontColor'         => $lot->getFontColor()
        ];
        $codes   = [];

        if ($lot->getType() == AccessCode::TYPE_PREDEFINED) {
            for ($i=0; $i<8; $i++) {
                array_push($codes, $lot->getCodes()[0]->getCode());
            }
        } else {
            foreach ($lot->getCodes() as $item) {
                array_push($codes, $item->getCode());
            }
        }

        return $this->render(
            'AdminBundle:AccessCode:print.html.twig',
            [
                'general'   => $general,
                'codes'     => $codes
            ]
        );
    }

    public function moduleConfigAction(Request $request)
    {
        $nas                            = $this->session->get(Nas::NAS_SESSION_KEY);
        $client                         = $this->getLoggedClient();
        $status                         = $request->get('status');
        $confirmation                   = $this->configurationService->get($nas, $client, 'confirmation_email');
        $blockPerTimeOrAccessValidity   = $this->groupService->checkModuleIsActive('blockPerTimeOrAccessValidity');
        $businessHours                  = $this->em
            ->getRepository("DomainBundle:ModuleConfigurationValue")
            ->findByModuleConfigurationKey($this->getLoggedClient(), 'enable_business_hours');

        if ($status == 'enable' &&
            ($confirmation == 1 || $blockPerTimeOrAccessValidity || $businessHours->getValue() == 1)
        ) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('access_code', [
                'block' => true
            ]));
        }

        $accessCode = $this->em
            ->getRepository("DomainBundle:ModuleConfigurationValue")
            ->findByModuleConfigurationKey($this->getLoggedClient(), 'enable_access_code');

        $accessCodeSize = $this->em
            ->getRepository('DomainBundle:AccessCode')
            ->findBy([
                'client' => $client
            ]);

        try {
            AssertEnableAccessCode::validateAccessCode($accessCodeSize);
        } catch (NotExistsAccessCodeLotException $ex) {
            $this->session->getFlashBag()->add('error',
                "É necessário cadastrar um código para ativar essa funcionalidade");
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('access_code'));
        }

        $this->moduleService->enableOrDisableModule($accessCode, $status);

        if ($status == 'disable') {
            $this->configurationService->deleteExpiration($this->getLoggedClient());
        }

        return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('access_code'));
    }

    public function utf8Fix($string)
    {
        if (!preg_match('/linux/i', $_SERVER['HTTP_USER_AGENT'])) {
            return utf8_decode($string);
        }
        return $string;
    }

    private function getUsedBy($userName)
    {
        $usedBy = 'N/I';

        $guest = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneBy([
                'mysql' => $userName
            ]);

        if ($guest) {
            $usedBy = $guest->getProperties()[$guest->getLoginField()];
        }

        return $usedBy;
    }

    public function uploadImageAction($files, AccessCode $entity)
    {
        try {
            if ($files['fileLogotipo']) {
                $this->accessCodeService->uploadImage($files['fileLogotipo'], $entity);
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
