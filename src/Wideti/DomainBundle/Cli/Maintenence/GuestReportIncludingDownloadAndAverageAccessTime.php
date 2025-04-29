<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;
use Wideti\DomainBundle\Repository\Elasticsearch\Radacct\RadacctRepository;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Helpers\FileUpload;

require_once __DIR__ . "/../../../../../app/bootstrap.php.cache";
require_once __DIR__ . "/../../../../../app/AppKernel.php";

define("BUCKET", "uploads.wspot.com.br", true);
define("STORAGE_FOLDER", "guest-reports", true);
define("SKIP_NUMBER", 1000, true);

$kernel = new AppKernel("prod", true);
$kernel->boot();

$application       = new Application($kernel);
$container         = $application->getKernel()->getContainer();
$output            = new ConsoleOutput();
$em                = $container->get("doctrine")->getEntityManager("default");
$mongo             = $container->get('doctrine.odm.mongodb.document_manager');
$elasticSearch     = new ElasticSearch($container->getParameter("elastic_hosts"));
$radacctRepository = new RadacctRepository($elasticSearch);
$fileUpload        = new FileUpload($container->getParameter("aws_key"), $container->getParameter("aws_secret"), $container->getParameter("aws_bucket_name"), null);

if (!isset($argv[1])) {
    $output->writeln("<error>Domain do cliente deve ser informado.</error>");
    exit;
}

$client = getClientByScriptParameter($em, $argv[1]);

if (!$client) {
    $output->writeln("<error>ID do cliente não foi encontrado na base de dados.</error>");
    exit;
}

$output->writeln("<info>Cliente: {$client->getDomain()}</info>");

$accessPoints = getAccessPointsByClient($em, $client);

if (!$accessPoints) {
    $output->writeln("<error>Cliente não possui pontos de acesso ativos.</error>");
    exit;
}

$mongoClient    = $mongo->getConnection()->getMongoClient();
$clientDatabase = StringHelper::slugDomain($client->getDomain());
$database       = $mongoClient->$clientDatabase;

$criteria = [];

if (isset($argv[2])) {
    if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $argv[2])) {
        $output->writeln("<error>Formato de data inválido! Formato: YYYY-mm-dd</error>");
        exit;
    }

    $criteria = [
        'created' => [
            '$gte' => new \MongoDate(@strtotime("{$argv[2]} 00:00:00")),
            '$lte' => new \MongoDate(@strtotime("{$argv[3]} 23:59:59")),
        ]
    ];
}

$totalGuests = $database->guests->find($criteria)->count();
$skip        = 0;
$processed   = 0;
$guest       = new Guest();
$domain      = StringHelper::slugDomain($client->getDomain());
$fileName    = "{$domain}_apData_" . str_replace("-", "", date("Y-m-d")) . ".csv";
$auxFile     = @getcwd() . "/aux" . str_replace("-", "", date("Y-m-d")) . ".txt";
$filePath    = @getcwd() . "/{$fileName}";

$output->writeln("<info>TOTAL DE VISITANTES A SER PROCESSADO: {$totalGuests}</info>");

if (is_file($auxFile)) {
    $skip = $processed = (int) file_get_contents($auxFile);
} else {
    $file = @fopen($fileName, "a+");
    @fwrite($file, "E-mail;Nome;Sobrenome;Sexo;Telefone;CPF;CEP;Data de Nascimento;Grupo;Ponto de Acesso;Registrado em;" .
        "Último Acesso;Quantidade de Visitas;Download;Upload;Tempo Médio de Acesso;Cadastrou via\n");
    @fclose($file);
}

