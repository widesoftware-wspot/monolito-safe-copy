<?php

namespace Wideti\DomainBundle\Helpers;

use Wideti\DomainBundle\Repository\ReservedDomainRepository;

class ClientHelper
{
    /**
     * @var ReservedDomainRepository
     */
    private $reservedDomainRepository;

    /**
     * ClientHelper constructor.
     * @param ReservedDomainRepository $reservedDomainRepository
     */
    public function __construct(ReservedDomainRepository $reservedDomainRepository)
    {
        $this->reservedDomainRepository = $reservedDomainRepository;
    }

    /**
     * @param $domain
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function checkIfIsReservedDomain($domain)
    {
        return $this->reservedDomainRepository->domainExists($domain);
    }

    public static function domainIsValid($domain)
    {
        if (preg_match('/^([A-Za-z0-9]{1,})$/', $domain) !== 1) {
            return false;
        }
        return true;
    }

    public static function panelDomainIsValid($domain)
    {
        if (preg_match('/^([A-Za-z0-9.]{1,})$/', $domain) !== 1) {
            return false;
        }
        return true;
    }


    public static function translateFields($field)
    {
        $translate = [
            'domain'                    => 'Domínio',
            'erp_id'                    => 'ERP ID',
            'type'                      => 'Tipo de cliente',
            'company'                   => 'Nome da empresa',
            'smsCost'                   => 'Valor da SMS',
            'contractedAccessPoints'    => 'Qtde APs contratadas',
            'closingDate'               => 'Data de fechamento',
            'status'                    => 'Status',
            'apCheck'                   => 'Verificação de AP',
            'pocEndDate'                => 'Término da POC',
        ];

        if (!array_key_exists($field, $translate)) {
            return $field;
        }

        return $translate[$field];
    }
}
