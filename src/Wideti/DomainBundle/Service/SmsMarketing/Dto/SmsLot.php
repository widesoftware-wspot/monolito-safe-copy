<?php

namespace Wideti\DomainBundle\Service\SmsMarketing\Dto;

class SmsLot
{
    private $lotNumber;
    private $content;

    /**
     * SmsLot constructor.
     * @param $lotNumber
     * @param $content
     */
    public function __construct($lotNumber, $content)
    {
        $this->lotNumber = $lotNumber;
        $this->content = $content;
    }

    /**
     * @return mixed
     */
    public function getLotNumber()
    {
        return $this->lotNumber;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }
}