<?php

namespace Wideti\ApiBundle\Tests\Integration\Controller;

use Faker\Factory;
use Faker\Generator;
use Wideti\ApiBundle\Tests\Integration\IntegrationTestCase;
use Wideti\DomainBundle\Service\AccessPoints\AccessPointApiValidatorImp;

class AccessPointsControllerTest extends IntegrationTestCase
{

    private $vendorsNoMask;
    private $vendorsWithMask;
    private $faker;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->faker = Factory::create();
    }

    public function setUp()
    {
        parent::setUp();
        $this->vendorsNoMask = ['aerohive', 'winco', 'mikrotik', 'pfsense', 'fortinet', 'ruckus-standalone'];
        $this->vendorsWithMask = ['xirrus', 'aruba', 'ruckus-controlador', 'zyxel', 'enterasys', 'cisco', 'motorola'];
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testMustListAllVendors()
    {
        $crawler = $this->httpClient->request('GET', '/api/access-points/vendors');
        $result = json_decode($this->httpClient->getResponse()->getContent(), true);

        $this->assertInternalType('array', $result);
        $this->assertTrue(
            $this->httpClient->getResponse()->headers->contains('Content-Type', 'application/json')
        );
        $this->assertEquals(200, $this->httpClient->getResponse()->getStatusCode());
        $this->assertTrue(count($result) > 0);
        $this->assertArrayHasKey('vendor', $result[0]);
        $this->assertArrayHasKey('mask', $result[0]);
    }

    public function testMustCreateAccessPointSuccessNoMask()
    {
        $apFakeData = $this->getApFakeToCreate();
        $crawler = $this->httpClient->request(
            'POST',
            '/api/access-points',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($apFakeData)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertInternalType('array', $result);
        $this->assertApFieldsExists($result);
    }

    public function testMustCreateAccessPointSuccessWithMask()
    {
        $apFakeData = $this->getApFakeToCreate(true);

        $crawler = $this->httpClient->request(
            'POST',
            '/api/access-points',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($apFakeData)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertInternalType('array', $result);
        $this->assertApFieldsExists($result);
    }

    public function testMustNotCreateAccessPointIfIdentifierHasInvalidMaskValueInVendorWithMask()
    {
        $apFakeData = $this->getApFakeWithWrongIdentifier();

        $crawler = $this->httpClient->request(
            'POST',
            '/api/access-points',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($apFakeData)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('identifier', $result);
        $this->assertEquals(
            AccessPointApiValidatorImp::ERROR_MAC_ADDRESS_MASK,
            $result['identifier']
        );
    }

    public function testMustNotCreateAccessPointWithWrongVendor()
    {
        $apFakeData = $this->getApFakeToCreate();
        $apFakeData['vendor'] = "wrong_vendor";

        $crawler = $this->httpClient->request(
            'POST',
            '/api/access-points',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($apFakeData)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('vendor', $result);
        $this->assertEquals(
            AccessPointApiValidatorImp::ERROR_VENDOR,
            $result['vendor']
        );
    }

    public function testMustNotCreateAccessPointWithWrongStatus()
    {
        $apFakeData = $this->getApFakeToCreate();
        $apFakeData['status'] = 10;

        $crawler = $this->httpClient->request(
            'POST',
            '/api/access-points',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($apFakeData)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals(
            AccessPointApiValidatorImp::ERROR_STATUS_INVALID,
            $result['status']
        );
    }

    public function testMustNotCreateAccessPointWithWrongGroupId()
    {
        $apFakeData = $this->getApFakeToCreate();
        $apFakeData['groupId'] = 12345678;

        $crawler = $this->httpClient->request(
            'POST',
            '/api/access-points',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($apFakeData)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('groupId', $result);
        $this->assertEquals(
            AccessPointApiValidatorImp::ERROR_GROUP_NOT_EXISTS,
            $result['groupId']
        );
    }

    public function testMustNotCreateAccessPointWithWrongTemplateId()
    {
        $apFakeData = $this->getApFakeToCreate();
        $apFakeData['templateId'] = 12345678;

        $crawler = $this->httpClient->request(
            'POST',
            '/api/access-points',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($apFakeData)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('templateId', $result);
        $this->assertEquals(
            AccessPointApiValidatorImp::ERROR_TEMPLATE_NOT_EXISTS,
            $result['templateId']
        );
    }

    public function testMustNotCreateAccessPointWithNoData()
    {
        $crawler = $this->httpClient->request(
            'POST',
            '/api/access-points',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            null
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('friendlyName', $result);
        $this->assertEquals(
            AccessPointApiValidatorImp::ERROR_MESSAGE_FRIENDLY_NAME_REQUIRED,
            $result['friendlyName']
        );
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals(
            AccessPointApiValidatorImp::ERROR_STATUS_INVALID,
            $result['status']
        );
        $this->assertArrayHasKey('vendor', $result);
        $this->assertEquals(
            AccessPointApiValidatorImp::ERROR_VENDOR,
            $result['vendor']
        );
        $this->assertArrayHasKey('identifier', $result);
        $this->assertEquals(
            AccessPointApiValidatorImp::ERROR_IDENTIFIER_REQUIRED,
            $result['identifier']
        );
        $this->assertArrayHasKey('groupId', $result);
        $this->assertEquals(
            AccessPointApiValidatorImp::ERROR_GROUP_NOT_EXISTS,
            $result['groupId']
        );
    }

    public function testMustNotCreateAccessPointIfIdentifierExists()
    {
        $apFakeData = $this->getApFakeToCreate();
        $existsAp = $this->getExistentAccessPoint();

        $apFakeData['identifier'] = $existsAp['identifier'];

        $crawler = $this->httpClient->request(
            'POST',
            '/api/access-points',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($apFakeData)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('identifier', $result);
        $this->assertEquals(
            AccessPointApiValidatorImp::ERROR_IDENTIFIER_EXISTS,
            $result['identifier']
        );
    }

    public function testMustNotCreateAccessPointWithIfUnmappedFieldsInRequest()
    {
        $apFakeData = $this->getApFakeToCreate();
        $apFakeData['unmapped_field'] = 12345678;

        $crawler = $this->httpClient->request(
            'POST',
            '/api/access-points',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($apFakeData)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(
            "Campos [unmapped_field] não são validos, consulte a documentação.",
            $result['error']
        );
    }

    public function testListAllAccessPoints()
    {
        $crawler = $this->httpClient->request(
            'GET',
            '/api/access-points'
        );

        $response = $this->httpClient->getResponse();
        $result = json_decode($response->getContent(), true);
        $this->assertTrue(
            $response->headers->contains(
                'Content-Type',
                'application/json'
            )
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertInternalType('array', $result);
        $this->assertTrue(count($result) > 0);
        $this->assertApFieldsExists($result[0]);
    }

    public function testMustListAllAccessPointPaginated()
    {
        $crawler = $this->httpClient->request(
            'GET',
            '/api/access-points?page=0&limit=1'
        );

        $response = $this->httpClient->getResponse();
        $result = json_decode($response->getContent(), true);
        $this->assertTrue(
            $response->headers->contains(
                'Content-Type',
                'application/json'
            )
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $result);
        $this->assertApFieldsExists($result[0]);
    }

    public function testMustListAllAccessPointPaginatedPageTwo()
    {
        $crawler = $this->httpClient->request(
            'GET',
            '/api/access-points?page=1&limit=1'
        );

        $response = $this->httpClient->getResponse();
        $result = json_decode($response->getContent(), true);
        $this->assertTrue(
            $response->headers->contains(
                'Content-Type',
                'application/json'
            )
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $result);
        $this->assertApFieldsExists($result[0]);
    }

    public function testMustListAllAccessPointPaginatedEmpty()
    {
        $crawler = $this->httpClient->request(
            'GET',
            '/api/access-points?page=1000&limit=1'
        );

        $response = $this->httpClient->getResponse();
        $result = json_decode($response->getContent(), true);
        $this->assertTrue(
            $response->headers->contains(
                'Content-Type',
                'application/json'
            )
        );
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertCount(0, $result);
    }

    public function testMustListAccessPointByStatusActive()
    {
        $page = 0;
        do {
            $this->httpClient->request("GET", "/api/access-points?status=1&page={$page}");
            $response = $this->httpClient->getResponse();
            $results = json_decode($response->getContent(), true);
            foreach ($results as $result) {
                if ($result['status'] !== 1) {
                    $this->fail("Encontrado ponto de acesso com status 0 = inativo");
                }
            }
            $this->httpClient->restart();
            $page++;
        } while (!empty($results));
    }

    public function testMustListAccessPointByStatusInactive()
    {
        $page = 0;
        do {
            $this->httpClient->request("GET", "/api/access-points?status=0&page={$page}" .
                "");
            $response = $this->httpClient->getResponse();
            $results = json_decode($response->getContent(), true);
            foreach ($results as $result) {
                if ($result['status'] !== 0) {
                    $this->fail("Encontrado ponto de acesso com status 1 = ativo");
                }
            }
            $this->httpClient->restart();
            $page++;
        } while (!empty($results));
    }

    public function testMustListAccessPointByIdentifier()
    {
        $accessPoint = $this->getExistentAccessPoint();
        $identifier = $accessPoint['identifier'];

        $this->httpClient->request('GET', "/api/access-points?identifier={$identifier}");
        $response = $this->httpClient->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($identifier, $result[0]['identifier']);
    }

    public function testMustListAccessPointByFriendlyName()
    {
        $accessPoint = $this->getExistentAccessPoint();
        $friendlyName = $accessPoint['friendlyName'];

        $this->httpClient->request('GET', "/api/access-points?friendlyName={$friendlyName}");
        $response = $this->httpClient->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($friendlyName, $result[0]['friendlyName']);
    }

    public function testMustNotListAccessPointDuplicatedBetweenPages()
    {
        $apsIds = [];
        $page = 0;
        do {
            $this->httpClient->request("GET", "/api/access-points?page={$page}" .
                "");
            $response = $this->httpClient->getResponse();
            $results = json_decode($response->getContent(), true);
            foreach ($results as $result) {
                $apsIds[] = $result['id'];
            }
            $this->httpClient->restart();
            $page++;
        } while (!empty($results));

        $uniqueIds = array_unique($apsIds);
        $this->assertEquals($uniqueIds, $apsIds);
    }

    public function testMustDetailAccessPoint()
    {
        $accessPoint = $this->getExistentAccessPoint();
        $this->httpClient->request('GET', "/api/access-points/{$accessPoint['id']}");

        $response = $this->httpClient->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($accessPoint['id'], $result['id']);
        $this->assertEquals($accessPoint['friendlyName'], $result['friendlyName']);
        $this->assertEquals($accessPoint['created'], $result['created']);
        $this->assertEquals($accessPoint['updated'], $result['updated']);
        $this->assertEquals($accessPoint['vendor'], $result['vendor']);
        $this->assertEquals($accessPoint['identifier'], $result['identifier']);
        $this->assertEquals($accessPoint['local'], $result['local']);
        $this->assertEquals($accessPoint['verified'], $result['verified']);
        $this->assertEquals($accessPoint['status'], $result['status']);
        $this->assertEquals($accessPoint['template']['id'], $result['template']['id']);
        $this->assertEquals($accessPoint['template']['name'], $result['template']['name']);
        $this->assertEquals($accessPoint['group']['id'], $result['group']['id']);
        $this->assertEquals($accessPoint['group']['name'], $result['group']['name']);
    }

    public function testMustNotDetailAccessPointWithInvalidId()
    {
        $this->httpClient->request('GET', "/api/access-points/AAA6677*A((S*S*S*S*D(D(__");

        $response = $this->httpClient->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals("Id inválido", $result['message']);
    }

    public function testMustNotDetailAccessPointNotFound()
    {
        $this->httpClient->request('GET', "/api/access-points/23233354566776565433555645");

        $response = $this->httpClient->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals([], $result);
    }

    public function testMustUpdateAccessPoint()
    {
        //Criar um accessPoint
        $apToCreate = $this->getApFakeToCreate();
        $this->httpClient->request('POST', '/api/access-points', [], [], [], json_encode($apToCreate));
        $apCreated = json_decode($this->httpClient->getResponse()->getContent(), true);
        $this->httpClient->restart();

        //Agora atualiza este access point criado
        $friendlyName = $this->faker->unique()->name;
        $local = $this->faker->unique()->text(10);
        $status = $this->faker->randomElement([1,0]);

        $updateData = [
            "friendlyName" => $friendlyName,
            "local" => $local,
            "status" => $status,
            "groupId" => $apCreated['group']['id']
        ];

        $this->httpClient->request(
            'PUT', "/api/access-points/{$apCreated['id']}", [], [], [], json_encode($updateData));
        $this->assertEquals(204, $this->httpClient->getResponse()->getStatusCode());
        $this->httpClient->restart();

        // Verifica se o access point atualizado realmente mudou.
        $updatedAp = $this->getAccessPointById($apCreated['id']);
        $this->assertEquals($apCreated['id'], $updatedAp['id']);
        $this->assertEquals($apCreated['identifier'], $updatedAp['identifier']);
        $this->assertEquals($apCreated['vendor'], $updatedAp['vendor']);
        $this->assertEquals($friendlyName, $updatedAp['friendlyName']);
        $this->assertEquals($local, $updatedAp['local']);
        $this->assertEquals($status, $updatedAp['status']);
        $this->assertEquals($apCreated['group']['id'], $updatedAp['group']['id']);
    }

    public function testMustNotUpdateAccessPointIdentifier()
    {
        $accessPoint = $this->getExistentAccessPoint();
        $this->httpClient->request(
            'PUT',
            "/api/access-points/{$accessPoint['id']}",
            [],
            [],
            [],
            json_encode([
                "identifier" => '11-11-11-11-11-11'
            ]));

        $response = $this->httpClient->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals("Os campos vendor e identifier não podem ser atualizados.", $content['error']);
    }

    public function testMustNotUpdateAccessPointVendor()
    {
        $accessPoint = $this->getExistentAccessPoint();
        $this->httpClient->request(
            'PUT',
            "/api/access-points/{$accessPoint['id']}",
            [],
            [],
            [],
            json_encode([
                "vendor" => 'cisco'
            ]));

        $response = $this->httpClient->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals("Os campos vendor e identifier não podem ser atualizados.", $content['error']);
    }

    public function testMustNotUpdateAccessPointIfIdNotExists()
    {
        $this->httpClient->request(
            'PUT',
            "/api/access-points/99999999",
            [],
            [],
            [],
            json_encode([
                "status" => 1
            ]));

        $response = $this->httpClient->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals("Ponto de acesso id: 99999999 não existe.", $content['id']);
    }

    public function testMustNotUpdateAccessPointIfRequestIsEmpty()
    {
        $accessPoint = $this->getExistentAccessPoint();
        $this->httpClient->request(
            'PUT',
            "/api/access-points/{$accessPoint['id']}",
            [],
            [],
            [],
            json_encode([]));

        $response = $this->httpClient->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals("Não existem campos na requisição para serem atualizados.", $content['error']);
    }

    /**
     * @param $result
     */
    private function assertApFieldsExists($result)
    {
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('friendlyName', $result);
        $this->assertArrayHasKey('created', $result);
        $this->assertArrayHasKey('updated', $result);
        $this->assertArrayHasKey('vendor', $result);
        $this->assertArrayHasKey('identifier', $result);
        $this->assertArrayHasKey('vendor', $result);
        $this->assertArrayHasKey('local', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('template', $result);
        $this->assertArrayHasKey('id', $result['template']);
        $this->assertArrayHasKey('name', $result['template']);
        $this->assertArrayHasKey('group', $result);
        $this->assertArrayHasKey('id', $result['group']);
        $this->assertArrayHasKey('name', $result['group']);
    }

    private function getIdentifierNoMacMask(Generator $faker)
    {
        return strtolower(str_replace(".", "", str_replace(" ", "_", $faker->unique()->name)));
    }

    private function getApFakeToCreate($withMac = false)
    {
        $faker = Factory::create();

        $identifier = $withMac
            ? str_replace(":", "-", $faker->unique()->macAddress)
            : $this->getIdentifierNoMacMask($faker);

        $vendorList = $withMac
            ? $this->vendorsWithMask
            : $this->vendorsNoMask;

        $vendor = $faker->randomElement($vendorList);

        return [
            'friendlyName' => str_replace(".", "",$faker->unique()->text(20)),
            'identifier' => $identifier,
            'status' => $faker->numberBetween(0,1),
            'local' => $faker->unique()->text(20),
            'vendor' => $vendor,
            'groupId' => $this->getFirstApGroup()[0]['id']
        ];
    }

    private function getFirstApGroup()
    {
        $crawler = $this->httpClient->request('GET', '/api/access-point-groups');
        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $this->httpClient->restart();

        return $result;
    }

    private function getApFakeWithWrongIdentifier()
    {
        $faker = Factory::create();

        return [
            'friendlyName' => str_replace(".", "",$faker->unique()->text(20)),
            'identifier' => $this->getIdentifierNoMacMask($faker),
            'status' => $faker->numberBetween(0,1),
            'local' => $faker->unique()->text(20),
            'vendor' => $faker->randomElement($this->vendorsWithMask),
            'groupId' => 1
        ];
    }

    private function getExistentAccessPoint()
    {
        $crawler = $this->httpClient->request('GET', '/api/access-points');
        $result = json_decode($this->httpClient->getResponse()->getContent(), true);

        $this->httpClient->restart();
        return $result[0];
    }

    private function getAccessPointById($id)
    {
        $crawler = $this->httpClient->request('GET', "/api/access-points/{$id}");
        $result = json_decode($this->httpClient->getResponse()->getContent(), true);

        $this->httpClient->restart();
        return $result;
    }
}
