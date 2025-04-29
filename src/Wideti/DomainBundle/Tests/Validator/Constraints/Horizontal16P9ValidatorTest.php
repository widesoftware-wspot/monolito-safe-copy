<?php

namespace Wideti\DomainBundle\Tests\Validator\Constraints;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Validator\Constraints\Horizontal16p9Validator;

class Horizontal16P9ValidatorTest extends WebTestCase
{

    public function testMustValidateCorrectResolution1920x1080()
    {
        $width = 1920;
        $height = 1080;
        $result = Horizontal16p9Validator::isValidResolution($width, $height);
        $this->assertEquals(true,$result);
    }

    public function testMustValidateCorrectResolution1024x576()
    {
        $width = 1024;
        $height = 576;
        $result = Horizontal16p9Validator::isValidResolution($width, $height);
        $this->assertEquals(true,$result);
    }

    public function testMustInvalidateWrongResolution800x600()
    {
        $width = 800;
        $height = 600;
        $result = Horizontal16p9Validator::isValidResolution($width, $height);
        $this->assertEquals(false, $result);
    }

    public function testMustInvalidateWrongResolution1920x1233()
    {
        $width = 1920;
        $height = 1233;
        $result = Horizontal16p9Validator::isValidResolution($width, $height);
        $this->assertEquals(false, $result);
    }

    public function testMustValidateCorrectNumberString()
    {
        $width = "1920";
        $height = "1080";
        $result = Horizontal16p9Validator::isValidResolution($width, $height);
        $this->assertEquals(true,$result);
    }

    public function testMustThrowInvalidArgumentExceptionIfNotValidNumberString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $width = "1920a";
        $height = "1080v";
        Horizontal16p9Validator::isValidResolution($width, $height);
    }

    public function testMustNotAcceptZeroInParameters()
    {
        $this->expectException(\InvalidArgumentException::class);
        $width = "1080";
        $height = 0;
        Horizontal16p9Validator::isValidResolution($width, $height);
    }
}