while ($skip <= $totalGuests) {
    $guests = $database->guests->find($criteria)->skip($skip);

    $file = @fopen($fileName, "a+");

    foreach ($guests as $guestData) {
        if ($processed > ($skip + SKIP_NUMBER)) {
            break;
        }

        $output->writeln("<comment>Visitante {$processed} processado</comment>");
        convertToEntity($guest, $guestData, $argv[1]);

        $properties         = $guest->getProperties();
	    $email              = $properties["email"];
	    $name               = $properties["name"];
	    $lastName           = $properties["last_name"];
	    $gender             = $properties["gender"];
	    $phone              = $properties["phone"];
	    $document           = $properties["document"];
	    $zipCode            = $properties["zip_code"];
        $birthDate          = ($properties["data_nascimento"] != "-") ? date("d/m/Y H:i:s", $properties["data_nascimento"]->sec) : "-";
        $group              = getGroupName($mongo, $guest);
	    $registrationApName = getRegistrationApName($em, $guest->getRegistrationMacAddress());
	    $created            = ($guest->getCreated() != "-") ? date("d/m/Y H:i:s", $guest->getCreated()->sec) : "-";
	    $lastAccess         = ($guest->getLastAccess() != "-") ? date("d/m/Y H:i:s", $guest->getLastAccess()->sec) : "-";
	    $registerMode       = $guest->getRegisterMode();

        try {
            $amountOfVisits = getAmountOfVisits($guest, $radacctRepository);
        } catch (\Exception $exception) {
            $amountOfVisits = 0;
        }

        try {
            $download = $upload = 0;
            getDownloadUploadRates($guest, $radacctRepository, $download, $upload);

            $download = convertByteToGBorMB($download);
            $upload = convertByteToGBorMB($upload);
        } catch (\Exception $exception) {
            $download = $upload = convertByteToGBorMB(0);
        }

        try {
            $averageTime = getAverageTime($guest, $radacctRepository);
        } catch (\Exception $exception) {
            $averageTime = applyTimeFormat(0);
        }

        @fwrite($file, "{$email};{$name};{$lastName};{$gender};{$phone};{$document};{$zipCode};{$birthDate};" .
            "{$group};{$registrationApName};{$created};{$lastAccess};{$amountOfVisits};{$download};{$upload};{$averageTime};{$registerMode};\n");

        $processed++;

        $aux = fopen($auxFile, "w+");
        @fwrite($aux, $processed);
        @fclose($aux);
    }

    @fclose($file);
    $skip += SKIP_NUMBER;
}

$transferLog = sendToStorage($fileName, $filePath, $fileUpload);
$output->writeln("<comment>{$transferLog}</comment>");
@unlink($filePath);
@unlink($auxFile);

/**
 * @param Guest $guest
 * @param RadacctRepository $radacctRepository
 * @return string
 * @throws Exception
 */
function getAverageTime(Guest $guest, RadacctRepository $radacctRepository) {
    $averageTime     = 0;
    $averageTimeData = $radacctRepository->getAverageTimeAccessByGuest($guest);

    if ($averageTimeData) {
        $totalSeconds   = $averageTimeData['aggregations']['total_access_time_in_seconds']['value'];
        $averageSeconds = ($averageTimeData['hits']['total'] == 0) ? 1 : $averageTimeData['hits']['total'];
        $averageTime    = (abs(intval(substr($totalSeconds, 0, -3) / $averageSeconds)));
    }

    return applyTimeFormat($averageTime);
}

/**
 * @param Guest $guest
 * @param RadacctRepository $radacctRepository
 * @param $download
 * @param $upload
 */
function getDownloadUploadRates(Guest $guest, RadacctRepository $radacctRepository, &$download, &$upload) {
    $downloadUpload = $radacctRepository->getDownloadUploadByGuest($guest, 'download', 'upload');
    countDownloadUploadRates($downloadUpload, $download, $upload);
}

/**
 * @param $downloadUpload
 * @param $download
 * @param $upload
 */
function countDownloadUploadRates($downloadUpload, &$download, &$upload) {
    if ($downloadUpload) {
        $download += $downloadUpload["download"]["value"];
        $upload   += $downloadUpload["upload"]["value"];
    }
}

/**
 * @param Guest $guest
 * @param RadacctRepository $radacctRepository
 * @return mixed
 */
function getAmountOfVisits(Guest $guest, RadacctRepository $radacctRepository) {
    return $radacctRepository->getTotalAccessByGuest($guest);
}

/**
 * @param Guest $guest
 * @param $guestArray
 * @param $id (parâmetro passado ao script em $argv[1])
 */
