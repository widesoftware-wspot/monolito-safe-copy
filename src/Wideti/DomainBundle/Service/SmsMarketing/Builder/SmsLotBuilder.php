<?php

namespace Wideti\DomainBundle\Service\SmsMarketing\Builder;

use Wideti\DomainBundle\Service\SmsMarketing\Dto\SmsLot;

class SmsLotBuilder
{
    private $lotNumber;
    private $content;

    public static function getBuilder()
    {
        return new SmsLotBuilder();
    }

    /**
     * @param $lotNumber
     * @return $this
     */
    public function withLotNumber($lotNumber)
    {
        $this->lotNumber = $lotNumber;
        return $this;
    }

    /**
     * @param $content
     * @return $this
     */
    public function withContent($content)
    {
        $this->content = $content;
        return $this;
    }

    public function build()
    {
        return new SmsLot($this->lotNumber, $this->content);
    }
}
