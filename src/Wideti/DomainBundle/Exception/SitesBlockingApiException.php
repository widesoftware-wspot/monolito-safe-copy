<?php
namespace Wideti\DomainBundle\Exception;

class SitesBlockingApiException extends \Exception
{
    protected $message = 'API communication failure';

    public function __construct($message = null, $apId = null, $uri = null, $accessToken = null)
    {
        if ($message !== null) {
            $errorMessage = json_encode([
                "message"       => $message,
                "apId"          => $apId,
                "apiEndpoint"   => $uri,
                "access_token"  => $accessToken
            ]);

            $this->message = $errorMessage;
        }
        parent::__construct($this->message);
    }
}
