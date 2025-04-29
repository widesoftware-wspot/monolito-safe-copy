<?php

namespace Wideti\DomainBundle\Service\Guest;

use Exception;
use Monolog\Logger;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Wideti\DomainBundle\Service\Queue\Message;
use PhpAmqpLib\Message\AMQPMessage;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;

class DisconnectGuestConsumer {

    /**
    * @var Logger
    */
    private $logger;

    /**
	  * @var AdminControllerHelper
	  */
	  private $controllerHelper;

    /**
    * DisconnectGuestConsumer constructor.
    * @param Logger $logger
    * @param AdminControllerHelper $controllerHelper
    */

    public function __construct(
      Logger $logger,
      AdminControllerHelper $controllerHelper
    ) {
        $this->logger = $logger;
        $this->controllerHelper = $controllerHelper;
    }

    public function execute(AMQPMessage $msg)
    {
        try {
						$pathLog = "/var/log/disconnect-guest.log";
						$bodyEncoded = $msg->getBody();
						$body = json_decode($bodyEncoded, true);
						file_put_contents(
							$pathLog,
							'Mensagem Recebida, data: ' . $bodyEncoded . PHP_EOL, FILE_APPEND
						);

						$publicIp =$body['appublicip'];
						$sessionId = $body['sessionid'];
						$framedIpAddress = $body['framedip'];
						$port = 3799;
						$host = 'radiusv3';

						if (!$sessionId || !$publicIp || !$framedIpAddress) {
							file_put_contents($pathLog, 'Não possui informação suficiente para a desconexão' . PHP_EOL, FILE_APPEND);
							return true;
						}

						$url = 'http://' . $host . ':8080/disconnect?' .
							'sessionId=' . urlencode($sessionId) . '&' .
							'serverAddress=' . urlencode($publicIp) . '&' .
							'framedIpAddress=' . urlencode($framedIpAddress) . '&' .
							'port=' . urlencode($port);

						file_put_contents($pathLog, "url: " . $url . PHP_EOL, FILE_APPEND);

						$response = file_get_contents($url);

						if ($response === false) {
								file_put_contents($pathLog, 'Não foi possível obter uma resposta da API.' . PHP_EOL, FILE_APPEND);
						} else {
								file_put_contents($pathLog, 'Resposta: ' . $response . PHP_EOL, FILE_APPEND);
						}

				} catch(Exception $e) {
						$message = 'Erro durante a desconexão do visitante ' . $body['identifier'] . ': ' . $e->getMessage();
						file_put_contents($pathLog, $message . PHP_EOL, FILE_APPEND);
				}

				file_put_contents($pathLog, 'Finalizou desconexão do visitante' . $body['identifier'] . PHP_EOL, FILE_APPEND);
				return true;
    }
}
