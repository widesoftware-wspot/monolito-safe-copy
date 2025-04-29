<?php

namespace Wideti\DomainBundle\Document\CustomFields\Fields;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class TextArea extends FieldType
{
    public function __construct()
    {
        parent::__construct();

        $this->type = TextType::class;

        $this->addValidator([
            new NotBlank()
        ]);
    }
}