<?php

namespace Wideti\DomainBundle\Service\HttpRequest;

use Wideti\DomainBundle\Service\HttpRequest\Dto\HttpResponse;

interface HttpRequestService
{
    /**
     * @param $url
     * @param $headers
     * @return HttpResponse
     */
    public function get($url, $headers);

    /**
     * @param $url
     * @param $headers
     * @param $body
     * @return mixed
     */
    public function post($url, $headers, $body);

    /**
     * @param $url
     * @param $headers
     * @param $body
     * @return mixed
     */
    public function put($url, $headers, $body);
}
