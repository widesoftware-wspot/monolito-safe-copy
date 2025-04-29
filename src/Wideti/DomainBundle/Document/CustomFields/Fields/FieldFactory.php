<?php
namespace Wideti\DomainBundle\Document\CustomFields\Fields;

class FieldFactory
{
    /**
     * @var FieldType
     */
    protected $type;

    public function __construct($className)
    {
        $class      = $this->camelCase($className);
        $namespace  = 'Wideti\DomainBundle\Document\CustomFields\Fields\\' .$class;
        $this->type = new $namespace;
    }

    protected function camelCase($class)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $class)));
    }

    public function getType()
    {
        return $this->type;
    }
}
