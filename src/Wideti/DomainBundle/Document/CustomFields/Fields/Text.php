<?php

namespace Wideti\DomainBundle\Document\CustomFields\Fields;

use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class Text extends FieldType
{
    protected $options = [
        'label_attr' => array(
            'class' => 'control-label',
        ),
        'required'  => true,
        'attr' => array(
            'class' => 'span12',
        ),
    ];

    public function __construct()
    {
        parent::__construct();

        $this->type = TextType::class;
    }
}
