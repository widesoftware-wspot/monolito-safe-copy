<?php


namespace Wideti\DomainBundle\Gateways\Sessions;

use Exception;

class SessionResponse
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var bool
     */
    private $hasError;
    /**
     * @var Exception
     */
    private $error;

    /**
     * Condition constructor.
     * @param string $id
     */
    private function __construct($id)
    {
        $this->hasError = false;
        $this->id = $id;
    }

    /**
     * @param string $id
     * @param string $description
     * @return SessionResponse
     */
    public static function create($id) {
        return new SessionResponse($id);
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Exception $err
     * @return SessionResponse
     */
    public function withError(Exception $err) {
        $this->hasError = true;
        $this->error = $err;
        return $this;
    }
}
