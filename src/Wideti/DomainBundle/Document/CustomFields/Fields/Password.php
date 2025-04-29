<?php

namespace Wideti\DomainBundle\Document\CustomFields\Fields;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class Password extends FieldType
{
    protected $options = [
        'type' => PasswordType::class,
        'invalid_message' => 'As senhas devem coincidir.',
        'required' => true,
        'mapped' => false,
        'first_options'  => array(
            'label' => 'Senha',
            'attr' => array(
                'class' => 'span4',
                'maxlength' => 30,
            ),
            'label_attr' => array(
                'class' => 'control-label',
            )
        ),
        'second_options' => array(
            'label' => 'Repita a senha',
            'attr' => array(
                'class' => 'span4',
                'maxlength' => 30,
            ),
            'label_attr' => array(
                'class' => 'control-label',
            )
        )
    ];

    public function __construct()
    {
        parent::__construct();

        $this->type = RepeatedType::class;

        $this->addValidator([
            new NotBlank()
        ]);
    }
}