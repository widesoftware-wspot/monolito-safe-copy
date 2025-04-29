<?php

namespace Wideti\DomainBundle\Service\Sms\Dto;

class SmsBuilder
{
    public $type;
    public $content;

    /**
     * @param $type
     * @return $this
     */
    public function withType($type)
    {
         $this->type = $type;
         return $this;
    }

    /**
     * @param $content
     * @return $this
     */
    public function withContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return SmsDto
     */

    public function build()
    {
        $response = new SmsDto();
        $response->setContent($this->content);
        $response->setType($this->type);
        return $response;
    }
}
