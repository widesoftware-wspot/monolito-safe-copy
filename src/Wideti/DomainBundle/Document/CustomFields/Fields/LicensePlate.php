<?php
/**
 * Created by PhpStorm.
 * User: romani
 * Date: 28/07/16
 * Time: 08:30
 */

namespace Wideti\DomainBundle\Document\CustomFields\Fields;
use Symfony\Component\Form\Extension\Core\Type\TextType;


class LicensePlate extends FieldType
{
    protected $options = [
        'label_attr' => array(
            'class' => 'control-label',
        ),
        'required'  => true,
        'attr' => array(
            'class'     => 'span12',
            'data-type' => 'licenseplate'
        ),
    ];

    public function __construct()
    {
        parent::__construct();

        $this->type = TextType::class;
    }
}