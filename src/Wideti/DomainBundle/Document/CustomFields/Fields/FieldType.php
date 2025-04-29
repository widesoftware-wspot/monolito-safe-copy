<?php

namespace Wideti\DomainBundle\Document\CustomFields\Fields;

use Symfony\Component\Form\DataTransformerInterface;

class FieldType implements FieldTypeInterface
{
    protected $validator;
    protected $type;
    protected $options = [];

    /**
     * @var DataTransformerInterface
     */
    protected $modelTransformer = null;

    public function __construct()
    {
        $this->validator = [];
    }

    public function setModelTransformer(DataTransformerInterface $transformer)
    {
        $this->modelTransformer = $transformer;
    }

    public function getModelTransformer()
    {
        return $this->modelTransformer;
    }

    public function addValidator($validator)
    {
        $this->validator = $validator;
    }

    public function getValidators()
    {
        return array_values($this->validator);
    }

    public function getType()
    {
        return $this->type;
    }

    public function getOptions()
    {
        return $this->options;
    }
}