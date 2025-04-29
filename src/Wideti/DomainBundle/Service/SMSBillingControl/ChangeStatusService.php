<?php

namespace Wideti\DomainBundle\Service\SMSBillingControl;

use Symfony\Component\HttpFoundation\JsonResponse;
use Wideti\DomainBundle\Repository\SMSBillingControlRepository;

class ChangeStatusService
{
    /**
     * @var SMSBillingControlRepository
     */
    private $SMSBillingControlRepository;

    /**
     * ChangeStatusService constructor.
     * @param SMSBillingControlRepository $SMSBillingControlRepository
     */
    public function __construct(SMSBillingControlRepository $SMSBillingControlRepository)
    {
        $this->SMSBillingControlRepository = $SMSBillingControlRepository;
    }

    /**
     * @param $id
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function change($id)
    {
        $result = $this->SMSBillingControlRepository->changeStatus($id);

        if ($result) {
            return new JsonResponse([ 'id' => $id, 'label' => $result ], 200);
        }

        return new JsonResponse("Could not change the status of SMS Billing Control #{$id}", 300);
    }
}
