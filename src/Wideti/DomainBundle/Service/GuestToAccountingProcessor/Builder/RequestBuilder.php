<?php

namespace Wideti\DomainBundle\Service\GuestToAccountingProcessor\Builder;

use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Dto\RequestDto;

class RequestBuilder
{
    const PERSIST = "persist";
    const REMOVE  = "remove";

    private $operation;
    private $guest;

    /**
     * @return RequestBuilder
     */
    public static function getBuilder()
    {
        return new RequestBuilder();
    }

    /**
     * @param $operation
     * @return $this
     */
    public function withOperation($operation)
    {
        $this->operation = $operation;
        return $this;
    }

    /**
     * @param $guest
     * @return $this
     */
    public function withGuest($guest)
    {
        $this->guest = $guest;
        return $this;
    }

    /**
     * @return RequestDto
     */
    public function build()
    {
        return new RequestDto(
            $this->operation,
            $this->guest
        );
    }
}
