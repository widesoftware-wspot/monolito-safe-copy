<?php

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Question\Question;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\DateTimeHelper;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Repository\ClientRepository;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsService;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\GuestToAccountingProcessor;

use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Builder\GuestBuilder;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Builder\RequestBuilder;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Dto\AccessDataDto;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Dto\SocialDto;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$input  = new ArgvInput([]);
$output = new ConsoleOutput();
$em     = $container->get('doctrine.orm.entity_manager');
$mongo  = $container->get('doctrine.odm.mongodb.document_manager');

$clientRepository = $container->get('core.repository.client');
$sendToQueueService = $container->get('core.service.guest_to_acct_processor_send');
$customFieldsService = $container->get('core.service.custom_fields');
$guestDeviceService = $container->get('core.service.guest_devices');
$monolog = $container->get('logger');

const ONE_CLIENT = '1';
const ALL_CLIENTS = '2';
const DATE_FORMAT = 'Y-m-d H:i:s';
const CONFIRM_POSITIVE = "yes";
const CONFIRM_NEGATIVE = "no";

// Styles do output
$optionTextStyle = new OutputFormatterStyle('yellow', null, array('bold'));
$output->getFormatter()->setStyle('option', $optionTextStyle);

$optionErrorStyle = new OutputFormatterStyle('red',null, array('bold'));
$output->getFormatter()->setStyle('optionError', $optionErrorStyle);

$bannerStyle = new OutputFormatterStyle('black', 'cyan');
$output->getFormatter()->setStyle('banner', $bannerStyle);

$optionsSelectedStyle = new OutputFormatterStyle('green');
$output->getFormatter()->setStyle('optionState', $optionsSelectedStyle);


// Renderiza a tela
$screen = new RenderScreen(
    $output,
    $input,
    new Options(),
    $clientRepository,
    $em,
    $mongo,
    $sendToQueueService,
    $customFieldsService,
    $guestDeviceService
);

$screen->render();


class Assert {
    public static function isValidMode($mode, $onError) {
        $isValid = in_array($mode, [ ONE_CLIENT, ALL_CLIENTS ]);

        if (!$isValid) {
            $onError();
        }

        return $isValid;
    }

    public static function domainExists($domain, ClientRepository $clientRepository, $onError) {
        $client = $clientRepository->findBy(['domain' => $domain]);

        if (empty($client)) {
            $onError();
        }

        return !empty($client);
    }

    public static function isValidDate($dateStr, $onError) {
        $date = \DateTime::createFromFormat(DATE_FORMAT, $dateStr);

        if (!$date) {
            $onError();
            return false;
        }

        return true;
    }

    public static function isValidConfirmation($confirmation, $onError)
    {
        $isValid = in_array(strtolower($confirmation), [CONFIRM_POSITIVE, CONFIRM_NEGATIVE]);
        if (!$isValid) {
            $onError();
        }
        return $isValid;
    }
}

class RenderScreen {

    private $input;
    private $output;
    private $options;
    private $questionHelper;
    private $mongo;
    private $clientRepository;
    /**
     * @var GuestToAccountingProcessor
     */
    private $sendToQueueService;
    /**
     * @var CustomFieldsService
     */
    private $customFieldsService;
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;
    /**
     * @var GuestDevices
     */
    private $guestDevices;

    /**
     * RenderScreen constructor.
     * @param ConsoleOutput $output
     * @param ArgvInput|null $input
     * @param Options $options
     * @param ClientRepository $clientRepository
     * @param \Doctrine\ORM\EntityManager $em
     * @param DocumentManager $mongo
     * @param GuestToAccountingProcessor $sendToQueueService
     * @param CustomFieldsService $customFieldsService
     * @param GuestDevices $guestDevices
     */
    public function __construct(
        ConsoleOutput $output,
        ArgvInput $input = null,
        Options $options,
        ClientRepository $clientRepository,
        \Doctrine\ORM\EntityManager $em,
        DocumentManager $mongo,
        GuestToAccountingProcessor $sendToQueueService,
        CustomFieldsService $customFieldsService,
        GuestDevices $guestDevices
    ) {
        $this->input = $input;
        $this->output = $output;
        $this->options = $options;
        $this->questionHelper = new QuestionHelper();
        $this->clientRepository = $clientRepository;
        $this->em = $em;
        $this->mongo = $mongo;
        $this->sendToQueueService = $sendToQueueService;
        $this->customFieldsService = $customFieldsService;
        $this->guestDevices = $guestDevices;
    }

