<?php

namespace Wideti\DomainBundle\Service\OnlineGuests;

use Monolog\Logger;
use Wideti\DomainBundle\Entity\Client;

class GetTotalOnlineGuestsByClientImp implements GetTotalOnlineGuestsByClient
{
    private $onlineGuestsApiUrl;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * GetTotalOnlineGuestsByClientImp constructor.
     * @param $onlineGuestsApiUrl
     * @param Logger $logger
     */
    public function __construct($onlineGuestsApiUrl, Logger $logger)
    {
        $this->onlineGuestsApiUrl = $onlineGuestsApiUrl;
        $this->logger = $logger;
    }

    public function get(Client $client)
    {
//        try {
//            $result = $this->curl("GET", $this->onlineGuestsApiUrl."/client/".$client->getId()."/count");
//            return $result["total"];
//        } catch (\Exception $ex) {
//            $this->logger->addCritical("Fail to get total of online guests: " . $ex->getMessage());
//        }
        return 0;
    }

    private function curl($method, $url, $body = null)
    {
        $curl = curl_init();

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
//                "authenticationtoken: {$this->token}",
//                "username: {$this->username}",
                "content-type: application/json"
            ]
        ];

        if ($method == "POST" && $body) {
            $options[CURLOPT_POSTFIELDS] = json_encode($body);
        }

        curl_setopt_array($curl, $options);
        $jsonReturn = curl_exec($curl);
        curl_close($curl);

        return json_decode($jsonReturn, true);
    }
}
