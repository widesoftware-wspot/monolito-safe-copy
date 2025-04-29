<?php
/**
 * Created by PhpStorm.
 * User: romani
 * Date: 29/05/18
 * Time: 15:26
 */

namespace Wideti\DomainBundle\Tests\Service\AccessPoints\Dto\Api;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\AccessPoints\Dto\Api\CreateAccessPointDto;

class CreateAccessPointDtoTest extends WebTestCase
{

    public function testMustCreateDtoFromValidRequestJson()
    {
        $jsonString = '
            {
                "friendlyName": "Tilapia",
                "vendor": "mikrotik",
                "identifier": "64-D1-54-E3-2A-D4",
                "local": "Tilapia",
                "status": 1,
                "templateId": 1,
                "groupId": 2094
            }
        ';

        $client = Client::createClientWithId(1);

        $apDto = CreateAccessPointDto::createFromAssocArray(json_decode($jsonString, true), $client);

        $this->assertEquals("64-D1-54-E3-2A-D4", $apDto->getIdentifier());
        $this->assertEquals("mikrotik", $apDto->getVendor());
        $this->assertEquals(1, $apDto->getClient()->getId());
    }

    public function testMustCreateEmptyObjectIFRequestJsonIsEmptyString()
    {
        $jsonString = '';

        $client = Client::createClientWithId(1);

        $apDto = CreateAccessPointDto::createFromAssocArray(json_decode($jsonString, true), $client);

        $this->assertEquals(null, $apDto->getIdentifier());
        $this->assertEquals(null, $apDto->getVendor());
    }

    public function testMustCreateEmptyObjectIfReceiveNull()
    {
        $client = Client::createClientWithId(1);
        $apDto = CreateAccessPointDto::createFromAssocArray(null, $client);

        $this->assertEquals(null, $apDto->getIdentifier());
        $this->assertEquals(null, $apDto->getVendor());
    }

    public function testMustThrowInvalidExceptionIfParameterNotExistsInDto()
    {
        $this->expectException(\InvalidArgumentException::class);

        $jsonString = '
            {
                "badName": "Tilapia",
                "teste_wrong_name": "mikrotik"
            }
        ';
        $client = Client::createClientWithId(1);
        $apDto = CreateAccessPointDto::createFromAssocArray(json_decode($jsonString, true), $client);
    }

    public function testMustThrowInvalidExceptionIfParameterIsString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $client = Client::createClientWithId(1);
        $apDto = CreateAccessPointDto::createFromAssocArray("teste string", $client);
    }

    public function testMustThrowInvalidExceptionIfParameterIsInteger()
    {
        $this->expectException(\InvalidArgumentException::class);
        $client = Client::createClientWithId(1);
        $apDto = CreateAccessPointDto::createFromAssocArray(20, $client);
    }

    public function testMustThrowInvalidExceptionIfParameterIsSequentialArray()
    {
        $this->expectException(\InvalidArgumentException::class);
        $client = Client::createClientWithId(1);
        $apDto = CreateAccessPointDto::createFromAssocArray([20], $client);
    }

    public function testVerifyIfActionIsCreate()
    {
        $jsonString = '
            {
                "friendlyName": "Tilapia",
                "vendor": "mikrotik",
                "identifier": "64-D1-54-E3-2A-D4",
                "local": "Tilapia",
                "status": 1,
                "templateId": 1,
                "groupId": 2094
            }
        ';

        $client = Client::createClientWithId(1);
        $apDto = CreateAccessPointDto::createFromAssocArray(json_decode($jsonString, true), $client);

        $this->assertEquals($apDto::ACTION_CREATE, $apDto->getAction());
    }

    public function testVerifyIfActionIsUpdate()
    {
        $jsonString = '
            {
                "id" : 1,
                "friendlyName": "Cação",
                "local": "Campinas",
                "status": 1,
                "templateId": 1,
                "groupId" : 1
            }
        ';

        $client = Client::createClientWithId(1);
        $apDto = CreateAccessPointDto::createFromAssocArray(json_decode($jsonString, true), $client);

        $this->assertEquals($apDto::ACTION_UPDATE, $apDto->getAction());
    }

    public function testMustActionBeCreateAndHasClientIfAssocArrayIsEmpty() {

        $client = Client::createClientWithId(1);
        $apDto = CreateAccessPointDto::createFromAssocArray([], $client);

        $this->assertEquals($apDto::ACTION_CREATE, $apDto->getAction());
        $this->assertEquals(1, $apDto->getClient()->getId());
    }
}
