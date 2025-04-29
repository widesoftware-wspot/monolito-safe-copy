<?php

namespace Wideti\DomainBundle\Document\CustomFields\Fields;

use Symfony\Component\Validator\Constraints\NotBlank;
use Wideti\DomainBundle\Validator\Constraints\DomainEmailIsValid;
use Wideti\DomainBundle\Validator\Constraints\EmailIsValid;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

class Email extends FieldType
{
    protected $options = [
        'label_attr' => array(
            'class' => 'control-label',
        ),
        'required'  => true,
        'attr' => array(
            'class'     => 'span12',
            'data-type' => 'email'
        ),
    ];

    public function __construct()
    {
        parent::__construct();

        $this->type = EmailType::class;

        $this->addValidator([
            new NotBlank(),
            new \Symfony\Component\Validator\Constraints\Email([
                'checkMX' => true
            ]),
            new DomainEmailIsValid(),
            new EmailIsValid()
        ]);
    }
}
