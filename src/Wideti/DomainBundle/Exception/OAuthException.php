<?php
namespace Wideti\DomainBundle\Exception;

class OAuthException extends \Exception
{
    protected $message = "Erro ao tentar buscar informações do provedor de identidade, tente novamente mais tarde ou contate o suporte";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}