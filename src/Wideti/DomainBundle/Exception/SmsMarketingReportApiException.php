<?php
namespace Wideti\DomainBundle\Exception;

class SmsMarketingReportApiException extends \Exception
{
    protected $message = 'API communication failure';

    public function __construct($message = null, $lotNumber = null, $uri = null, $accessToken = null)
    {
        if ($message !== null) {
            $errorMessage = json_encode([
                "message"       => $message,
                "lotNumber"     => $lotNumber,
                "apiEndpoint"   => $uri,
                "access_token"  => $accessToken
            ]);

            $this->message = $errorMessage;
        }
        parent::__construct($this->message);
    }
}
