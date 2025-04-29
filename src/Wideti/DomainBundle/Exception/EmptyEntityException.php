<?php
namespace Wideti\DomainBundle\Exception;

class EmptyEntityException extends \Exception
{
    protected $message = 'There are no records for this entity';

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
