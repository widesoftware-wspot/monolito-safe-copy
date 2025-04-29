<?php

namespace Wideti\DomainBundle\Document\CustomFields\Fields;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class Choice extends FieldType
{
    protected $options = [
        'label_attr' => array(
            'class' => 'control-label',
        ),
        'placeholder' => false,
        'required'  => true,
        'attr' => array(
            'class' => 'span12',
        )
    ];

    public function __construct()
    {
        parent::__construct();

        $this->type = ChoiceType::class;

        $this->addValidator([
            new NotBlank()
        ]);
    }
}