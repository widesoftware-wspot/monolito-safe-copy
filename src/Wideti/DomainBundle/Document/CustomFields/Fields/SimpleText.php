<?php

namespace Wideti\DomainBundle\Document\CustomFields\Fields;

use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class SimpleText extends FieldType
{
    public function __construct()
    {
        parent::__construct();

        $this->type = TextType::class;

        $this->addValidator([]);
    }
}