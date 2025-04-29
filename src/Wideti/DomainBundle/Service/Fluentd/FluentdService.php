<?php

namespace Wideti\DomainBundle\Service\Fluentd;

interface FluentdService
{
    public function send($tag, $data);
}