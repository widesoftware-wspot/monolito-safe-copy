<?php


namespace Wideti\DomainBundle\Exception;


class NotExistsAccessCodeLotException extends \Exception
{
    protected $message = "There isn't any Access Code Lot registered";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}