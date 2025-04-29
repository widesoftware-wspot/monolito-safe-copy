<?php


namespace Wideti\DomainBundle\Gateways\Consents;

use Exception;

class SignedConsentRevoke
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var bool
     */
    private $hasError;

    /**
     * @var Exception
     */
    private $error;

    /**
     * Consent constructor.
     * @param string $message
     */
    private function __construct($message) {
        $this->hasError = false;
        $this->message = $message;
    }

    /**
     * @param string $message
     * @return SignedConsentRevoke
     */
    public static function create($message) {
        return new SignedConsentRevoke($message);
    }

    /**
     * @param Exception $err
     * @return SignedConsentRevoke
     */
    public function withError(Exception $err) {
        $this->hasError = true;
        $this->error = $err;
        return $this;
    }

    /**
     * @return Exception
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        return $this->hasError;
    }
}