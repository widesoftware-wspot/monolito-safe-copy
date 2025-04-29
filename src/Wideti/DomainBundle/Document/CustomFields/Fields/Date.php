<?php

namespace Wideti\DomainBundle\Document\CustomFields\Fields;

use Wideti\DomainBundle\Document\CustomFields\Transformer\MongoDateTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class Date extends FieldType
{
    protected $options = [];

    public function __construct()
    {
        parent::__construct();

        $this->type = DateType::class;

        $this->options = [
            'label_attr' => array(
                'class' => 'control-label',
            ),
            'required'  => true,
            'attr' => array(
                'class'     => 'span12 numbers',
                'data-type' => 'date'
            ),
            "widget" => "single_text",
            "format" => "dd/MM/yyyy"
        ];

        $this->setModelTransformer(new MongoDateTransformer());
    }
}
