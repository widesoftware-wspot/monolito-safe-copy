<?php

namespace Wideti\DomainBundle\Service\Deskbee;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Service\Module\ModuleAware;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client as GuzzleClient;



class DeskbeeService
{
    use EntityManagerAware;
    use TwigAware;
    use SecurityAware;
    use FlashMessageAware;
    use ModuleAware;
    use LoggerAware;


    private $guzzleClient;

    /**
     * DeskbeeService constructor.
     */
    public function __construct() {
        $this->baseUriSandbox = 'https://api-beta.deskbee.io/';
        $this->baseUriProduction = 'https://api.deskbee.io/';
        $this->guzzleClientConfig = [
            'defaults' => [
                'exceptions' => false,
            ],
            'headers' => ['Content-Type' => 'application/json']
        ];
    }

    public function checkinAction($guest, $accessPoint)
    {
        $client = $this->getLoggedClient();

        $clientDeskbeeModule = $this->moduleService->modulePermission('deskbee_integration', $client);
        if (!$clientDeskbeeModule) {
            return false;
        }

        $deskbeeIntegrationIsActive = $this->moduleService->checkModuleIsActive('deskbee_integration', $client);

        if (!$deskbeeIntegrationIsActive) {
            return false;
        }

        $apDevice = $accessPoint->getDeskbeeDevice();
        if (!$apDevice) {
            return false;
        }
        $device = $apDevice->getDevice();
        if (!$device) {
            return false;
        }
        $deskbeeEnv = $this->getModuleConfigurationValue($client, 'deskbee_environment');
        if ($deskbeeEnv == 'prod') {
            $this->guzzleClientConfig['base_uri'] = $this->baseUriProduction;
        } else {
            $this->guzzleClientConfig['base_uri'] = $this->baseUriSandbox;
        }

        $this->guzzleClient = new GuzzleClient($this->guzzleClientConfig);

        $email = $guest->getProperties()['email'];
        $token = $this->getToken($client)['access_token'];

        $checkinResponse = $this->checkin($token, $email, $device);
        $isCheckinCompleted = $this->getCheckinCompleted($checkinResponse);

        $shouldRedirectToDeskbee = false;

        if (!$isCheckinCompleted) {
            # Se o checkin não foi feito:
                # Analisa se não tem uma reserva em andamento - state busy
                # Verifica se há alguma reserva disponível no horário, considerando a tolerância
            # Se atender algum desses pontos, quer dizer que ou não tem reserva feita
            # ou houve algum erro ao tentar fazer checkin, então redireciona para o deskbee
            $shouldRedirectToDeskbee = true;
            $date = new \DateTime();
            $bookings = $this->searchBookingsForGuest($email, $token)['data'];
            $hasBusyBooking = false;
            $hasBookingReserved = false;
            foreach ($bookings as $booking) {

                if ($booking["state"] == "busy") {
                    $hasBusyBooking = true;
                } elseif ($booking["state"] == "reserved") {
                    $startDateStr = $booking["start_date"];
                    $minTolerance = $booking["min_tolerance"];
                    $startDate = new \DateTime($startDateStr);

                    $acceptableDate = $startDate->sub(new \DateInterval('PT' . $minTolerance . 'M'));
                    if ($acceptableDate < $date) {
                        $hasBookingReserved = true;
                    }
                }
            }

            $shouldRedirectToDeskbee = !$hasBusyBooking || $hasBookingReserved;
        }

        $redirectUrl = $this->getModuleConfigurationValue($client, 'deskbee_redirect_url');

        if ($shouldRedirectToDeskbee && $redirectUrl) {
            $this->session->set('redirectUrl', $redirectUrl);
        }
    }

    private function getModuleConfigurationValue($client, $key) {
        $moduleConfiguration = $this->em
        ->getRepository('DomainBundle:ModuleConfigurationValue')
        ->findByModuleConfigurationKey($client, $key);
        if ($moduleConfiguration) {
            return $moduleConfiguration->getValue();
        }
        return "";
    }

    private function getToken($client) {
        $clientSecret = $this->getModuleConfigurationValue($client, 'deskbee_client_secret');
        $clientId = $this->getModuleConfigurationValue($client, 'deskbee_client_id');
        $body = [
            "grant_type" => "client_credentials",
            "client_id" => $clientId,
            "client_secret" => $clientSecret,
            "scope" => "integration.checkin booking.show"
        ];
        try {
            $response = $this->guzzleClient
                ->post("v1.1/oauth/token", [
                  "body" => json_encode($body)
                ]
            );
            $jsonString = $response->getBody()->getContents();
            return json_decode($jsonString, true);
        }catch (RequestException $ex){
            $this->logger->addCritical($ex->getMessage());
        }
    }

    private function searchBookingsForGuest($email, $token) {
        try {
            $response = $this->guzzleClient
                ->get("v1.1/bookings?search=email:" . $email . "&include=checkin;min_tolerance", [
                    "headers" => ['Authorization' => 'Bearer ' . $token]
                ]
            );
            $jsonString = $response->getBody()->getContents();
            return json_decode($jsonString, true);
        }catch (RequestException $ex){
            $this->logger->addCritical($ex->getMessage());
        }
    }

    private function getCheckinCompleted($response) {
        if (isset($response['data']['fails']) && !empty($response['data']['fails'])) {
            $this->logger->addCritical(json_encode($response['data']['fails'], JSON_UNESCAPED_UNICODE));
            return false;
        }
        if (!$response) {
            return false;
        }
        return true;
    }

    private function checkin($token, $email, $device) {
        $date = new \DateTime();
        $body = [
          "date"      => $date,
          "device"    => $device,
          "person"    => $email,
          "entrance"  => 1
        ];
        try {
            $response = $this->guzzleClient
                ->post("v1.1/integrations/checkin", [
                    "body" => json_encode($body),
                    "headers" => ['Authorization' => 'Bearer ' . $token]
                ]
            );
            $jsonString = $response->getBody()->getContents();
            return json_decode($jsonString, true);
        }catch (RequestException $ex){
            $this->logger->addCritical($ex->getMessage());
        }

    }
}