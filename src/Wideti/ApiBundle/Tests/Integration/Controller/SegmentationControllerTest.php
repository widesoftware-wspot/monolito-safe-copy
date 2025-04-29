<?php

namespace Wideti\ApiBundle\Tests\Integration\Controller;

use Faker\Factory;
use Symfony\Component\HttpFoundation\Response;
use Wideti\ApiBundle\Tests\Integration\IntegrationTestCase;
use Wideti\DomainBundle\Document\Segmentation\Segmentation;
use Wideti\DomainBundle\Service\Segmentation\Filter\Filter;

class SegmentationControllerTest extends IntegrationTestCase
{
    private $faker;

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

    public function testMustReturnPreviewSegmentationSuccess()
    {
        $filter = [
            "client" => 1,
            "type"   => Filter::TYPE_ALL,
            "items"  => [
                "default" => [
                    "registrations" => [
                        "identifier" => "registrations",
                        "equality" => "RANGE",
                        "type" => "date",
                        "value" => "2018-07-01|2018-07-20"
                    ]
                ]
            ]
        ];

        $this->httpClient->request(
            'POST',
            '/api/segmentation/preview',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($filter)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertInternalType('array', $result);
        $this->assertSegmentationPreviewExists($result);
    }

    public function testMustReturnExceptionMissingFieldsOnPreview()
    {
        $filter = [
            "items" => [
                "default" => [
                    "registrations" => [
                        "identifier" => "registrations",
                        "equality" => "RANGE",
                        "type" => "date",
                        "value" => "2018-07-01|2018-07-20"
                    ]
                ]
            ]
        ];

        $this->httpClient->request(
            'POST',
            '/api/segmentation/preview',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($filter)
        );

        $response = $this->httpClient->getResponse();
        $content = json_decode($response->getContent(), true)[0];

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertArrayHasKey('missingFields', $content);
        $this->assertEquals('Campos obrigatórios não estão sendo enviados', $content['message']);
    }

    public function testMustReturnExceptionEqualityNotExistsOnPreview()
    {
        $filter = [
            "client" => 1,
            "type"   => Filter::TYPE_ALL,
            "items"  => [
                "default" => [
                    "not_exists" => [
                        "identifier" => "not_exists",
                        "equality" => "RANGE",
                        "type" => "date",
                        "value" => "2018-07-01|2018-07-20"
                    ]
                ]
            ]
        ];

        $this->httpClient->request(
            'POST',
            '/api/segmentation/preview',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($filter)
        );

        $response = $this->httpClient->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertArrayHasKey('error', $content);
        $this->assertEquals('Ocorreu um erro, nossa equipe já foi notificada.', $content['error']);
    }

    public function testMustCreateSegmentationSuccess()
    {
        $body = [
            "title" => "Segmentação por visitantes cadastrados",
            "filter" => [
                [
                    "type" => "ALL",
                    "default" => [
                        "registrations" => [
                            "identifier" => "registrations",
                            "equality" => "RANGE",
                            "type" => "date",
                            "value" => "2018-07-01|2018-07-20"
                        ]
                    ]
                ]
            ]
        ];

        $this->httpClient->request(
            'POST',
            '/api/segmentation',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($body)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('filter', $result);
        $this->assertArrayHasKey('created', $result);
        $this->assertArrayHasKey('updated', $result);
    }

    public function testMustReturnExceptionMissingTitleFieldOnCreate()
    {
        $body = [
            "filter" => [
                [
                    "type" => "ALL",
                    "default" => [
                        "registrations" => [
                            "identifier" => "registrations",
                            "equality" => "RANGE",
                            "type" => "date",
                            "value" => "2018-07-01|2018-07-20"
                        ]
                    ]
                ]
            ]
        ];

        $this->httpClient->request(
            'POST',
            '/api/segmentation',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($body)
        );

        $response = $this->httpClient->getResponse();
        $content = json_decode($response->getContent(), true)[0];

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertArrayHasKey('missingFields', $content);
        $this->assertEquals('Campos obrigatórios não estão sendo enviados', $content['message']);
        $this->assertContains('title', $content['missingFields']['fields']);
    }

    public function testMustReturnExceptionMissingFilterFieldOnCreate()
    {
        $body = [
            "title" => "Segmentação por visitantes cadastrados"
        ];

        $this->httpClient->request(
            'POST',
            '/api/segmentation',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($body)
        );

        $response = $this->httpClient->getResponse();
        $content = json_decode($response->getContent(), true)[0];

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertArrayHasKey('missingFields', $content);
        $this->assertEquals('Campos obrigatórios não estão sendo enviados', $content['message']);
        $this->assertContains('filter', $content['missingFields']['fields']);
    }

    public function testMustReturnExceptionFilterFieldIsEmptyOnCreate()
    {
        $body = [
            "title" => "Segmentação por visitantes cadastrados",
            "filter" => []
        ];

        $this->httpClient->request(
            'POST',
            '/api/segmentation',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($body)
        );

        $response = $this->httpClient->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertArrayHasKey('error', $content);
        $this->assertEquals('Ocorreu um erro, nossa equipe já foi notificada.', $content['error']);
    }

    public function testMustEditSegmentationSuccess()
    {
        $body = [
            "title" => "Segmentação por visitantes cadastrados em Janeiro",
            "status" => Segmentation::ACTIVE,
            "filter" => [
                [
                    "type" => "ALL",
                    "default" => [
                        "registrations" => [
                            "identifier" => "registrations",
                            "equality" => "RANGE",
                            "type" => "date",
                            "value" => "2018-01-01|2018-02-01"
                        ]
                    ]
                ]
            ]
        ];

        $segmentation = $this->getExistentSegmentation();
        $segmentationId = $segmentation['id'];

        $this->httpClient->request(
            'POST',
            "/api/segmentation/edit/{$segmentationId}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($body)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('filter', $result);
        $this->assertArrayHasKey('created', $result);
        $this->assertArrayHasKey('updated', $result);
    }

    public function testMustReturnExceptionMissingTitleFieldOnEdit()
    {
        $body = [
            "status" => Segmentation::ACTIVE,
            "filter" => [
                [
                    "type" => "ALL",
                    "default" => [
                        "registrations" => [
                            "identifier" => "registrations",
                            "equality" => "RANGE",
                            "type" => "date",
                            "value" => "2018-01-01|2018-02-01"
                        ]
                    ]
                ]
            ]
        ];

        $segmentation = $this->getExistentSegmentation();
        $segmentationId = $segmentation['id'];

        $this->httpClient->request(
            'POST',
            "/api/segmentation/edit/{$segmentationId}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($body)
        );

        $response = $this->httpClient->getResponse();
        $content = json_decode($response->getContent(), true)[0];

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertArrayHasKey('missingFields', $content);
        $this->assertEquals('Campos obrigatórios não estão sendo enviados', $content['message']);
        $this->assertContains('title', $content['missingFields']['fields']);
    }

    public function testMustReturnExceptionMissingStatusFieldOnEdit()
    {
        $body = [
            "title" => "Segmentação por visitantes cadastrados em Janeiro",
            "filter" => [
                [
                    "type" => "ALL",
                    "default" => [
                        "registrations" => [
                            "identifier" => "registrations",
                            "equality" => "RANGE",
                            "type" => "date",
                            "value" => "2018-01-01|2018-02-01"
                        ]
                    ]
                ]
            ]
        ];

        $segmentation = $this->getExistentSegmentation();
        $segmentationId = $segmentation['id'];

        $this->httpClient->request(
            'POST',
            "/api/segmentation/edit/{$segmentationId}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($body)
        );

        $response = $this->httpClient->getResponse();
        $content = json_decode($response->getContent(), true)[0];

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertArrayHasKey('missingFields', $content);
        $this->assertEquals('Campos obrigatórios não estão sendo enviados', $content['message']);
        $this->assertContains('status', $content['missingFields']['fields']);
    }

    public function testMustReturnExceptionMissingFilterFieldOnEdit()
    {
        $body = [
            "title" => "Segmentação por visitantes cadastrados em Janeiro",
            "status" => Segmentation::ACTIVE
        ];

        $segmentation = $this->getExistentSegmentation();
        $segmentationId = $segmentation['id'];

        $this->httpClient->request(
            'POST',
            "/api/segmentation/edit/{$segmentationId}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($body)
        );

        $response = $this->httpClient->getResponse();
        $content = json_decode($response->getContent(), true)[0];

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertArrayHasKey('missingFields', $content);
        $this->assertEquals('Campos obrigatórios não estão sendo enviados', $content['message']);
        $this->assertContains('filter', $content['missingFields']['fields']);
    }

    public function testMustReturnExceptionFilterFieldIsEmptyOnEdit()
    {
        $body = [
            "title" => "Segmentação por visitantes cadastrados em Janeiro",
            "status" => Segmentation::ACTIVE,
            "filter" => []
        ];

        $segmentation = $this->getExistentSegmentation();
        $segmentationId = $segmentation['id'];

        $this->httpClient->request(
            'POST',
            "/api/segmentation/edit/{$segmentationId}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($body)
        );

        $response = $this->httpClient->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertArrayHasKey('error', $content);
        $this->assertEquals('Ocorreu um erro, nossa equipe já foi notificada.', $content['error']);
    }

    public function testMustReturnAllSegmentations()
    {
        $this->httpClient->request(
            'GET',
            '/api/segmentation'
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
    }

    public function testMustDeleteSegmentationSuccess()
    {
        $segmentation = $this->getExistentSegmentation();
        $segmentationId = $segmentation['id'];

        $this->httpClient->request(
            'DELETE',
            "/api/segmentation/{$segmentationId}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('msg', $result);
        $this->assertEquals('Registro removido com sucesso.', $result['msg']);
    }

    public function testMustReturnExceptionIdNullOnDelete()
    {
        $this->httpClient->request(
            'DELETE',
            "/api/segmentation/",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $response->getStatusCode());
    }

    public function testMustReturnExceptionSegmentationNotFoundOnDelete()
    {
        $segmentationId = 'abc123';

        $this->httpClient->request(
            'DELETE',
            "/api/segmentation/{$segmentationId}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Registro não encontrado.', $result['error']);
    }

    public function testMustRequestExportSuccessfully()
    {
        $segmentation = $this->getExistentSegmentation();
        $segmentationId = $segmentation['id'];

        $body = [
            "client" => 1,
            "segmentationId" => $segmentationId,
            "recipient" => "developers@widesoftware.com.br"
        ];

        $this->httpClient->request(
            'POST',
            "/api/segmentation/export",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($body)
        );

        $response = $this->httpClient->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertArrayHasKey('msg', $content);
        $this->assertEquals('A exportação está sendo processada. Em breve o arquivo será enviado para seu e-mail.', $content['msg']);
    }

    public function testMustRequestExportExceptionDocumentNotFound()
    {
        $body = [
            "client" => 1,
            "segmentationId" => "123",
            "recipient" => "developers@widesoftware.com.br"
        ];

        $this->httpClient->request(
            'POST',
            "/api/segmentation/export",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($body)
        );

        $response = $this->httpClient->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertArrayHasKey('error', $content);
        $this->assertEquals('Segmentação não encontrada.', $content['error']);
    }

    /**
     * @param $result
     */
    private function assertSegmentationPreviewExists($result)
    {
        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('preview', $result);
    }

    public function testMustReturnConvertedSchema()
    {
        $body = [
            "client" => 1,
            "title" => "Segmentação Dev",
            "status" => "active",
            "filter" => [
                [
                    "type" => "ALL",
                    "default" => [
                        "birthdays" => [
                            "identifier" => "data_nascimento",
                            "equality" => "RANGE",
                            "type" => "date",
                            "value" => "2018-01-01|2018-02-01"
                        ],
                        "registrations" => [
                            "identifier" => "registrations",
                            "equality" => "RANGE",
                            "type" => "date",
                            "value" => "2018-01-01|2018-02-01"
                        ]
                    ]
                ]
            ]
        ];

        $this->httpClient->request(
            'POST',
            "/api/segmentation/convert-to-schema",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($body)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('type', $result);
    }

    public function testMustReturnExceptionOnConvertedSchema()
    {
        $body = [
            "client" => 1,
            "title" => "Segmentação Dev",
            "status" => "active",
            "filter" => [
                [
                    "type" => "ALL",
                    "default" => [
                        "not_exists" => [
                            "identifier" => "v",
                            "equality" => "RANGE",
                            "type" => "date",
                            "value" => "2018-01-01|2018-02-01"
                        ]
                    ]
                ]
            ]
        ];

        $this->httpClient->request(
            'POST',
            "/api/segmentation/convert-to-schema",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($body)
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Ocorreu um erro, nossa equipe já foi notificada.', $result['error']);
    }

    public function testMustReturnDefaultSchema()
    {
        $this->httpClient->request(
            'GET',
            "/api/segmentation/schema",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        $result = json_decode($this->httpClient->getResponse()->getContent(), true);
        $response = $this->httpClient->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('default', $result);
    }

    private function getExistentSegmentation()
    {
        $this->httpClient->request('GET', '/api/segmentation');
        $result = json_decode($this->httpClient->getResponse()->getContent(), true);

        $this->httpClient->restart();

        return $result[0];
    }
}
