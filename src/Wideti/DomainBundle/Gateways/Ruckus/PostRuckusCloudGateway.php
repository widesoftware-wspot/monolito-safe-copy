<?php


namespace Wideti\DomainBundle\Gateways\Ruckus;


use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;
use Wideti\DomainBundle\Helpers\Resilience\Resilience;
use Wideti\DomainBundle\Helpers\Resilience\RetryExceededException;

class PostRuckusCloudGateway
{

    public static function post($ruckusCloudHost, array $encodedMacBody, $locale = 'pt_BR', $headers = []) {

        $onError = function (ClientException $ex) {
            $statusCode = $ex->getResponse()->getStatusCode();
            return $statusCode == 400 || $statusCode == 409;
        };

        $body = $encodedMacBody;


        $uri = "/portalintf";
        try {
            $resilienceClient = Resilience::newClient($ruckusCloudHost)
                ->withRetry(3, 100)
                ->withTimeout(5000)
                ->addHeader("Accept-Language", $locale)
                ->addBody($body);

            foreach ($headers as $key => $value) {
            	$resilienceClient->addHeader($key, $value);
			}

            $result = $resilienceClient->doPOST($uri, $onError);

            return self::build($result);
        } catch (ClientException $e) {
            return RuckusResponse::create("", "", "")->withError($e);
        } catch (RetryExceededException $e) {
            return RuckusResponse::create("", "", "")->withError($e);
        }
    }

    /**
     * @param ResponseInterface $response
     * @return RuckusResponse
     */
    private static function build(ResponseInterface $response) {
        $data = json_decode($response->getBody()->getContents(), true);
        $ruckusResponse = RuckusResponse::create($data["ReplyMessage"], $data['ResponseCode'], $data['DEC-UE-IP']);
        return $ruckusResponse;
    }
}