    public function render() {
        // Console Questions
        $question = new Question('<info>></info>', null);

        if (empty($this->options->getMode())) {
            $this->cleanTerminal()
                ->renderHeader()
                ->askModeQuestion($question);
        }

        if (empty($this->options->getClientDomain()) && $this->options->getMode() == ONE_CLIENT) {
            $this->cleanTerminal()
                ->renderHeader()
                ->askDomainQuestion($question);
        }

        if (empty($this->options->getFrom())) {
            $this->cleanTerminal()
                ->renderHeader()
                ->askDateQuestion($question, "Data que o visitante foi criado (created): FROM (ex.: 2019-03-02 20:30:00)", function(DateTime $from) {
                    $this->options->addFrom($from);
                });
        }

        if (empty($this->options->getTo())) {
            $this->cleanTerminal()
                ->renderHeader()
                ->askDateQuestion($question, "Data que o visitante foi criado (created): TO (ex.: 2019-03-02 20:30:00)", function(DateTime $to) {
                    $this->options->addTo($to);
                });
        }

        if (!$this->options->isDateFromSmallerThanTo()) {
            $this->cleanTerminal()
                ->renderHeader()
                ->printError('Data "FROM" maior ou igual a data "TO", entre novamente com as datas!')
                ->askToContinue($question, function() {
                    $this->options->clearDates();
                });
        }

        if (!$this->options->getConfirm()) {
            $this
                ->cleanTerminal()
                ->renderHeader()
                ->askConfirmation($question, function($confirmation) {

                    $confirmation == CONFIRM_POSITIVE
                        ? $this->processPositive()
                        : $this->processNegative();
                });
        }

        if (!$this->options->isFinished()) {
            $this->render();
        }

        exit(0);
    }

    private function processNegative() {
        $this->options->cleanState();
    }

    private function processPositive() {

        $mode = $this->options->getMode();

        if ($mode === ONE_CLIENT) {
            $this->sendOneClient();
        } else if ($mode === ALL_CLIENTS) {
            $this->sendAllClients();
        } else {
            $this->output->writeln("<optionError>Modo de envio inválido</optionError>");
        }

        $this->output->writeln("");
        $this->options->addIsFinished(true);
    }

    private function sendAllClients() {

        /**
         * @var Client[] $clients
         */
        $clients = $this
                ->clientRepository
                ->findAll();

        if (empty($clients)) {
            $this->printError("Não existem clientes para serem processados");
        }

        foreach ($clients as $client) {
            $this->sendGuests($client, $this->options->getFrom(), $this->options->getTo());
        }

    }

    private function sendOneClient() {

        $domain = $this->options->getClientDomain();

        /**
         * @var Client|null $client
         */
        $client = $this
            ->clientRepository
            ->findOneBy([
                'domain' => $domain
            ]);

        if (empty($client)) {
            $this->output->writeln("<optionError>Cliente \"{$domain}\" nao encontrado</optionError>");
            exit(1);
        }

        $this->sendGuests($client, $this->options->getFrom(), $this->options->getTo());
    }

