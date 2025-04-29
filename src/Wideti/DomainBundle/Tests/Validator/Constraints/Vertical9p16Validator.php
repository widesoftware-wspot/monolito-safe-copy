<?php

namespace Wideti\DomainBundle\Tests\Validator\Constraints;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Validator\Constraints\Vertical9p16Validator;

class Vertical9p16ValidatorTest extends WebTestCase
{

    public function testMustValidateCorrectResolution1920x1080()
    {
        $width = 1080;
        $height = 1920;
        $result = Vertical9p16Validator::isValidResolution($width, $height);
        $this->assertEquals(true,$result);
    }

    public function testMustValidateCorrectResolution1024x576()
    {
        $width = 576;
        $height = 1024;
        $result = Vertical9p16Validator::isValidResolution($width, $height);
        $this->assertEquals(true,$result);
    }

    public function testMustInvalidateWrongResolution800x600()
    {
        $width = 800;
        $height = 600;
        $result = Vertical9p16Validator::isValidResolution($width, $height);
        $this->assertEquals(false, $result);
    }

    public function testMustInvalidateWrongResolution1920x1233()
    {
        $width = 1920;
        $height = 1233;
        $result = Vertical9p16Validator::isValidResolution($width, $height);
        $this->assertEquals(false, $result);
    }

    public function testMustValidateCorrectNumberString()
    {
        $width = "1080";
        $height = "1920";
        $result = Vertical9p16Validator::isValidResolution($width, $height);
        $this->assertEquals(true,$result);
    }

    public function testMustThrowInvalidArgumentExceptionIfNotValidNumberString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $width = "1080v";
        $height = "1920a";
        Vertical9p16Validator::isValidResolution($width, $height);
    }

    public function testMustNotAcceptZeroInParameters()
    {
        $this->expectException(\InvalidArgumentException::class);
        $width = "1080";
        $height = 0;
        Vertical9p16Validator::isValidResolution($width, $height);
    }
}