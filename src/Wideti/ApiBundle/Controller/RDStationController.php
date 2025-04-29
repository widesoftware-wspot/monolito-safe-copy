<?php

namespace Wideti\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use Doctrine\DBAL\DBALException;

use Wideti\ApiBundle\Exception\WrongDomainException;
use Wideti\ApiBundle\Helpers\ConverterHelper;
use Wideti\DomainBundle\Helpers\ClientHelper;
use Wideti\DomainBundle\Helpers\RedirectUrlHelper;
use Wideti\DomainBundle\Service\Client\ClientServiceAware;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\EmailConfigNas\EmailConfigNasAware;
use Wideti\DomainBundle\Service\Jaeger\TracerHeaders;
use Wideti\DomainBundle\Service\Mail\MailHeaderServiceAware;
use Wideti\DomainBundle\Service\Mailer\MailerServiceAware;
use Wideti\DomainBundle\Service\Mailer\Message\MailMessageBuilder;
use Wideti\DomainBundle\Service\RDStation\RDStationAware;
use Wideti\DomainBundle\Service\RDStation\RDStationService;
use Wideti\DomainBundle\Service\User\UserServiceAware;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\RouterAware;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use \DateTime;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\ApiBundle\Exception\DuplicatedDomainException;
use Wideti\ApiBundle\Exception\RdStationConversionNotFoundException;

class RDStationController implements ApiResource
{
    const CONVERSION_TYPE = 'quero-testar-o-mambo-wifi';

    use RouterAware;
    use ClientServiceAware;
    use LoggerAware;
    use EntityManagerAware;
    use MailerServiceAware;
    use MailHeaderServiceAware;
    use UserServiceAware;
    use RDStationAware;
    use TwigAware;
    use EmailConfigNasAware;

    const QUERO_TESTAR          = 'quero-testar-o-wspot-wifi';
    const QUERO_TESTAR_PASSO_1  = 'quero-testar-o-wspot-passo-1';

    /**
     * @var RedirectUrlHelper
     */
    private $urlHelper;
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var ClientHelper
     */
    private $clientHelper;

    /**
     * @param ConfigurationService $configurationService
     * @param ClientHelper $clientHelper
     */
    public function __construct(
        ConfigurationService $configurationService,
        ClientHelper $clientHelper
    ) {
        $this->urlHelper = new RedirectUrlHelper();
        $this->configurationService = $configurationService;
        $this->clientHelper = $clientHelper;
    }

    /**
     * Create New Client
     * @param Request $request
     * @return JsonResponse
     */
    public function createClientAction(Request $request)
    {
        $data = [];

        try {
            $data = $this->handleRdstationRequest($request);

            $converter = new ConverterHelper();
            $client = $converter->convertJsonToClient($data);
            $this->verifyDomain($data);
            $traceHeaders = TracerHeaders::from($request);
            $data['client_id'] = $this->createNewClient($client, $data, $traceHeaders);
            $this->createAdminUser($client, $data);

            try {
                $this->sendSalesTeamNotification($data);
            } catch (\Exception $e) {
                $this->logger->addCritical("Cliente {$data['domain']} criou painel, mas e-mail não foi enviado " .
                    "à equipe de vendas.", $e->getTrace());

                return new JsonResponse("Cliente {$data['domain']} criou painel, mas e-mail não foi enviado " .
                    "à equipe de vendas.", 500);
            }

            return new JsonResponse(["message" => "Cliente {$data['domain']} criado com sucesso"], 201);
        } catch (RdStationConversionNotFoundException $e) {
            $this->onErrorException($request, $data, $e);

            return new JsonResponse([
                "message" => $e->getMessage()
            ], 400);
        } catch (WrongDomainException $e) {
            $this->onErrorException($request, $data, $e);

            return new JsonResponse([
                "message" => $e->getMessage()
            ], 400);
        } catch(DBALException $e) {
            $this->onErrorException($request, $data, $e);

            return new JsonResponse([
                "message" => "Ocorreu um erro no banco de dados, a equipe responsável já foi notificada."
            ], 500);

        } catch (DuplicatedDomainException $e) {
            $this->onErrorException($request, $data, $e);

            return new JsonResponse([
                "message" => "Cliente '{$data['domain']}' já existe na base de dados."
            ], 409);

        } catch (\Exception $e) {
            $this->onErrorException($request, $data, $e);

            return new JsonResponse([
                "message" => "Cliente '{$data['domain']}' não pode ser criado. Nossa equipe já foi notificada."
            ], 500);
        }
    }