    private function sendGuests(Client $client, \DateTime $from, \DateTime $to) {

        $domain = $client->getDomain();

        $this->breakLine();
        $this->output->writeln("<info>[Enviando cliente: {$domain}]</info>");

        $guests = $this->findGuests($domain, $from, $to);
        $totalGuests = $guests->count();

        if ($totalGuests == 0) {
            $this->output->writeln("<optionError>Cliente \"{$domain}\" não possui visitantes</optionError>");
            return;
        }

        $this->output->writeln("<info>[Guests a serem enviados: {$totalGuests}]</info>");

        $index = 0;
        $progressBar = new ProgressBar($this->output);
        foreach ($guests as $guest) {
            $builder = new GuestBuilder();

            $emailIsValidDate = isset($guest['emailIsValidDate'])
                ? $this->getTimeInMilliseconds($guest['emailIsValidDate'])
                : null;

            $lastAccess = isset($guest['lastAccess'])
                ? $this->getTimeInMilliseconds($guest['lastAccess'])
                : null;

            $createdDate = $this->getTimeInMilliseconds($guest['created']);

            $group = isset($guest['group'])
                ? $guest['group']
                : 'guest';

            $registerMode = isset($guest['registerMode'])
                ? $guest['registerMode']
                : null;

            $guestDto = $builder
                ->withClientId($client->getId())
                ->withId($guest['mysql'])
                ->withMongoId((string) $guest['_id'])
                ->withGroup($group)
                ->withStatus($guest['status'])
                ->withEmailIsValid($guest['emailIsValid'])
                ->withEmailIsValidDate($emailIsValidDate)
                ->withRegisterMode($registerMode)
                ->withLocale($guest['locale'])
                ->withDocumentType($guest['documentType'])
                ->withRegistrationMacAddress($guest['registrationMacAddress'])
                ->withCreated($createdDate)
                ->withLastAccess($lastAccess)
                ->withTimezone($guest['timezone'])
                ->withAccessData($this->getAccessData($guest))
                ->withSocial($this->getSocial($guest))
                ->withProperties($this->getProperties($guest))
                ->withLoginField($this->getLoginFieldIdentifier($domain))
                ->build();

            $requestBuilder = new RequestBuilder();
            $objectToSend = $requestBuilder
                ->withOperation(RequestBuilder::PERSIST)
                ->withGuest($guestDto)
                ->build();

            $this->sendToQueueService->send($objectToSend);

            $index++;
            $progressBar->progressBar($index, $totalGuests);
        }

        $this->breakLine();
    }


    private function getLoginFieldIdentifier($clientDomain)
    {
        $mongoClient = $this
            ->mongo
            ->getConnection()
            ->getMongoClient();

        $database = StringHelper::slugDomain($clientDomain);
        $fieldsCollection = $mongoClient
            ->selectDB($database)
            ->selectCollection('fields');

        $field = $fieldsCollection->findOne([
            'isLogin' => true
        ]);

        return isset($field['identifier'])
            ? $field['identifier']
            : null;
    }

    private function getProperties($guest)
    {
        if (!isset($guest['properties']) || empty($guest['properties'])) return [];

        $properties = $guest['properties'];

        foreach ($guest['properties'] as $key => $value) {
            if ($value == null) {
                $properties[$key] = "";
            }

            if ($key == 'data_nascimento' && gettype($value) != 'string') {
                $properties['data_nascimento'] = date('Y-m-d', $value->sec);
            }
        }

        return $properties;
    }

    private function getSocial($guest)
    {
        $social = [];

        if (!isset($guest['social']) && empty($guest['social'])) {
            return $social;
        }

        foreach ($guest['social'] as $item) {
            $dto = new SocialDto();
            $dto->setId($item['id']);
            $dto->setType($item['type']);
            array_push($social, $dto->jsonSerialize());
        }

        return $social;
    }

    private function getAccessData($guest)
    {
        $guestMysql = $this->em->getRepository("DomainBundle:Guests")->findOneBy(['id' => $guest['mysql']]);
        $devices = $this->guestDevices->getDevices($guestMysql);

        $accessData = [];

        foreach ($devices as $item) {
            $device = $this->adjustObjectToAcctProcessor($item);

            $dto = new AccessDataDto();
            $dto->setOs($device['os']);
            $dto->setPlatform($device['platform']);
            $dto->setMacAddress($device['macAddress']);
            $dto->setAccessDate($device['accessDate']);

            array_push($accessData, $dto->jsonSerialize());
        }

        return $accessData;
    }

