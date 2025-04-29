<?php
/**
 * Created by PhpStorm.
 * User: evandro
 * Date: 14/03/19
 * Time: 10:07
 */

namespace Wideti\DomainBundle\Exception;


class MongoDuplicateKeyRegisterException extends \Exception
{
    protected $message = "This register is supposed to be unique";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
