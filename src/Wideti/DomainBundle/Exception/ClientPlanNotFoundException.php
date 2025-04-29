<?php
/**
 * Created by PhpStorm.
 * User: evandro
 * Date: 14/03/19
 * Time: 10:07
 */

namespace Wideti\DomainBundle\Exception;


class ClientPlanNotFoundException extends \Exception
{
    protected $message = "Plan not found for client";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}