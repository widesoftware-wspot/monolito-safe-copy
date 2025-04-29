<?php
require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Wideti\DomainBundle\Helpers\StringHelper;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$output     = new \Symfony\Component\Console\Output\ConsoleOutput();

$application = new Application($kernel);
$container = $application->getKernel()->getContainer();

$em = $container->get('doctrine.orm.entity_manager');
$mongo = $container->get('doctrine.odm.mongodb.document_manager');

$clients = $em
    ->getRepository('DomainBundle:Client')
    ->findAll();

/**
 * MONGODB
 */
$i = 0;
foreach (getClientsNotGroup() as  $domain) {
    $mongoClient = $mongo->getConnection()->getMongoClient();
    $repository = $mongo->getRepository("Wideti\DomainBundle\Document\Group\Group");
    $db = StringHelper::slugDomain($domain);
    $database = $mongoClient->$db;
    $guests = $database->guests;
    $output->writeln("--------- PROCESSANDO CLIENT $i : $domain  ---------");
    $i++;
    foreach ($guests->find() as $guest) {
        if (!isset($guest['group'])) {
            $guests->update(["_id" => $guest['_id']], [
                '$set' => [
                    "group" => 'guest'
                ]
            ]);
            $output->writeln("--------- guest_id: " . $guest['_id']
                . " Client: $domain");
        }
    }
    $output->writeln("\n");
}
$output->writeln("----------- FIM -----------");

function getClientsNotGroup()
{
    return [
         "prefeituradeosorio",
         "thiago",
         "prefeituradetramandai",
         "diagnosticocampinas",
         "bvl",
         "genmills",
         "morumbicorporate",
         "ober",
         "cpfl",
         "camaraavare",
         "pousadadolago",
         "bariripracadigital",
         "viafast",
         "vocetelecom",
         "lojaodobras",
         "santagertrudes",
         "fipecafi",
         "colegiosenhoradefatima",
         "antonybeautycenter",
         "ciaathleticabrasilia",
         "cambuihotel",
         "unitedti",
         "buffetjardimviena",
         "enxuto",
         "prefeituracontagem",
         "hexato",
         "tat",
         "gloriacoelho",
         "lojaodobras2",
         "pousadadosandi",
         "medicinaocular",
         "codiub",
         "hollandaise",
         "suregoestesp",
         "gegnet",
         "bardalaje",
         "iphonebel",
         "atmosfera",
         "freeinternetcrz",
         "maniadeloja",
         "sicredicamposgerais",
         "mcdonaldsbarao",
         "sak",
         "tkn",
        "wififi",
        "grupoouroeprata",
        "thunderburger",
        "redepontual",
        "bomtempotelecom",
        "itease2",
        "nexttelecom",
        "postocacique",
        "osconnect",
        "romana",
        "prefeituradetupandi",
        "presidenteepitacio",
        "soulsports",
        "fam",
        "butekodalagoa",
        "tvlar",
        "clubecamporc",
        "reismagos",
        "superpao",
        "dlcbrasil",
        "bigjack",
        "broagolfresort",
        "mirellacalcados",
        "olimpiadas2016",
        "cardiesel",
        "lapatria",
        "interline",
        "giganet",
        "boniatti",
        "reboltelecom",
        "brsuper",
        "barnabe",
        "kf",
        "tca",
        "novaroma",
        "salomematriz",
        "novahartz",
        "vidainfinite",
        "sicrediserrana",
        "plug",
        "feb",
        "walfanger",
        "venga",
        "itulab",
        "clinicatotalsaude",
        "unimedcatanduva",
        "instant",
        "aeroportomaringa",
        "ciatradicional",
        "rioquente",
        "savol",
        "symmetry",
        "prefeituradecaete",
        "grupobaruel",
        "bdfrance",
        "print",
        "marvitel",
        "concordiabox",
        "premiatto",
        "primavera",
        "dcm-rio",
        "hn",
        "casalgarcia",
        "gruporovema",
        "hbj",
        "girus",
        "popdentsmaracanau",
        "chevroletnova",
        "next",
        "jaboticabalshopping",
        "sescse",
        "forneriadamata",
        "churrascariamazzochini",
        "sistemaocepar",
        "bieremporium",
        "mcdonaldsecl",
        "barladob",
        "mcdonaldspaulinia",
        "festaeaventura",
        "isso",
        "restauranteaobarracao",
        "pousadachoppao",
        "weplinguas",
        "mgmaster",
        "cabanadomacaco",
        "duc3",
        "cinecafe",
        "clubeaquaticofeliz",
        "topshopping",
        "duc2",
        "vivamaismaster",
        "restauranteharmoniehaus",
        "tdrive",
        "clinicaads",
        "sicredinortesc",
        "med10",
        "nhnpassos",
        "nhnparaiso",
        "shoppingplaza",
        "realnetmg",
        "clinicamellovalereal",
        "clinicamellotupandi",
        "recreiomg",
        "cervejariaurwald",
        "sicredinoroeste",
        "aquarioshotel",
        "cianeshopping",
        "sebraese",
        "boulangeriedefrance",
        "infocabos",
        "imfeliz",
        "unimed",
        "marcodastresfronteiras",
        "hittelco-baciodilatte",
        "pmmc",
        "mottanet",
        "wififi-shoppingsul",
        "internetultra",
        "mmb",
        "villabisutti",
        "redefragata",
        "lcptech",
        "pirai",
        "onecenter1",
        "chicaobarerestaurante",
        "jccafecampobello",
        "brlesamis",
        "rederealdehoteis",
        "sicrediurdc",
        "restaurantedivariani",
        "itanetmg",
        "matrix",
        "defreitas",
        "prefeituradigita",
        "prefeituradeaguai",
        "sicredinorterssc"
    ];
}
