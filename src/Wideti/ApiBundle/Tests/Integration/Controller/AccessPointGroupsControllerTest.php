<?php

namespace Wideti\ApiBundle\Controller;

use Wideti\ApiBundle\Tests\Integration\IntegrationTestCase;

class AccessPointGroupsControllerTest extends IntegrationTestCase
{

    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testMustListAllAccessPointGroups()
    {
        $crawler = $this->httpClient->request('GET', '/api/access-point-groups');
        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(count($result) > 0);
        $this->assertArrayHasKey('id', $result[0]);
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertArrayHasKey('isDefault', $result[0]);
        $this->assertArrayHasKey('template', $result[0]);
        $this->assertArrayHasKey('id', $result[0]['template']);
        $this->assertArrayHasKey('name', $result[0]['template']);
    }

    public function testMustListAllAccessPointGroupsWithPaginationParams()
    {
        $crawler = $this->httpClient->request('GET', '/api/access-point-groups?page=0&limit=1');
        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(count($result) > 0);
        $this->assertArrayHasKey('id', $result[0]);
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertArrayHasKey('isDefault', $result[0]);
        $this->assertArrayHasKey('template', $result[0]);
        $this->assertArrayHasKey('id', $result[0]['template']);
        $this->assertArrayHasKey('name', $result[0]['template']);
    }

    public function testMustEmptyListAccessPointGroupsWithPaginationParamsBlankPage()
    {
        $crawler = $this->httpClient->request('GET', '/api/access-point-groups?page=10&limit=200');
        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertCount(0,$result);
        $this->assertEmpty($result);
    }

    public function testMustFindAccessPointGroupByName()
    {
        $crawler = $this->httpClient->request('GET', '/api/access-point-groups?name=grupo padrao');
        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(count($result) > 0);
        $this->assertArrayHasKey('id', $result[0]);
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertArrayHasKey('isDefault', $result[0]);
        $this->assertArrayHasKey('template', $result[0]);
        $this->assertArrayHasKey('id', $result[0]['template']);
        $this->assertArrayHasKey('name', $result[0]['template']);
    }

    public function testMustNotFoundAccessPointGroupByName()
    {
        $crawler = $this->httpClient->request('GET', '/api/access-point-groups?name=notFound');
        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertInternalType('array', $result);
    }
}
