<?php


namespace Wideti\DomainBundle\Gateways\Survey;


use FontLib\Table\Type\loca;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\Resilience\Resilience;
use Wideti\DomainBundle\Helpers\Resilience\RetryExceededException;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;

class GetSurveyGateway
{
    use LoggerAware;

    private $surveyAnswerHost;

    /**
     * ListSurveyAnswer constructor.
     * @param $surveyAnswerHost
     */
    public function __construct($surveyAnswerHost)
    {
        $this->surveyAnswerHost = $surveyAnswerHost;
    }

    public function get(Client $client, Guest $guest, $apGroupId, $locale = 'pt_br', $headers = []) {


        $onError = function (ClientException $ex) {
            $statusCode = $ex->getResponse()->getStatusCode();
            return $statusCode == 400 || $statusCode == 404;
        };

        $uri = "api/v1/guests/".$guest->getMysql()."/client/" . $client->getId() . "?visits={$guest->getCountVisits()}&ap_group_id={$apGroupId}&locale={$locale}";

        try {
			$resilienceClient = Resilience::newClient($this->surveyAnswerHost)
				->withRetry(3, 100)
				->withTimeout(500)
				->addHeader("Accept-Language", $locale);

			foreach ($headers as $key => $value) {
				$resilienceClient->addHeader($key, $value);
			}

            $result = $resilienceClient->doGET($uri, $onError);
            return $this->build($result);
        } catch (ClientException $e) {
            $this->logger->warning("Client error on retrieve survey answer validation from microservice: " . $e->getMessage(), $e->getHandlerContext());
            return SurveyResponse::create("")->withError($e);
        } catch (RetryExceededException $e) {
            $this->logger->error("Fail on retrieve survey answer validation from microservice: " . $e->getMessage(), $e->getTrace());
            return SurveyResponse::create("")->withError($e);
        }
    }

    /**
     * @param ResponseInterface $response
     * @return SurveyResponse
     */
    private function build(ResponseInterface $response) {
        $data = json_decode($response->getBody()->getContents(), true);
        return SurveyResponse::create($data["survey_id"])
            ->withShowSurvey($data['show_survey']);
    }
}
