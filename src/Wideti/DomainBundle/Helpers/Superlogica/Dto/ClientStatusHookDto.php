<?php

namespace Wideti\DomainBundle\Helpers\Superlogica\Dto;

class ClientStatusHookDto
{
    /**
     * @var string
     */
    private $status;

    /**
     * @var int
     */
    private $erpId;

    /**
     * @var bool
     */
    private $hasFinancialPending;

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return ClientStatusHookDto
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return int
     */
    public function getErpId()
    {
        return $this->erpId;
    }

    /**
     * @param int $erpId
     * @return ClientStatusHookDto
     */
    public function setErpId($erpId)
    {
        $this->erpId = $erpId;
        return $this;
    }

    /**
     * @return bool
     */
    public function isHasFinancialPending()
    {
        return $this->hasFinancialPending;
    }

    /**
     * @param bool $hasFinancialPending
     * @return ClientStatusHookDto
     */
    public function setHasFinancialPending($hasFinancialPending)
    {
        $this->hasFinancialPending = $hasFinancialPending;
        return $this;
    }
}
