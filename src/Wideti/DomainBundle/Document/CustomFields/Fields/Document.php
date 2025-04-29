<?php

namespace Wideti\DomainBundle\Document\CustomFields\Fields;

use Doctrine\Bundle\MongoDBBundle\Validator\Constraints\Unique;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class Document extends FieldType
{
    protected $options = [
        'label_attr' => array(
            'class' => 'control-label label_document',
        ),
        'required'  => true,
        'attr' => array(
            'class'     => 'span12 numbers',
            'data-type' => 'document'
        ),
    ];

    public function __construct()
    {
        parent::__construct();

        $this->type = TextType::class;

        $this->addValidator([
            new NotBlank()
        ]);
    }
}
