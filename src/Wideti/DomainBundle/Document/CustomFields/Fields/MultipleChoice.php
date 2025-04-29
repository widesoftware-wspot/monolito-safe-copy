<?php

namespace Wideti\DomainBundle\Document\CustomFields\Fields;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class MultipleChoice extends FieldType
{
    protected $options = [
      'required'      => false,
      'placeholder'   => 'Selecione',
      'multiple' => true,
      'attr'     => [
        'class' => 'span10',
        'autocomplete' => 'off'
      ],
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