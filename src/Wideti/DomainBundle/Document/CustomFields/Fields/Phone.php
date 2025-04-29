<?php

namespace Wideti\DomainBundle\Document\CustomFields\Fields;

use Symfony\Component\Validator\Constraints\NotBlank;
use Wideti\DomainBundle\Document\CustomFields\Transformer\PhoneNumberTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class Phone extends FieldType
{
    protected $options = [
        'label_attr' => array(
            'class' => 'control-label',
        ),
        'required'  => true,
        'attr' => array(
            'class'     => 'span12 numbers',
            'data-type' => 'phone'
        ),
    ];

    public function __construct()
    {
        parent::__construct();

        $this->type = TextType::class;

        $this->addValidator([
            new NotBlank()
        ]);

        $this->setModelTransformer(new PhoneNumberTransformer());
    }
}