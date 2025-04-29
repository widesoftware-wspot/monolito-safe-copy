<?php
namespace Wideti\DomainBundle\Exception;

class EmptyFieldsToUpdateException extends \Exception
{
    protected $message = "Não foram informados campos para serem atualizados.";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
