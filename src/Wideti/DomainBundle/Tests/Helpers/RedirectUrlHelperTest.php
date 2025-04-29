<?php

namespace Wideti\DomainBundle\Tests\Helpers;

use Wideti\DomainBundle\Helpers\RedirectUrlHelper;
use Wideti\DomainBundle\Tests\WspotTestCase;

class RedirectUrlHelperTest extends WspotTestCase
{
    /**
     * @var RedirectUrlHelper
     */
    private $urlHelper;

    public function setUp()
    {
        $this->urlHelper = new RedirectUrlHelper();
    }

    public function testMustReturnFalseInvalidURL()
    {
        $url = "www.google.com";

        $isValid = $this->urlHelper->isValid($url);

        $this->assertEquals(false, $isValid);
    }

    public function testMustReturnTrueInValidURL()
    {
        $url = "https://www.google.com.br";
        $isValid = $this->urlHelper->isValid($url);
        $this->assertEquals(true, $isValid);
    }

    public function testIfURLIsValidMustReturnSameStringValue()
    {
        $url = "https://www.google.com.br";
        $newUrl = $this->urlHelper->getValidUrl($url);
        $this->assertEquals($url, $newUrl);
    }

    public function testMustReturnCorrectURLIfParameterUrlIsInvalid()
    {
        $url = "www.google.com.br";
        $newUrl = $this->urlHelper->getValidUrl($url);
        $this->assertEquals("https://" . $url, $newUrl);
    }

    public function testMustReturnCorrectURLIfParameterUrlIsRealyWrongURL()
    {
        $url = "22222";
        $newUrl = $this->urlHelper->getValidUrl($url);
        $this->assertEquals("https://" . $url, $newUrl);
    }
}