    private function getTimeInMilliseconds($dateObject)
    {
        if ($dateObject instanceof \MongoDate) {
            $timestamp = $dateObject->sec;
            return DateTimeHelper::secondsToMilleseconds($timestamp);
        }

        if ($dateObject instanceof \DateTime) {
            $timestamp = $dateObject->getTimestamp();
            return DateTimeHelper::secondsToMilleseconds($timestamp);
        }

        return null;
    }

    private function findGuests($clientDomain, \DateTime $from, \DateTime $to) {
        $mongoClient = $this
            ->mongo
            ->getConnection()
            ->getMongoClient();

        $clientDomain = StringHelper::slugDomain($clientDomain);
        $collectionGuests = $mongoClient
            ->selectDB($clientDomain)
            ->selectCollection('guests');

        $query = [
            '$and' => [
                [
                    'created' => [
                    '$gte' => new MongoDate($from->getTimestamp()),
                    '$lte' => new MongoDate($to->getTimestamp()),
                    ]
                ],
                [
                    'registerMode' => [
                        '$ne' => 'API'
                    ]
                ]
            ]

        ];

        return $collectionGuests->find($query);
    }

    private function renderHeader() {
        return $this->helloBanner()
                    ->breakLine()
                    ->renderOptions();
    }

    private function printError($message) {
        $this->output->writeln("<optionError>{$message}</optionError>");
        $this->output->writeln("");
        $this->output->writeln("");
        return $this;
    }

    private function askConfirmation(Question $question, $onConfirm) {
        $this->output->writeln("<option>Digite YES para iniciar o processamento ou NO para reinserir os parâmetros</option>");
        do {
            $confirmation = $this->questionHelper->ask($this->input, $this->output, $question);
            $this->options->addConfirm($confirmation);
        } while(!Assert::isValidConfirmation($confirmation, function() {
            $this->output->writeln("<optionError>Confirmação precisa ser YES/NO</optionError>");
        }));
        $onConfirm($confirmation);
    }

    private function askToContinue(Question $question, $onContinue) {
        $this->output->writeln("<option>Aperte ENTER para continuar</option>");
        $this->questionHelper->ask($this->input, $this->output, $question);
        $onContinue();
    }

    public function helloBanner() {
        $this->output->writeln("<banner> ########################################### </banner>");
        $this->output->writeln("<banner> ### Reenvio de visitantes para RabbitMQ ### </banner>");
        $this->output->writeln("<banner> ########################################### </banner>");
        $this->breakLine();
        $this->output->writeln("<optionError> *** Este script não envia visitantes com \"registerMode: API\" *** </optionError>");
        $this->breakLine();
        return $this;
    }

    public function breakLine() {
        $this->output->writeln(" ");
        return $this;
    }

    public function cleanTerminal() {
        system('clear');
        return $this;
    }

    public function askModeQuestion(Question $question) {
        $this->output->writeln("<option>Qual modo de execução deseja?</option>");
        $this->output->writeln("<option>-----------------------------</option>");
        $this->output->writeln("<option>1) Cliente específico </option>");
        $this->output->writeln("<option>2) Todos Clientes </option>");

        do {
            $mode = $this->questionHelper->ask($this->input, $this->output, $question);
            $this->options->addMode($mode);
        } while(!Assert::isValidMode($mode, function() {
            $this->output->writeln("<optionError>Modo inválido</optionError>");
        }));
    }

