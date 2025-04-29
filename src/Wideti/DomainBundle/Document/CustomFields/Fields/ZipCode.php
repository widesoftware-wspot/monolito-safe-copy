<?php

namespace Wideti\DomainBundle\Document\CustomFields\Fields;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ZipCode extends FieldType
{
    protected $options = [
        'label_attr' => array(
            'class' => 'control-label',
        ),
        'required'  => true,
        'attr' => array(
            'class'     => 'span12 numbers',
            'data-type' => 'zipcode'
        ),
    ];

    public function __construct()
    {
        parent::__construct();

        $this->type = TextType::class;
    }
}