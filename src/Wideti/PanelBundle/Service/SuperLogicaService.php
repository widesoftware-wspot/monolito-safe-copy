<?php

namespace Wideti\PanelBundle\Service;

class SuperLogicaService
{
	private $accessToken;
	private $appToken;

	/**
	 * SuperLogicaService constructor.
	 * @param $accessToken
	 * @param $appToken
	 */
	public function __construct($accessToken, $appToken)
	{
		$this->accessToken = $accessToken;
		$this->appToken = $appToken;
	}

	/**
     * @param $query
     * @return mixed
     */
    private function queryDebtApi($query)
    {
	    $query          = http_build_query($query);
	    $service_url    = "http://api.superlogica.net/v2/financeiro/cobranca?{$query}";
        $curl           = curl_init($service_url);

        curl_setopt($curl, CURLOPT_HTTPHEADER, [
	        "access_token: {$this->accessToken}",
	        "app_token: {$this->appToken}"
        ]);

        // CURLOPT_RETURNTRANSFER - TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it out directly.
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $curl_response = curl_exec($curl);
        curl_close($curl);

        return json_decode($curl_response);
    }

    /**
     * @param $searchBy
     * @return mixed
     */
    public function processDebtQueryBySearchFilter($searchBy)
    {
        switch ($searchBy) {
            case 'firstPayment':
                return $this->queryDebtApi($this->getQueryFirstPayment());
            case 'migrationSettled':
                return $this->queryDebtApi($this->getQueryMigrationSettled());
            case 'migrationPending':
                return $this->queryDebtApi($this->getQueryMigrationPending());
            case 'migrationCanceled':
                return $this->queryDebtApi($this->getQueryMigrationCanceled());
        }
        return $this->queryDebtApi($this->getQueryFirstPayment());
    }

    /**
     * @return array
     */
    private function getQueryFirstPayment()
    {
        return [
            'filtrarpor'    => 'liquidacao',
            'status'        => 'liquidadas',
            'tipo[0]'       => 'primeiroPagamento'
        ];
    }

    /**
     * @return array
     */
    private function getQueryMigrationSettled()
    {
        return [
            'filtrarpor'    => 'liquidacao',
            'status'        => 'liquidadas',
            'tipo[1]'       => 'migracao'

        ];
    }

    /**
     * @return array
     */
    private function getQueryMigrationPending()
    {
        return [
            'status'    => 'pendentes',
            'tipo[1]'   => 'migracao'

        ];
    }

    /**
     * @return array
     */
    private function getQueryMigrationCanceled()
    {
        return [
            'status'    => 'canceladas',
            'tipo[1]'   => 'migracao'

        ];
    }

    /**
     * @param $firstPayments
     * @return int
     */
    public function processAmountOfYesterdayPayments($firstPayments)
    {
        $totalYesterdayPayments = 0;
        foreach ($firstPayments as $firstPayment) {
            if ($firstPayment->dt_liquidacao_recb == $this->getYesterdayDate()) {
                $totalYesterdayPayments++;
            }
        }
        return $totalYesterdayPayments;
    }

    public function getYesterdayDate()
    {
        $yesterdayDate = new \DateTime();
        $yesterdayDate = $yesterdayDate->sub(new \DateInterval('P1D'));
        $yesterdayDate = $yesterdayDate->format('m/d/Y');
        return $yesterdayDate;
    }
}
