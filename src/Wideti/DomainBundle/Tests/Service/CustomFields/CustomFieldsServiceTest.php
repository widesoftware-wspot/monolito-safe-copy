<?php

namespace Wideti\DomainBundle\Tests\Service\CustomFields;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Document\Repository\Fields\FieldRepository;
use Wideti\DomainBundle\Document\Repository\GuestRepository;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsCacheService;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsService;

/**
 * Class CustomFieldsServiceTest
 * @package Wideti\DomainBundle\Tests\Service\CustomFields
 */
class CustomFieldsServiceTest extends WebTestCase
{
    const DOMAIN = "dev";

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $configurationService;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $customFieldsCacheService;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $guestRepository;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $customFieldsRepository;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $client;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $request;
    /**
     * @var CustomFieldsService
     */
    private $customFieldsService;

    public function setUp()
    {
        $this->configurationService = $this->getMockBuilder(ConfigurationService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customFieldsCacheService = $this->getMockBuilder(CustomFieldsCacheService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->guestRepository = $this->getMockBuilder(GuestRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customFieldsRepository = $this->getMockBuilder(FieldRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customFieldsService = new CustomFieldsService(
            $this->configurationService,
            $this->customFieldsCacheService,
            $this->guestRepository,
            $this->customFieldsRepository
        );
    }

    public function testMustAllowChangesOnLoginField()
    {
        $existingGuests = 0;

        $this->guestRepository->expects($this->once())
            ->method("getTotalGuestsPerDomain")
            ->willReturn($existingGuests);

        $this->client->method("getDomain")
            ->willReturn(self::DOMAIN);

        $result = $this->customFieldsService->allowChangeOnLoginField($this->client);

        $this->assertThat($result, new \PHPUnit_Framework_Constraint_IsTrue());
    }

    public function testMustNotAllowChangesOnLoginField()
    {
        $existingGuests = 10;

        $this->guestRepository->expects($this->once())
            ->method("getTotalGuestsPerDomain")
            ->willReturn($existingGuests);

        $this->client->method("getDomain")
            ->willReturn(self::DOMAIN);

        $result = $this->customFieldsService->allowChangeOnLoginField($this->client);

        $this->assertThat($result, new \PHPUnit_Framework_Constraint_IsFalse());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid post data format
     */
    public function testMustNotProcessInvalidNullParameterOnAjaxSaveRequest()
    {
        $this->request->method("getContent")->willReturn(null);
        $this->customFieldsService->parseArrayToObjectField($this->request);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid post data format
     */
    public function testMustNotProcessInvalidIntegerParameterOnAjaxSaveRequest()
    {
        $this->request->method("getContent")->willReturn(10);
        $this->customFieldsService->parseArrayToObjectField($this->request);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid post data format
     */
    public function testMustNotProcessInvalidStringParameterOnAjaxSaveRequest()
    {
        $this->request->method("getContent")->willReturn(self::DOMAIN);
        $this->customFieldsService->parseArrayToObjectField($this->request);
    }
}