    /**
     * @param Client $client
     * @param $data
     * @return bool
     * @throws DBALException
     * @throws \Exception
     */
    private function createAdminUser(Client $client, $data)
    {
        $user           = new Users();
        $email          = trim($data['from_email']);
        $leadName       = trim($data['lead_name']);
        $role           = $this->em
            ->getRepository('DomainBundle:Roles')
            ->find(Users::ROLE_ADMIN);

        $user->setUsername($email);
        $user->setNome($leadName);
        $user->setStatus(Users::ACTIVE);
        $user->setReceiveReportMail(true);
        $user->setReportMailLanguage(0);
        $user->setFinancialManager(1);
        $user->setRole($role);
        $user->setClient($client);
        $user->setFinancialManager(true);

        $this->userService->register($user, true);

        return true;
    }

    /**
     * @param $data
     * @throws \Wideti\DomainBundle\Exception\SendEmailFailException
     */
    private function sendSalesTeamNotification($data)
    {

        $domain = $data['domain'];

        if (!strpos($domain, '.')) {
            $domain .= ".mambowifi.com";
        }

        $builder = new MailMessageBuilder();
        $message = $builder
            ->subject('Nova POC Criada - '. $data['company'])
            ->from(['API Mambo WiFi' => $this->emailHeader->getSender()])
            ->to($this->emailHeader->getCommercialRecipient())
            ->htmlMessage(
                $this->renderView(
                    'ApiBundle:Client:emailNovoCliente.html.twig',
                    [
                        'config'        => [
                            'partner_name' => 'Mambo WiFi'
                        ],
                        'conversion'    => $data['conversion'],
                        'client'        => $domain,
                        'panel'         => "https://demo.wspot.com.br/panel/client/{$data['client_id']}/edit/",
                        'lead'          => $data['lead_url']
                    ]
                )
            )
            ->build()
        ;

        $this->mailerService->send($message);
    }

    /**
     * @param $data
     * @throws \Wideti\ApiBundle\Exception\DuplicatedDomainException
     */
    private function verifyDomain($data)
    {
        $domain = $data['domain'];

        if ($domain) {
            $client     = $this->em->getRepository("DomainBundle:Client")->findOneBy(['domain' => $domain]);
            $isReserved = $this->clientHelper->checkIfIsReservedDomain($domain);

            if ($client || $isReserved) {
                throw new DuplicatedDomainException(
                    409,
                    "The domain \"{$domain}\" is no avaible for use.");
            }

            return;
        }
        throw new WrongDomainException(400, "Domínio não existe na request.");
    }

    /**
     * @param Client $client
     * @param $data
     * @return mixed
     * @throws DBALException
     * @throws \Exception
     */
    private function createNewClient(Client $client, $data, $traceHeaders = [])
    {
        $client->setPocEndDate(new DateTime('+7 days'));
        $client->setStatus(Client::STATUS_POC);

        $modules = $this->em
            ->getRepository('DomainBundle:Module')
            ->findBy([
                'shortCode' => ['campaign', 'blacklist', 'access_code','business_hours', 'api', 'customer_area', 'rd_station']
            ]);

        foreach ($modules as $module) {
            $client->addModule($module);
        }
        $additionalInfo = [
            'from_email'    => $data['from_email'],
            'redirect_url'  => $data['redirect_url'],
            'partner_name'  => $data['company']
        ];
        $this->clientService->create($client,$additionalInfo, $traceHeaders);

        return $client->getId();
    }

    /**
     * @param $lead
     * @return string
     */
    private function getRedirectUrl($lead)
    {
        if (isset($lead['first_conversion']['Site ou página em que o usuário será redirecionado após o login'])) {
            return $lead['first_conversion']['Site ou página em que o usuário será redirecionado após o login'];
        } elseif (isset($lead['last_conversion']['Site ou página em que o usuário será redirecionado após o login'])) {
            return $lead['last_conversion']['Site ou página em que o usuário será redirecionado após o login'];
        } elseif (isset($lead['custom_fields']['Site ou página em que o usuário será redirecionado após o login']) &&
                  !empty($lead['custom_fields']['Site ou página em que o usuário será redirecionado após o login'])) {
            return $lead['custom_fields']['Site ou página em que o usuário será redirecionado após o login'];
        } else {
            return 'https://www.google.com';
        }
    }

