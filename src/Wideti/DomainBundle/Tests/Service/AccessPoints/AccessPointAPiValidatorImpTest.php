<?php

namespace Wideti\DomainBundle\Tests\Service\AccessPoints;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Exception\EmptyFieldsToUpdateException;
use Wideti\DomainBundle\Service\AccessPoints\AccessPointApiValidatorImp;
use Wideti\DomainBundle\Service\AccessPoints\Dto\Api\CreateAccessPointDto;
use Wideti\DomainBundle\Service\Vendor\VendorService;
use Wideti\DomainBundle\Tests\Service\Vendor\VendorTestHelper;

class AccessPointApiValidatorImpTest extends WebTestCase
{

    public function testMustValidateErrorIfFriendlyNameNotExists()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock();
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);

        $ap = new CreateAccessPointDto();
        $ap->setClient(Client::createClientWithId(1));

        $errors = [];
        $errors = $service->checkFriendlyName($ap, $errors);

        $this->assertArrayHasKey('friendlyName', $errors);
        $this->assertEquals(AccessPointApiValidatorImp::ERROR_MESSAGE_FRIENDLY_NAME_REQUIRED, $errors['friendlyName']);
    }

    public function testMustValidateErrorIfVendorNotExists()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock();
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);
        $ap = new CreateAccessPointDto();
        $ap->setClient(Client::createClientWithId(1));

        $errors = [];
        $errors = $service->checkVendor($ap, $errors);

        $this->assertArrayHasKey('vendor', $errors);
        $this->assertEquals(AccessPointApiValidatorImp::ERROR_VENDOR, $errors['vendor']);
    }

    public function testMustValidateSuccessIfVendorNameIsValid()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock();
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $ap = new CreateAccessPointDto();
        $ap->setVendor('cisco');
        $ap->setClient(Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);

        $errors = [];
        $result = $service->checkVendor($ap, $errors);

        $this->assertArrayNotHasKey('vendor', $result);
    }

    public function testMustValidateErrorIfIdentifierNotExists()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock();
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);
        $ap = new CreateAccessPointDto();
        $ap->setClient(Client::createClientWithId(1));

        $errors = [];
        $errors = $service->checkIdentifierNotEmpty($ap, $errors);

        $this->assertArrayHasKey('identifier', $errors);
        $this->assertEquals(AccessPointApiValidatorImp::ERROR_IDENTIFIER_REQUIRED, $errors['identifier']);
    }

    public function testMustValidateErrorIfIdentifierUseBadMacAddressMaskInVendorWithMask()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock();
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = new CreateAccessPointDto();
        $accessPoint
            ->setIdentifier("wrong_identifier_value")
            ->setVendor("cisco")
            ->setClient(Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);

        $errors = [];
        $errors = $service->checkIdentifierMacMask($accessPoint, $errors);

        $this->assertArrayHasKey('identifier', $errors);
        $this->assertEquals(AccessPointApiValidatorImp::ERROR_MAC_ADDRESS_MASK, $errors['identifier']);
    }

    public function testMustValidateSuccessIfIdentifierHasNoMaskInVendor()
    {
        $vendorService = $this->getVendorServiceMock(false);
        $templateRepository = $this->getTemplateRepositoryExistsMock();
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = new CreateAccessPointDto();
        $accessPoint
            ->setIdentifier("mikrotik_identifier")
            ->setVendor("mikrotik")
            ->setClient(Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);

        $errors = [];
        $errors = $service->checkIdentifierMacMask($accessPoint, $errors);

        $this->assertArrayNotHasKey('identifier',$errors);
    }

    public function testMustValidateSuccessIfIdentifierIsMacAndVendorHasMask()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock();
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = new CreateAccessPointDto();
        $accessPoint
            ->setIdentifier("11-11-11-11-11-11")
            ->setClient(Client::createClientWithId(1))
            ->setVendor("cisco");

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);

        $errors = [];
        $errors = $service->checkIdentifierMacMask($accessPoint, $errors);

        $this->assertArrayNotHasKey('identifier', $errors);
    }

    public function testMustValidateSuccessIfIdentifierHasTwoPointsMacFormatWithVendorMacMask()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock();
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = new CreateAccessPointDto();
        $accessPoint
            ->setIdentifier("11:11:11:11:11:11")
            ->setVendor("cisco")
            ->setClient(Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);

        $errors = [];
        $errors = $service->checkIdentifierMacMask($accessPoint, $errors);

        $this->assertArrayNotHasKey('identifier', $errors);
    }

    public function testMustValidateErrorIfIdentifierExistsInDatabase()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock();
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock(true);

        $accessPoint = new CreateAccessPointDto();
        $accessPoint
            ->setIdentifier("11-11-11-11-11-11")
            ->setVendor("cisco")
            ->setClient(Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);

        $errors = [];
        $errors = $service->checkIdentifierIsUnique($accessPoint, $errors);

        $this->assertArrayHasKey('identifier', $errors);
        $this->assertEquals(AccessPointApiValidatorImp::ERROR_IDENTIFIER_EXISTS, $errors['identifier']);
    }

    public function testMustValidateSuccessIfIdentifierNotExistsInDatabase()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock();
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = new CreateAccessPointDto();
        $accessPoint
            ->setIdentifier("11-11-11-11-11-11")
            ->setVendor("cisco")
            ->setClient(Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);

        $errors = [];
        $errors = $service->checkIdentifierIsUnique($accessPoint, $errors);

        $this->assertArrayNotHasKey('identifier', $errors);
    }

    public function testMustValidateErrorWhenGroupIdIsNull()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock();
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = new CreateAccessPointDto();
        $accessPoint
            ->setIdentifier('11-11-11-11-11-11')
            ->setVendor('cisco')
            ->setClient(Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);

        $errors = [];
        $errors = $service->checkGroupIsValidInteger($accessPoint, $errors);

        $this->assertArrayHasKey('groupId', $errors);
        $this->assertEquals(AccessPointApiValidatorImp::ERROR_IS_NOT_INTEGER, $errors['groupId']);
    }

    public function testMustValidateErrorWhenGroupIdIsString()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock();
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = new CreateAccessPointDto();
        $accessPoint
            ->setIdentifier('11-11-11-11-11-11')
            ->setVendor('cisco')
            ->setGroupId('1')
            ->setClient(Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);

        $errors = [];
        $errors = $service->checkGroupIsValidInteger($accessPoint, $errors);

        $this->assertArrayHasKey('groupId', $errors);
        $this->assertEquals(AccessPointApiValidatorImp::ERROR_IS_NOT_INTEGER, $errors['groupId']);
    }

    public function testMustValidateErrorWhenGroupIdIsFloat()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock();
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = new CreateAccessPointDto();
        $accessPoint
            ->setIdentifier('11-11-11-11-11-11')
            ->setVendor('cisco')
            ->setGroupId(1.20)
            ->setClient(Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);

        $errors = [];
        $errors = $service->checkGroupIsValidInteger($accessPoint, $errors);

        $this->assertArrayHasKey('groupId', $errors);
        $this->assertEquals(AccessPointApiValidatorImp::ERROR_IS_NOT_INTEGER, $errors['groupId']);
    }

    public function testMustValidateSuccessWhenGroupIdIsInt()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock();
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = new CreateAccessPointDto();
        $accessPoint
            ->setIdentifier('11-11-11-11-11-11')
            ->setVendor('cisco')
            ->setGroupId(1)
            ->setClient(Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);

        $errors = [];
        $errors = $service->checkGroupIsValidInteger($accessPoint, $errors);

        $this->assertArrayNotHasKey('groupId', $errors);
    }

    public function testMustValidateErrorIfGroupIdNotExists()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock();
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = new CreateAccessPointDto();
        $accessPoint
            ->setIdentifier('11-11-11-11-11-11')
            ->setVendor('cisco')
            ->setGroupId(30)
            ->setClient(Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);
        $errors = [];
        $errors = $service->checkGroupIdExists($accessPoint, $errors);

        $this->assertArrayHasKey('groupId', $errors);
        $this->assertEquals(AccessPointApiValidatorImp::ERROR_GROUP_NOT_EXISTS, $errors['groupId']);
    }

    public function testMustValidateSuccessIfGroupIdExists()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock();
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock(true);
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = new CreateAccessPointDto();
        $accessPoint
            ->setIdentifier('11-11-11-11-11-11')
            ->setVendor('cisco')
            ->setGroupId(30)
            ->setClient(Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);
        $errors = [];
        $errors = $service->checkGroupIdExists($accessPoint, $errors);

        $this->assertArrayNotHasKey('groupId', $errors);
    }

    public function testMustValidateSuccessIfTemplateIdIsNull()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock(true);
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = new CreateAccessPointDto();
        $accessPoint
            ->setIdentifier('11-11-11-11-11-11')
            ->setVendor('cisco')
            ->setGroupId(30)
            ->setClient(Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);
        $errors = [];
        $errors = $service->checkTemplateIdExists($accessPoint, $errors);

        $this->assertArrayNotHasKey('templateId', $errors);
    }

    public function testMustValidateSuccessIfTemplateIdExists()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock(true);
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = new CreateAccessPointDto();
        $accessPoint
            ->setIdentifier('11-11-11-11-11-11')
            ->setVendor('cisco')
            ->setGroupId(30)
            ->setTemplateId(1)
            ->setClient(Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);
        $errors = [];
        $errors = $service->checkTemplateIdExists($accessPoint, $errors);

        $this->assertArrayNotHasKey('templateId', $errors);
    }

    public function testMustValidateErrorIfTemplateIdNotExists()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock();
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = new CreateAccessPointDto();
        $accessPoint
            ->setIdentifier('11-11-11-11-11-11')
            ->setVendor('cisco')
            ->setGroupId(30)
            ->setTemplateId(1)
            ->setClient(Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);
        $errors = [];
        $errors = $service->checkTemplateIdExists($accessPoint, $errors);

        $this->assertArrayHasKey('templateId', $errors);
        $this->assertEquals(AccessPointApiValidatorImp::ERROR_TEMPLATE_NOT_EXISTS, $errors['templateId']);
    }

    public function testMustValidateErrorIfTemplateIdIsString()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock(true);
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = new CreateAccessPointDto();
        $accessPoint
            ->setIdentifier('11-11-11-11-11-11')
            ->setVendor('cisco')
            ->setGroupId(30)
            ->setTemplateId('1')
            ->setClient(Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);
        $errors = [];
        $errors = $service->checkTemplateIdExists($accessPoint, $errors);

        $this->assertArrayHasKey('templateId', $errors);
        $this->assertEquals(AccessPointApiValidatorImp::ERROR_IS_NOT_INTEGER, $errors['templateId']);

    }

    public function testMustValidateErrorIfTemplateIdIsFloat()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock(true);
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = new CreateAccessPointDto();
        $accessPoint
            ->setIdentifier('11-11-11-11-11-11')
            ->setVendor('cisco')
            ->setGroupId(30)
            ->setTemplateId(23.3)
            ->setClient(Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);
        $errors = [];
        $errors = $service->checkTemplateIdExists($accessPoint, $errors);

        $this->assertArrayHasKey('templateId', $errors);
        $this->assertEquals(AccessPointApiValidatorImp::ERROR_IS_NOT_INTEGER, $errors['templateId']);
    }

    public function testMustValidateSuccessIfStatusIsActive()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock(true);
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = new CreateAccessPointDto();
        $accessPoint
            ->setIdentifier('11-11-11-11-11-11')
            ->setVendor('cisco')
            ->setGroupId(30)
            ->setTemplateId(23.3)
            ->setStatus(1)
            ->setClient(Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);
        $errors = [];
        $errors = $service->checkStatusIsValid($accessPoint, $errors);

        $this->assertArrayNotHasKey('status', $errors);
    }

    public function testMustValidateSuccessIfStatusIsInactive()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock(true);
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = new CreateAccessPointDto();
        $accessPoint
            ->setIdentifier('11-11-11-11-11-11')
            ->setVendor('cisco')
            ->setGroupId(30)
            ->setTemplateId(23.3)
            ->setStatus(0)
            ->setClient(Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);
        $errors = [];
        $errors = $service->checkStatusIsValid($accessPoint, $errors);

        $this->assertArrayNotHasKey('status', $errors);
    }

    public function testMustValidateErrorIfStatusIsInvalid()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock(true);
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = new CreateAccessPointDto();
        $accessPoint
            ->setIdentifier('11-11-11-11-11-11')
            ->setVendor('cisco')
            ->setGroupId(30)
            ->setTemplateId(23.3)
            ->setStatus(3)
            ->setClient(Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);
        $errors = [];
        $errors = $service->checkStatusIsValid($accessPoint, $errors);

        $this->assertArrayHasKey('status', $errors);
        $this->assertEquals(AccessPointApiValidatorImp::ERROR_STATUS_INVALID, $errors['status']);
    }

    public function testMustValidateErrorIfStatusIsNull()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock(true);
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = new CreateAccessPointDto();
        $accessPoint
            ->setStatus(null)
            ->setClient(Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);
        $errors = [];
        $errors = $service->checkStatusIsValid($accessPoint, $errors);

        $this->assertArrayHasKey('status', $errors);
        $this->assertEquals(AccessPointApiValidatorImp::ERROR_STATUS_INVALID, $errors['status']);
    }

    public function testMustValidateErrorIfStatusIsString()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock(true);
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = new CreateAccessPointDto();
        $accessPoint
            ->setStatus('1')
            ->setClient(Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);
        $errors = [];
        $errors = $service->checkStatusIsValid($accessPoint, $errors);

        $this->assertArrayHasKey('status', $errors);
        $this->assertEquals(AccessPointApiValidatorImp::ERROR_STATUS_INVALID, $errors['status']);
    }

    public function testMustValidateErrorIfStatusIsBoolean()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock(true);
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = new CreateAccessPointDto();
        $accessPoint
            ->setStatus(true)
            ->setClient(Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);
        $errors = [];
        $errors = $service->checkStatusIsValid($accessPoint, $errors);

        $this->assertArrayHasKey('status', $errors);
        $this->assertEquals(AccessPointApiValidatorImp::ERROR_STATUS_INVALID, $errors['status']);
    }

    public function testUpdateRequest()
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

        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock(true);
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock(true);
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock(true);

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);

        $errors = $service->validate($apDto);

        $this->assertEmpty($errors);
    }

    public function testMustReturnAlErrorsIfApDtoIsEmptyOnCreate()
    {

        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock(true);
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = CreateAccessPointDto::createFromAssocArray([], Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);
        $errors = [];
        $errors = $service->validate($accessPoint, $errors);

        $this->assertArrayHasKey('status', $errors);
        $this->assertArrayHasKey('friendlyName', $errors);
        $this->assertArrayHasKey('vendor', $errors);
        $this->assertArrayHasKey('identifier', $errors);
        $this->assertArrayHasKey('groupId', $errors);
        $this->assertEquals(AccessPointApiValidatorImp::ERROR_STATUS_INVALID, $errors['status']);
    }

    public function testMustValidateErrorIfApDtoHasOnlyIdToUpdateButIsEmpty()
    {
        $this->expectException(EmptyFieldsToUpdateException::class);
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock(true);
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = CreateAccessPointDto::createFromAssocArray(["id" => 10], Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);
        $errors = [];
        $errors = $service->validate($accessPoint, $errors);
    }

    public function testMustValidateErrorIfApDtoHasSomeFieldToUpdateButFieldIsInvalid()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock(true);
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = CreateAccessPointDto::createFromAssocArray(["id" => 10, "status" => 10], Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);
        $errors = [];
        $errors = $service->validate($accessPoint, $errors);
        $this->assertArrayHasKey('status', $errors);
    }

    public function testMustValidateErrorIfAccessPointNotExistsInUpdate()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock(true);
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock();

        $accessPoint = CreateAccessPointDto::createFromAssocArray(["id" => 10, "status" => 1], Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);
        $errors = [];
        $errors = $service->validate($accessPoint, $errors);

        $this->assertArrayHasKey('id', $errors);
    }


    public function testMustValidateSuccessIfAccessPointExistsInUpdate()
    {
        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock(true);
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock(true);

        $accessPoint = CreateAccessPointDto::createFromAssocArray(["id" => 10, "status" => 1], Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);
        $errors = [];
        $errors = $service->validate($accessPoint, $errors);

        $this->assertArrayNotHasKey('id', $errors);
        $this->assertCount(0, $errors);
    }

    public function testMustThrowInvalidArgumentExceptionIfDtoHasIdentifierOnUpdate()
    {
        $this->setExpectedException(\InvalidArgumentException::class);

        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock(true);
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock(true);

        $accessPoint = CreateAccessPointDto::createFromAssocArray(["id" => 10, "identifier" => '11-11-11-11-11-11'], Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);
        $errors = [];
        $errors = $service->validate($accessPoint, $errors);
    }

    public function testMustThrowInvalidArgumentExceptionIfDtoHasVendorOnUpdate()
    {
        $this->setExpectedException(\InvalidArgumentException::class);

        $vendorService = $this->getVendorServiceMock(true);
        $templateRepository = $this->getTemplateRepositoryExistsMock(true);
        $accessPointGroupRepository = $this->getAccessPointsGroupRepositoryExistsMock();
        $accessPointRepository = $this->getAccessPoinsRepositoryExistsMock(true);

        $accessPoint = CreateAccessPointDto::createFromAssocArray(["id" => 10, "vendor" => 'cisco'], Client::createClientWithId(1));

        $service = new AccessPointApiValidatorImp($vendorService, $templateRepository, $accessPointGroupRepository, $accessPointRepository);
        $errors = [];
        $errors = $service->validate($accessPoint, $errors);
    }

    /**
     * @param bool $hasMaskResult
     * @return VendorService
     */
    private function getVendorServiceMock($hasMaskResult = false)
    {
        $vendorService = $this
            ->getMockBuilder('Wideti\DomainBundle\Service\Vendor\VendorService')
            ->disableOriginalConstructor()
            ->setMethods(['getVendorsAsList', 'hasMask'])
            ->getMock();

        $vendorService
            ->expects($this->any())
            ->method('getVendorsAsList')
            ->will($this->returnValue(VendorTestHelper::getVendorsAsList()));

        $vendorService
            ->expects($this->any())
            ->method('hasMask')
            ->will($this->returnValue($hasMaskResult));

        /** @var VendorService $vendorService */
        return $vendorService;
    }

    private function getAccessPointsGroupRepositoryExistsMock($existsResult = false)
    {
        $repository = $this
            ->getMockBuilder('Wideti\DomainBundle\Repository\AccessPointsGroupsRepository')
            ->disableOriginalConstructor()
            ->setMethods(['exists'])
            ->getMock();

        $repository
            ->expects($this->any())
            ->method('exists')
            ->will($this->returnValue($existsResult));

        /** @var AccessPointsGroupsRepository $repository */
        return $repository;
    }

    private function getAccessPoinsRepositoryExistsMock($existsResult = false)
    {
        $repository = $this
            ->getMockBuilder('Wideti\DomainBundle\Repository\AccessPointsRepository')
            ->disableOriginalConstructor()
            ->setMethods(['exists'])
            ->getMock();

        $repository
            ->expects($this->any())
            ->method('exists')
            ->will($this->returnValue($existsResult));

        /** @var AccessPointsGroupsRepository $repository */
        return $repository;
    }

    private function getTemplateRepositoryExistsMock($existsResult = false)
    {
        $repository = $this
            ->getMockBuilder('Wideti\DomainBundle\Repository\TemplateRepository')
            ->disableOriginalConstructor()
            ->setMethods(['exists'])
            ->getMock();

        $repository
            ->expects($this->any())
            ->method('exists')
            ->will($this->returnValue($existsResult));

        /** @var TemplateRepository $repository */
        return $repository;
    }
}
