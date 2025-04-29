<?php

namespace Wideti\DomainBundle\Tests\Helpers;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Document\CustomFields\Field;
use Wideti\DomainBundle\Service\CustomFields\Helper\CustomFieldMapper;

/**
 * Class CustomFieldMapperTest
 * @package Wideti\DomainBundle\Tests\Helpers
 */
class CustomFieldMapperTest extends WebTestCase
{
    /**
     * @var array
     */
    private $invalidParameters;
    /**
     * @var array
     */
    private $fieldSample;

    public function setUp()
    {
        $this->invalidParameters = [
            "integer" => 10,
            "string"  => "dev",
            "object"  => new \stdClass()
        ];

        $this->fieldSample = [
            [
                "id" => "5c2dfc584509dc09008b4567",
                "type" => "text",
                "identifier" => "test",
                "choices" => [],
                "isLogin" => false,
                "isUnique" => false,
                "mask" => "",
                "name" => [
                    "pt_br" => "Teste",
                    "en" => "Test",
                    "es" => "Teste"
                ],
                "validations" => [],
                "visibleForClients" => null
            ]
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected array parameter
     */
    public function testMustNotAllowIntegerParameter()
    {
        CustomFieldMapper::arrayToObjectList($this->invalidParameters["integer"]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected array parameter
     */
    public function testMustNotAllowStringParameter()
    {
        CustomFieldMapper::arrayToObjectList($this->invalidParameters["string"]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected array parameter
     */
    public function testMustNotAllowObjectParameter()
    {
        CustomFieldMapper::arrayToObjectList($this->invalidParameters["object"]);
    }

    public function testMustParseFieldsArrayToObject()
    {
        $result = CustomFieldMapper::arrayToObjectList($this->fieldSample);
        $this->assertTrue($result[0] instanceof Field);
    }
}