function convertToEntity(Guest $guest, $guestArray, $id) {
    $properties = [
	    "email"             => isset($guestArray["properties"]["email"]) ? $guestArray["properties"]["email"] : "-",
	    "name"              => isset($guestArray["properties"]["name"]) ? $guestArray["properties"]["name"] : "-",
	    "last_name"         => isset($guestArray["properties"]["last_name"]) ? $guestArray["properties"]["last_name"] : "-",
	    "gender"            => isset($guestArray["properties"]["gender"]) ? $guestArray["properties"]["gender"] : "-",
	    "phone"             => isset($guestArray["properties"]["phone"]) ? $guestArray["properties"]["phone"] : "-",
	    "document"          => isset($guestArray["properties"]["document"]) ? $guestArray["properties"]["document"] : "-",
	    "zip_code"          => isset($guestArray["properties"]["zip_code"]) ? $guestArray["properties"]["zip_code"] : "-",
	    "data_nascimento"   => isset($guestArray["properties"]["data_nascimento"]) ? $guestArray["properties"]["data_nascimento"] : "-"
    ];

    $guest->setId($id);
    $guest->setProperties($properties);
    $guest->setMysql(isset($guestArray["mysql"]) ? $guestArray["mysql"] : "-");
    $guest->setGroup(isset($guestArray["group"]) ? $guestArray["group"] : "-");
    $guest->setCreated(isset($guestArray["created"]) ? $guestArray["created"] : "-");
    $guest->setLastAccess(isset($guestArray["lastAccess"]) ? $guestArray["lastAccess"] : "-");
    $guest->setRegisterMode(isset($guestArray["registerMode"]) ? $guestArray["registerMode"] : "Formulário");
    $guest->setRegistrationMacAddress($guestArray['registrationMacAddress']);
}

/**
 * @param $time
 * @return string
 * @throws Exception
 */
function applyTimeFormat($time)
{
    $begin  = new \DateTime("@0");
    $finish = new \DateTime("@" . (int)$time);
    $format = "";

    $diff = $begin->diff($finish);

    if ($diff->m > 0) {
        $format .= "%mm ";
    }

    if ($diff->d > 0) {
        $format .= "%Dd ";
    }

    if ($diff->h > 0) {
        $format .= "%Hh ";
    }

    if ($diff->i > 0) {
        $format .= "%Im ";
    }
    $format .= "%Ss";

    return $diff->format($format);
}

/**
 * @param DocumentManager $mongo
 * @param Guest $guest
 * @return string
 */
function getGroupName(DocumentManager $mongo, Guest $guest)
{
    if ($guest->getGroup()) {
        $group = $mongo->getRepository("DomainBundle:Group\Group")
            ->findOneByShortcode($guest->getGroup());

        if ($group) {
            return @str_replace(";", ",", $group->getName());
        }
    }

    return "Visitantes";
}

/**
 * @param $bytes
 * @return string
 */
function convertByteToGBorMB($bytes)
{
    $mb = ($bytes / 1024 / 1024);

    if ($mb >= 100000000) {
        $result = number_format(($mb/1024/1024/1024), 0, '.', '')." PB";
    } else if ($mb >= 1000000 && $mb <= 100000000) {
        $result = number_format(($mb/1024/1024), 0, '.', "")." TB";
    } else if ($mb >= 1024 && $mb <= 1000000) {
        $result = number_format(($mb/1024), 0, '.', "")." GB";
    } else if (substr($mb, 0, 1) != 0) {
        $result = number_format($mb, 0, '.', "")." MB";
    } else {
        $result = number_format($mb, 2, '.', "")." MB";
    }

    return $result;
}

/**
 * @param $fileName
 * @param $filePath
 * @param FileUpload $fileUpload
 * @return string
 */
function sendToStorage($fileName, $filePath, FileUpload $fileUpload) {
    try {
        $fileUpload->uploadFile(new UploadedFile($filePath, $fileName), $fileName, BUCKET, STORAGE_FOLDER);
        return "URL: " . $fileUpload->getUrl($fileName, BUCKET, STORAGE_FOLDER);
    } catch (\Exception $exception) {
        return "Nenhum arquivo foi enviado ao Storage.";
    }
}

/**
 * @param EntityManager $em
 * @param $domain
 * @return object|Client|null
 */
function getClientByScriptParameter(EntityManager $em, $domain) {
    return $em->getRepository("DomainBundle:Client")->findOneBy(['domain' => $domain]);
}

/**
 * @param EntityManager $em
 * @param Client $client
 * @return array|AccessPoints[]
 */
function getAccessPointsByClient(EntityManager $em, Client $client) {
	return $em->getRepository("DomainBundle:AccessPoints")->findBy(["client" => $client]);
}

/**
 * @param EntityManager $em
 * @param $identifier
 * @return string
 */
function getRegistrationApName(EntityManager $em, $identifier) {
	$apName = '-';

	$search = $em->getRepository("DomainBundle:AccessPoints")->findOneBy(["identifier" => $identifier]);

	if ($search) {
		return $search->getFriendlyName();
	}

	return $apName;
}
