<?php

namespace Wideti\PanelBundle\Controller;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Wideti\DomainBundle\Service\SMSBillingControl\ChangeStatusService;
use Wideti\DomainBundle\Service\SMSBillingControl\BillingManager;
use Wideti\DomainBundle\Service\SMSBillingControl\DateIntervalManagementService;
use Wideti\DomainBundle\Service\SMSBillingControl\FilterService;
use Wideti\PanelBundle\Form\Type\SMSBillingControl\SMSBillingControlType;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;

class SMSBillingController
{
    use TwigAware;

    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var FrontendControllerHelper
     */
    private $controllerHelper;
    /**
     * @var SMSBillingControlType
     */
    private $SMSBillingControlType;
    /**
     * @var DateIntervalManagementService
     */
    private $dateIntervalManagementService;
    /**
     * @var ChangeStatusService
     */
    private $changeStatusService;
    /**
     * @var BillingManager
     */
    private $billingManager;
    /**
     * @var FilterService
     */
    private $filterService;
    /**
     * @var string
     */
    private $conditionsToSearch;

    /**
     * SMSBillingController constructor.
     * @param ConfigurationService $configurationService
     * @param FrontendControllerHelper $controllerHelper
     * @param DateIntervalManagementService $dateIntervalManagementService
     * @param ChangeStatusService $changeStatusService
     * @param BillingManager $billingManager
     * @param FilterService $filterService
     */
    public function __construct(
        ConfigurationService          $configurationService,
        FrontendControllerHelper      $controllerHelper,
        DateIntervalManagementService $dateIntervalManagementService,
        ChangeStatusService           $changeStatusService,
        BillingManager                $billingManager,
        FilterService                 $filterService
    )
    {
        $this->configurationService          = $configurationService;
        $this->controllerHelper              = $controllerHelper;
        $this->dateIntervalManagementService = $dateIntervalManagementService;
        $this->changeStatusService           = $changeStatusService;
        $this->billingManager                = $billingManager;
        $this->filterService                 = $filterService;
        $this->SMSBillingControlType         = SMSBillingControlType::class;
    }

	/**
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
    public function indexAction(Request $request)
    {
        $data = $this->prepareFilterAndData($request);

        return $this->render('PanelBundle:SMSBilling:index.html.twig',
            $this->setReturningDataFormat(
                $data['filterForm']->createView(),
                $data['billingData'],
                $data['filterLabel']
            )
        );
    }

	/**
	 * @param Request $request
	 * @return array
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
    public function prepareFilterAndData(Request $request)
    {
        $filterForm = $this->controllerHelper->createForm($this->SMSBillingControlType);
        $filterForm->handleRequest($request);

        return $this->setDataToProcess($filterForm);
    }

    /**
     * @param $form
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setDataToProcess($form)
    {
        if ($form->isValid()) {
            if (!empty($form->get('date_from')->getData())) {
                $this->conditionsToSearch .= " AND s.closing_date_reference >= " .
                    "'{$form->get('date_from')->getData()->format('Y-m-d')}'";
            }

            if (!empty($form->get('date_to')->getData())) {
                $this->conditionsToSearch .= " AND s.closing_date_reference <= " .
                    "'{$form->get('date_to')->getData()->format('Y-m-d')}'";
            }

            if (!is_null($form->get('status')->getData())) {
                $this->conditionsToSearch .= " AND s.status = " .
                    "'{$form->get('status')->getData()}'";
            }

            if (!empty($form->get('filtro')->getData())) {
                $filterField = "{$form->get('filtro')->getData()}Filter";
                return $this->{$filterField}($form);
            }
            return $this->otherFilters($form);
        }

        return $this->defaultFilter($form);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function changeStatusAction(Request $request)
    {
        return $this->changeStatusService->change($request->get('id'));
    }

    /**
     * @param $form
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function clientFilter($form)
    {
        return $this->setReturningDataFormat(
            $form,
            $this->filterService->filter("c.company LIKE '%{$form->get('value')->getData()}%' {$this->conditionsToSearch}"),
            "Razão Social que contenha {$form->get('value')->getData()}"
        );
    }

    /**
     * @param $form
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function otherFilters($form)
    {
        return $this->setReturningDataFormat(
            $form,
            $this->filterService->filter("1 {$this->conditionsToSearch}"),
            ""
        );
    }

    /**
     * @param $form
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function erpIdFilter($form)
    {
        return $this->setReturningDataFormat(
            $form,
            $this->filterService->filter("erp_id = '{$form->get('value')->getData()}' {$this->conditionsToSearch}"),
            "ERP ID #{$form->get('value')->getData()}"
        );
    }

    /**
     * @param $form
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function domainFilter($form)
    {
        return $this->setReturningDataFormat(
            $form,
            $this->filterService->filter(
                "c.domain LIKE '%{$form->get('value')->getData()}%' {$this->conditionsToSearch}"
            ),
            "Domínio que contenha {$form->get('value')->getData()}"
        );
    }

    /**
     * @param $form
     * @param null $customFilter
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function statusFilter($form, $customFilter = null)
    {
        $filterValue  = (!is_null($customFilter) ? $customFilter : $form->get('value')->getData());
        $data         = $this->filterService->filter("s.status = 0 {$this->conditionsToSearch}");

        return $this->setReturningDataFormat($form, $data,"Status: {$filterValue}");
    }

	/**
	 * @param $form
	 * @return array
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function defaultFilter($form)
	{
		return $this->statusFilter($form, 'Pendente');
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
	public function generateBillingAction()
	{
		$this->billingManager->manageBilling();
		return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('panel_user_sms_billing_control'));
	}

    /**
     * @param AbstractType $form
     * @param $dateInterval
     * @param $billingData
     * @param $filterLabel
     * @param $noDataCriteria
     * @return array
     */
    private function setReturningDataFormat($form, $billingData, $filterLabel)
    {
        return [
            'filterForm'   => $form,
            'billingData'  => $billingData,
            'filterLabel'  => $filterLabel,
        ];
    }
}