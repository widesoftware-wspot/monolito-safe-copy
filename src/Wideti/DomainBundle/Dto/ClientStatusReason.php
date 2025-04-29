<?php

namespace Wideti\DomainBundle\Dto;

class ClientStatusReason
{
    private function __construct() {}

    const FINANCIAL_PENDING = 'Pendência Financeira';
    const SETTLED_CHARGES = 'Cobrança Liquidada';
    const UNFREEZE_CLIENT = 'Cliente descongelado';
    const CANCELLATION_REQUESTED_BY_CLIENT  = 'Cliente solicitou cancelamento';
}
