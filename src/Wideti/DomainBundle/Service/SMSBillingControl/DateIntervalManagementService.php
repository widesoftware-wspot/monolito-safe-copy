<?php

namespace Wideti\DomainBundle\Service\SMSBillingControl;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Repository\SmsBillingControlRepository;

class DateIntervalManagementService
{
    /**
     * @var string
     */
    private $closingDateStart;
    /**
     * @var string
     */
    private $closingDateEnd;
	/**
	 * @var SmsBillingControlRepository
	 */
	private $smsBillingControlRepository;
	/**
	 * @var LastBillingDateService
	 */
	private $lastBillingDateService;

	/**
	 * DateIntervalManagementService constructor.
	 * @param SmsBillingControlRepository $smsBillingControlRepository
	 * @param LastBillingDateService $lastBillingDateService
	 */
	public function __construct(
		SmsBillingControlRepository $smsBillingControlRepository,
		LastBillingDateService $lastBillingDateService
	) {
		$this->smsBillingControlRepository = $smsBillingControlRepository;
		$this->lastBillingDateService = $lastBillingDateService;
	}

	/**
	 * @param Client $client
	 * @return array
	 * @throws \Exception
	 */
    public function get(Client $client)
    {
        $this->set($client);

        return [
            'closingDateStart' => $this->closingDateStart,
            'closingDateEnd'   => $this->closingDateEnd
        ];
    }

	/**
	 * @param Client $client
	 * @throws \Exception
	 * A regra aqui é primeiramente pegar a data da última cobrança do cliente, assim filtramos a partir dela.
	 * Caso o cliente não tenha cobrança realizada, então pega o range baseado no closingDate, 1 mês atrás até hoje-1 dia
	 */
    private function set(Client $client)
    {
	    $clientClosingDate = $client->getClosingDate();
	    $clientClosingDate = str_pad($clientClosingDate, 2, '0', STR_PAD_LEFT);

	    $start = $this->lastBillingDateService->getLastBillingDateSent($client);
	    $end   = clone $start;

	    /**
	     * workarround para tratar mês de fevereiro
	     */
	    if (in_array($clientClosingDate, [30, 31]) && $start->format('m') == '01') {
		    $end = $end->modify('last day of next month');
	    } else {
            $end->setDate(
                $end->format('Y'),
                $end->format('m'),
                $clientClosingDate
            );

            if ($end <= $start) {
                $end->modify('+1 month');
            }

		    $end = $end->modify('-1 day');
	    }

	    $this->closingDateStart = $start->format("Y-m-d");
	    $this->closingDateEnd   = $end->format("Y-m-d");
    }

    /**
     * @return string
     */
    public function getClosingDateStart()
    {
        return $this->closingDateStart;
    }

    /**
     * @return string
     */
    public function getClosingDateEnd()
    {
        return $this->closingDateEnd;
    }
}
