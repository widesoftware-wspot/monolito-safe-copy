<?php

namespace Wideti\ApiBundle\Tests\Integration\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Service\Radacct\GetAccountingDataImp;

/**
 * Class GetAccountingDataTest
 * @package Wideti\ApiBundle\Tests\Integration\Controller
 */
class GetAccountingDataTest extends WebTestCase
{
    private $service;
    private $request;

    public function setUp()
    {
        $this->service = $this->getMockBuilder(GetAccountingDataImp::class)->disableOriginalConstructor()->getMock();
        $this->request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
    }

    public function testMustNotFindAnyResultsDueToInvalidIDOnRequest()
    {
        $this->service->expects($this->once())->method("get")->willReturn([]);
        $this->request->method("getContent")->willReturn([ "3", "4" ]);
        $this->assertEquals($this->service->get($this->request), []);
    }

    public function testMustReturnEmptyArrayDueToEmptyRequest()
    {
        $this->service->expects($this->once())->method("get")->willReturn([]);
        $this->request->method("getContent")->willReturn([]);
        $this->assertEquals($this->service->get($this->request), []);
    }

    public function testMustReturnArrayWithResultsDueToValidRequest()
    {
        $this->service->expects($this->once())->method("get")->willReturn([ "1", "2" ]);
        $this->request->method("getContent")->willReturn([ "1", "2" ]);
        $this->assertEquals($this->service->get($this->request), [ "1", "2" ]);
    }
}