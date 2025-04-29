<?php

namespace Wideti\DomainBundle\Helpers\Superlogica;

use Wideti\DomainBundle\Helpers\Superlogica\Dto\ClientStatusHookDto;

class ParseHookSuperlogicaHelper
{
    const CLIENT_STATUS_CONGELADO = 'congelado';

    /**
     * @param array $requestContent
     * @return ClientStatusHookDto[]
     */
    public static function parseClientStatusHook(array $requestContent)
    {
        $clients = [];

        if (!$requestContent && !isset($requestContent['data'])) {
            return [];
        }

        foreach ($requestContent as $data) {
            $hasFinancialPending = isset($data['pendencias_financeiras']) && !empty($data['pendencias_financeiras']);

            $clientDto = new ClientStatusHookDto();
            $clientDto
                ->setErpId($data['cliente_id'])
                ->setStatus($data['cliente_status'])
                ->setHasFinancialPending($hasFinancialPending);

            $clients[] = $clientDto;
        }

        return $clients;
    }
}