    public function askDomainQuestion(Question $question) {
        $this->output->writeln("<option>Qual domínio deseja</option>");

        do {
            $domain = $this->questionHelper->ask($this->input, $this->output, $question);
            $this->options->addClientDomain($domain);
        } while(!Assert::domainExists($domain, $this->clientRepository,  function() {
            $this->output->writeln("<optionError>Domínio não exsite</optionError>");
        }));
    }

    public function askDateQuestion(Question $question, $label, $onSuccess) {
        $this->output->writeln("<option>$label</option>");

        do {
            $dateStr = $this->questionHelper->ask($this->input, $this->output, $question);
        } while(!Assert::isValidDate($dateStr, function() use ($dateStr) {
            $this->output->writeln("<optionError>Data \"{$dateStr}\" inválida </optionError>");
        }));

        $dateTime = \DateTime::createFromFormat(DATE_FORMAT, $dateStr);

        $onSuccess($dateTime);
    }

    public function renderOptions() {
        $mode = $this->options->getModeLabel() ?: '-';
        $domain = $this->options->getClientDomain() ?: '-';

        $from = $this->options->getFrom();
        $fromLabel = '-';

        if ($from) {
            $fromLabel = $from->format(DATE_FORMAT);
        }

        $to = $this->options->getTo();
        $toLabel = '-';

        if ($to) {
            $toLabel = $to->format(DATE_FORMAT);
        }

        $this->output->writeln("<optionState> Modo selecionado | {$mode}</optionState>");
        $this->output->writeln("<optionState> Domain           | {$domain}</optionState>");
        $this->output->writeln("<optionState> From             | {$fromLabel}</optionState>");
        $this->output->writeln("<optionState> To               | {$toLabel}</optionState>");
        $this->breakLine();

        return $this;
    }
}

class Options {
    private $mode;
    private $clientDomain;
    private $confirm;
    private $from;
    private $to;
    private $finished = false;

    public function addMode($mode) {
        $this->mode = $mode;
    }

    public function addClientDomain($clientDomain) {
        $this->clientDomain = $clientDomain;
    }

    public function addConfirm($confirm) {
        $this->confirm = $confirm;
    }

    public function addFrom(\DateTime $from) {
        $this->from = $from;
    }

    public function addTo(\DateTime $to) {
        $this->to = $to;
    }

    public function addIsFinished($finished) {
        $this->finished = $finished;
    }

    public function isFinished() {
        return (bool) $this->finished;
    }

    /**
     * @return mixed
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @return mixed
     */
    public function getModeLabel()
    {
        if($this->mode === ONE_CLIENT) return "Cliente específico";
        if($this->mode === ALL_CLIENTS) return "Todos Clientes";

        return null;
    }

    /**
     * @return mixed
     */
    public function getClientDomain()
    {
        return $this->clientDomain;
    }

    /**
     * @return mixed
     */
    public function getConfirm()
    {
        return $this->confirm;
    }

    /**
     * @return \DateTime
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @return mixed
     */
    public function getTo()
    {
        return $this->to;
    }

    public function isDateFromSmallerThanTo()
    {
        return $this->from < $this->to;
    }

    public function clearDates()
    {
        $this->from = null;
        $this->to = null;
    }

    public function cleanState() {
        $this->mode = null;
        $this->clientDomain = null;
        $this->from = null;
        $this->to = null;
        $this->finished = false;
    }
}

class ProgressBar {
    private $bar = "";
    private $lastPercent = 0;

    /**
     * @var ConsoleOutput
     */
    private $output;

    public function __construct(ConsoleOutput $output) {
        $this->output = $output;
    }

    public function progressBar($index, $total) {
        $progress = round(($index * 100)/$total);
        $totalBars = $progress - $this->lastPercent;
        $this->lastPercent = $progress;
        $this->cleanBar();

        for ($progressBar = 0; $progressBar < $totalBars; $progressBar++ ) {
            $this->bar = $this->bar . "|";
        }
        $this->output->write("<info>$this->bar[{$progress}%]</info>");
    }

    private function cleanBar() {
        $this->bar = "";
    }
}