    /**
     * @param Request $request
     * @return array
     * @throws \Wideti\ApiBundle\Exception\RdStationConversionNotFoundException
     */
    private function handleRdstationRequest(Request $request)
    {
        $data               = json_decode($request->getContent(), true);
        $lead = $this->convertLeadKeysToLowerCase($data);

        $requiredFields = [
            'empresa',
            'email_lead',
            'identificador',
            'nome',
            'qual endereço web você deseja para seu mambowifi? (ex: empresa.mambowifi.com)'
        ];

        $lastConversion    = isset($lead['last_conversion']['content'])
            ? $lead['last_conversion']['content']
            : null;

        $firstConversion   = isset($lead['first_conversion']['content'])
            ? $lead['first_conversion']['content']
            : null;

        $redirectUrl = $this->getRedirectUrl($lead);

        if ($lastConversion && $this->parametersValidate($lastConversion, $requiredFields)) {
            return $this->extractValuesFromConversion($lastConversion, $lead, $redirectUrl);
        }

        if ($firstConversion && $this->parametersValidate($firstConversion, $requiredFields)) {
            return $this->extractValuesFromConversion($firstConversion, $lead, $redirectUrl);
        }

        $fieldsAsString = implode(", ", $requiredFields);
        throw new RdStationConversionNotFoundException(
            "Algum dos campos obrigatórios: {$fieldsAsString} não existem na conversão"
        );
    }

    /**
     * @param array $conversion
     * @param array $requiredFields
     * @return bool
     */
    private function parametersValidate(array $conversion, array $requiredFields)
    {
        $keys = array_keys($conversion);

        foreach ($requiredFields as $required) {
            if (!in_array($required, $keys)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $conversion
     * @param array $lead
     * @param $redirectUrl
     * @return array
     */
    private function extractValuesFromConversion(array $conversion, array $lead, $redirectUrl)
    {
        $domain = $this->extractDomainIfURL(
            $conversion['qual endereço web você deseja para seu mambo-wifi? (ex: empresa.mambowifi.com)']);
        return  [
            "company"       => $conversion['empresa'],
            "domain"        => $domain,
            'from_email'    => $conversion['email_lead'],
            "conversion"    => $conversion['identificador'],
            "lead_name"     => $conversion['nome'],
            "redirect_url"  => $redirectUrl,
            "lead_url"      => $lead['public_url']
        ];
    }

    /**
     * @param $url
     * @return string
     */
    private function extractDomainIfURL($url)
    {
        $domain = explode(".", $url);

        $text   = $domain[0];

        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '', $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '');

        // remove duplicate -
        $text = preg_replace('~-+~', '', $text);

        // remove http, https and www
        $text = str_replace('http', '', str_replace('https', '', str_replace('www', '', $text)));

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return '';
        }

        return $text;
    }

    public function getResourceName()
    {
        return "rdstation";
    }

    /**
     * @param Request $request
     * @param array $data
     * @param \Exception $exception
     */
    private function onErrorException(Request $request, array $data, \Exception $exception)
    {

        $this->logger->addCritical("MAMBOWIFI_RDSTATION_API: {$exception->getMessage()}", [
            'handleData' => $data,
            'rawData' => json_decode($request->getContent(), true)
        ]);

        $fromEmail = isset($data['from_email'])
            ? $data['from_email']
            : null;

        if ($fromEmail) {
            try {
                $this->rdStationService->insertTagLead($fromEmail, [
                    RDStationService::TAG_WSPOT_PROBLEMA_CRIACAO_AUTOMATICA
                ]);
            } catch (\Exception $e) {
                $this->logger->addCritical("MAMBOWIFI_RDSTATION_API: {$e->getMessage()}", [
                    'handleData' => $data,
                    'rawData' => json_decode($request->getContent(), true)
                ]);
            }
        }
    }

    /**
     * @param $data
     * @return array
     */
    private function convertLeadKeysToLowerCase($data)
    {
        $lead = array_change_key_case($data["leads"][0], CASE_LOWER);

        $firstConversion = isset($lead['first_conversion']['content'])
            ? array_change_key_case($lead['first_conversion']['content'], CASE_LOWER)
            : null;

        $lastConversion = isset($lead['last_conversion']['content'])
                ? array_change_key_case($lead['last_conversion']['content'], CASE_LOWER)
                : null;

        $customFields = isset($lead['custom_fields'])
            ? array_change_key_case($lead['custom_fields'], CASE_LOWER)
            : null;

        if ($firstConversion) {
            $lead['first_conversion']['content'] = $firstConversion;
        }

        if ($lastConversion) {
            $lead['last_conversion']['content'] = $lastConversion;
        }

        if ($customFields) {
            $lead['custom_fields'] = $customFields;
        }

        return $lead;
    }
}