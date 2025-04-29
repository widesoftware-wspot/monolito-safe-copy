<?php

namespace Wideti\ApiBundle\Tests\Integration\Controller;

use Faker\Factory;
use Symfony\Component\HttpFoundation\Response;
use Wideti\ApiBundle\Tests\Integration\IntegrationTestCase;
use Wideti\DomainBundle\Helpers\SpecialCharactersHelper;

class GuestControllerTest extends IntegrationTestCase
{
    private $faker;

    // TODO ====================== PARA EXECUTAR ESSA SUITE DE TESTES, TENHA AO MENOS O CUSTOM FIELD 'EMAIL' CADASTRADO

    /**
     * GuestControllerTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->faker = Factory::create();
    }

    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testMustGetAllCustomFieldsSuccess()
    {
        $this->httpClient->request(
            'GET',
            '/api/guests/fields'
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $this->assertInternalType('array', $result);
        $this->assertTrue((count($result) > 0));
    }

    public function testMustNotCreateGuestWithEmptyBody()
    {
        $this->httpClient->request(
            'POST',
            '/api/guests/pt_br',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals("A requisição POST não pode ser enviada com o body vazio", $result['message']);
    }

    public function testMustNotCreateGuestWithInvalidCustomFields()
    {
        $guest = $this->getGuestFakeInvalidCustomFields();

        $this->httpClient->request(
            'POST',
            '/api/guests/pt_br',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($guest)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertArrayHasKey("errors", $result);
        $this->assertArrayHasKey("invalid", $result['errors']);
        $this->assertEquals("Campo não pode ser inserido, pois não existe em seu formulário", $result['errors']['invalid'][0]);
    }

    public function testMustNotCreateGuestWithInvalidRegistrationMacAddress()
    {
        $guest = $this->getGuestFake();
        $guest = $this->changeFieldValue($guest, "registrationMacAddress", "BB-BB-BB-BB-BB-BB");

        $this->httpClient->request(
            'POST',
            '/api/guests/pt_br',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($guest)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertArrayHasKey("errors", $result);
        $this->assertArrayHasKey("accessPoint", $result['errors']);
        $this->assertEquals("Campo \"registrationMacAddress\" informado não é válido!", $result['errors']['accessPoint'][0]);
    }

    public function testMustNotCreateGuestWithoutPassword()
    {
        $guest = $this->getGuestFake();
        $guest = $this->changeFieldValue($guest, "password", null);

        $this->httpClient->request(
            'POST',
            '/api/guests/pt_br',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($guest)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertArrayHasKey("errors", $result);
        $this->assertArrayHasKey("password", $result['errors']);
        $this->assertEquals("Senha é obrigatória", $result['errors']['password'][0]);
    }

    public function testMustNotCreateGuestEmailAlreadyExists()
    {
        $guest = $this->getGuestFake();

        $this->httpClient->request(
            'POST',
            '/api/guests/pt_br',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($guest)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);

        $email = $result['properties']['email'];

        $newGuest = $this->getGuestFake();
        $newGuest = $this->changePropertiesValue($newGuest, 'email', $email);

        $this->httpClient->request(
            'POST',
            '/api/guests/pt_br',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($newGuest)
        );

        $newResult = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response  = $this->httpClient->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertArrayHasKey("errors", $newResult);
        $this->assertArrayHasKey("email", $newResult['errors']);
        $this->assertEquals("O campo E-mail já esta cadastrado.", $newResult['errors']['email']);
    }

    public function testMustCreateGuestSuccess()
    {
        $guest = $this->getGuestFake();

        $this->httpClient->request(
            'POST',
            '/api/guests/pt_br',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($guest)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertInternalType('array', $result);
        $this->assertGuestFieldsExists($result);
    }

    public function testMustNotUpdateGuestWithPasswordOnBody()
    {
        $guest = $this->getGuestFake();

        $this->httpClient->request(
            'PUT',
            '/api/guests/pt_br',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($guest)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response  = $this->httpClient->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(400, $result['status_code']);
        $this->assertEquals("Campo password não pode ser enviado ao atualizar um visitante", $result['message']);
    }

    public function testMustNotUpdateGuestWithoutIdOnBody()
    {
        $guest = $this->getGuestFake();
        unset($guest['password']);
        unset($guest['id']);

        $this->httpClient->request(
            'PUT',
            '/api/guests/pt_br',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($guest)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response  = $this->httpClient->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('id', $result['errors']);
        $this->assertEquals("É necessário um id para atualizar", $result['errors']['id'][0]);
    }

    public function testMustNotUpdateGuestWithMissedFields()
    {
        $object = [
            "id" => 1234
        ];

        $this->httpClient->request(
            'PUT',
            '/api/guests/pt_br',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($object)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response  = $this->httpClient->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(400, $result[0]['status']);
        $this->assertEquals("Campos obrigatórios estão faltando", $result[0]['message']);
    }

    public function testMustUpdateGuestSuccess()
    {
        $guest = $this->getGuestFakeToUpdate();

        $this->httpClient->request(
            'PUT',
            '/api/guests/pt_br',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($guest)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response  = $this->httpClient->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($guest['id'], $result['id']);
    }

    public function testMustGetAllGuestsSuccess()
    {
        $this->httpClient->request(
            'GET',
            '/api/guests'
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(($result['totalOfElements'] > 0));
    }

    public function testMustNotGetGuestsWithInvalidFilter()
    {
        $this->httpClient->request(
            'GET',
            '/api/guests?filter=invalid&value=invalid'
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(($result['totalOfElements'] == 0));
    }

    public function testMustNotGetGuestWithInvalidId()
    {
        $this->httpClient->request(
            'GET',
            '/api/guests/1234'
        );

        $response = $this->httpClient->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testMustGetGuestWithId()
    {
        $guest = $this->getGuestFake();

        $this->httpClient->request(
            'POST',
            '/api/guests/pt_br',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($guest)
        );

        $postResult = json_decode($this->httpClient->getResponse()->getContent(), true);
        $postResponse = $this->httpClient->getResponse();

        $this->assertEquals(201, $postResponse->getStatusCode());
        $this->assertArrayHasKey('id', $postResult);

        $guestId = $postResult['id'];

        $this->httpClient->request(
            "GET",
            "/api/guests/{$guestId}"
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals($guestId, $result['id']);
    }

    public function testMustNotCreateGuestBulkWithEmptyBody()
    {
        $this->httpClient->request(
            'POST',
            '/api/guests/bulk/pt_br',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $this->assertEquals(400, $result['status_code']);
        $this->assertEquals("A requisição POST não pode ser enviada com o body vazio", $result['message']);

        $response = $this->httpClient->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testMustNotCreateGuestBulkWithEmptyObjectOnBody()
    {
        $guest = [
            "id" => 1234
        ];

        $this->httpClient->request(
            'POST',
            '/api/guests/bulk/pt_br',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($guest)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);

        $this->assertEquals(400, $result[0]['status']);
        $this->assertEquals("Campos obrigatórios estão faltando", $result[0]['message']);

        $response = $this->httpClient->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testMustNotCreateGuestBulkWithInvalidArray()
    {
        $guest = $this->getGuestFake();

        $this->httpClient->request(
            'POST',
            '/api/guests/bulk/pt_br',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($guest)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(400, $result['status_code']);
        $this->assertEquals("Você não enviou um array para operação de bulk", $result['message']);

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testMustCreateGuestBulkSuccess()
    {
        $guest = $this->getGuestFake(true);

        $this->httpClient->request(
            'POST',
            '/api/guests/bulk/pt_br',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($guest)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertFalse($result['hasErrors']);
        $this->assertEquals(1, $result['successTotal']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testMustNotGetGuestDevicesWrongGuestId()
    {
        $guest = $this->getGuestFake();

        $this->httpClient->request(
            'POST',
            '/api/guests/pt_br',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($guest)
        );

        $postResult = json_decode($this->httpClient->getResponse()->getContent(), true);
        $postResponse = $this->httpClient->getResponse();
        $this->assertEquals(201, $postResponse->getStatusCode());

        $guestId = $postResult['id'];

        $this->httpClient->request(
            'GET',
            "/api/v2/guests/{$guestId}/devices"
        );

        $response = $this->httpClient->getResponse();

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testMustGetGuestDevicesSuccess()
    {
        $guest = $this->getGuestFake();

        $this->httpClient->request(
            'POST',
            '/api/v2/guests/pt_br',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($guest)
        );

        $postResult = json_decode($this->httpClient->getResponse()->getContent(), true);
        $postResponse = $this->httpClient->getResponse();
        $this->assertEquals(201, $postResponse->getStatusCode());

        $guestId = $postResult['id'];

        $this->httpClient->request(
            'GET',
            "/api/v2/guests/{$guestId}/devices"
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertInternalType('array', $result);
    }

    private function getGuestFake($bulk = false)
    {
        $faker = Factory::create();

        $password = SpecialCharactersHelper::removeSpecialCharacters($faker->password());

        if (strlen($password) < 6) {
            $password = "WSPOT${password}";
        }

        $guest = [
            "password"                  => $password,
            "status"                    => 1,
            "registrationMacAddress"    => "11-11-11-11-11-11",
            "group"                     => "Visitantes",
            "properties"                => [
                "email" => $faker->email
            ],
            "sendWelcomeSMS"            => false
        ];

        if ($bulk) {
            return [
                $guest
            ];
        }

        return $guest;
    }

    private function getGuestFakeToUpdate()
    {
        $guest = $this->getGuestFake();

        $this->httpClient->request(
            'POST',
            '/api/guests/pt_br',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($guest)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);

        return [
            "id"                        => $result['id'],
            "status"                    => $result['status'],
            "registrationMacAddress"    => $result['registrationMacAddress'],
            "group"                     => "Visitantes",
            "properties"                => [
                "email" => $result['properties']['email']
            ],
            "sendWelcomeSMS"            => false
        ];
    }

    private function getGuestFakeInvalidCustomFields()
    {
        $faker = Factory::create();

        $password = SpecialCharactersHelper::removeSpecialCharacters($faker->password());

        if (strlen($password) < 6) {
            $password = "WSPOT${password}";
        }

        return [
            "password" => $password,
            "status" => 1,
            "registrationMacAddress" => "11-11-11-11-11-11",
            "group" => "Visitantes",
            "properties" => [
                "email" => $faker->email,
                "invalid" => $faker->word
            ],
            "sendWelcomeSMS" => false
        ];
    }

    private function changeFieldValue($object, $field, $value)
    {
        $object[$field] = $value;
        return $object;
    }

    private function changePropertiesValue($object, $field, $value)
    {
        $object['properties'][$field] = $value;
        return $object;
    }

    private function assertGuestFieldsExists($result)
    {
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('group', $result);
        $this->assertArrayHasKey('created', $result);
        $this->assertArrayHasKey('validated', $result);
        $this->assertArrayHasKey('lastAccess', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('emailIsValid', $result);
        $this->assertArrayHasKey('emailIsValidDate', $result);
        $this->assertArrayHasKey('locale', $result);
        $this->assertArrayHasKey('documentType', $result);
        $this->assertArrayHasKey('authorizeEmail', $result);
        $this->assertArrayHasKey('registrationMacAddress', $result);
        $this->assertArrayHasKey('returning', $result);
        $this->assertArrayHasKey('properties', $result);
        $this->assertArrayHasKey('email', $result['properties']);
        $this->assertArrayHasKey('social', $result);
        $this->assertArrayHasKey('facebookFields', $result);
        $this->assertArrayHasKey('loginField', $result);
        $this->assertArrayHasKey('nasVendor', $result);
        $this->assertArrayHasKey('nasRaw', $result);
        $this->assertArrayHasKey('utc', $result);
        $this->assertArrayHasKey('timezone', $result);
        $this->assertArrayHasKey('refId', $result);
    }
}
