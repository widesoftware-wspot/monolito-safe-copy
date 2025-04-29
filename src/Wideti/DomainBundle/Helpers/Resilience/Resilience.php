<?php


namespace Wideti\DomainBundle\Helpers\Resilience;

use Exception;
use GuzzleHttp\Exception\ClientException;
use InvalidArgumentException;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class Resilience
{
    /**
     * @var int
     */
    private $timeout;
    /**
     * @var int
     */
    private $nRetry;
    /**
     * @var int
     */
    private $delay;
    /**
     * @var string[]
     */
    private $headers;

    /**
     * @var string[]
     */
    private $body;

    /**
     * @var Client
     */
    private $http;

    private function __construct(Client $http)
    {
        $this->headers = [];
        $this->timeout = -1;
        $this->nRetry = -1;
        $this->delay = -1;
        $this->http = $http;
        $this->body = [];
    }

    public static function newClient($basePath)
    {
        return new Resilience(new Client(['base_uri' => $basePath]));
    }

    /**
     * @param int $retries
     * @param int $delayMs
     * @return Resilience
     */
    public function withRetry($retries, $delayMs)
    {
        if ($retries <= 0 || $delayMs <= 0) {
            throw new InvalidArgumentException("retry and delay can't be negative or zero");
        }
        $this->nRetry = $retries;
        $this->delay = $delayMs;
        return $this;
    }

    /**
     * @param int $timeoutMs
     * @return Resilience
     */
    public function withTimeout($timeoutMs)
    {
        if ($timeoutMs <= 0) {
            throw new InvalidArgumentException("Timeout can't be negative or zero");
        }
        $this->timeout = $timeoutMs;
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return Resilience
     */
    public function addHeader($key, $value)
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return Resilience
     */
    public function addBody($value)
    {
        $this->body = $value;
        return $this;
    }

    /**
     * @param $uri
     * @param $onError
     * @return mixed|ResponseInterface
     * @throws RetryExceededException
     * @throws ClientException
     */
    public function doGET($uri, $onError)
    {

        $action = function () use ($uri) {
            return $this->get($uri);
        };

        if ($this->nRetry > 0) {
            return $this->retry($action, $this->nRetry, $this->delay, $onError);
        } else {
            return $this->get($uri);
        }
    }

    /**
     * @param $uri
     * @param $onError
     * @return mixed|ResponseInterface
     * @throws RetryExceededException
     * @throws ClientException
     */
    public function doPOST($uri, $onError)
    {

        $action = function () use ($uri) {
            return $this->post($uri);
        };

        if ($this->nRetry > 0) {
            return $this->retry($action, $this->nRetry, $this->delay, $onError);
        } else {
            return $this->post($uri);
        }
    }

    /**
     * @param $uri
     * @param $onError
     * @return mixed|ResponseInterface
     * @throws RetryExceededException
     * @throws ClientException
     */
    public function doDELETE($uri, $onError)
    {

        $action = function () use ($uri) {
            return $this->delete($uri);
        };

        if ($this->nRetry > 0) {
            return $this->retry($action, $this->nRetry, $this->delay, $onError);
        } else {
            return $this->delete($uri);
        }
    }

    /**
     * @param string $uri
     * @return ResponseInterface
     */
    private function get($uri)
    {

        $options = [];
        if (!empty($this->headers)) {
            $options["headers"] = $this->headers;
        }
        if ($this->timeout > 0) {
            $options['timeout'] = ($this->timeout / 1000);
        }

        return $this->http->get($uri, $options);
    }


    /**
     * @param string $uri
     * @return ResponseInterface
     */
    private function post($uri)
    {

        $options = [];
        if (!empty($this->headers)) {
            $options["headers"] = $this->headers;
        }

        if (!empty($this->body)) {
            $options["json"] = $this->body;
        }
        if ($this->timeout > 0) {
            $options['timeout'] = ($this->timeout / 1000);
        }
        return $this->http->post($uri, $options);
    }

    /**
     * @param string $uri
     * @return ResponseInterface
     */
    private function delete($uri)
    {

        $options = [];
        if (!empty($this->headers)) {
            $options["headers"] = $this->headers;
        }

        if (!empty($this->body)) {
            $options["json"] = $this->body;
        }
        if ($this->timeout > 0) {
            $options['timeout'] = ($this->timeout / 1000);
        }

        return $this->http->delete($uri, $options);
    }

    /**
     * @param $execFunction
     * @param int $retries
     * @param $delayMS
     * @param $mustAbort
     * @return ResponseInterface
     * @throws RetryExceededException
     * @throws ClientException
     */
    private function retry($execFunction, $retries, $delayMS, $mustAbort)
    {
        try {
            return $execFunction();
        } catch (Exception $e) {
            $newRetry = $retries - 1;
            if ($newRetry == 0) {
                throw new RetryExceededException($e->getMessage(), $e->getCode(), $e);
            }

            if ($e instanceof ClientException) {
                $break = $mustAbort($e);
                if ($break) {
                    throw $e;
                }
            }

            self::msleep($delayMS);
            return self::retry($execFunction, $newRetry, $delayMS, $mustAbort);
        }
    }

    /**
     * Delays execution of the script by the given time.
     * @param mixed $time in MS
     */
    private static function msleep($time)
    {
        usleep($time * 1000);
    }
}
