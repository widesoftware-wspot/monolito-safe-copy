<?php
namespace Wideti\DomainBundle\Service\EntityLogger\Computers;

use Wideti\DomainBundle\Service\Configuration\Dto\ConfigurationDto;
use Wideti\DomainBundle\Service\EntityLogger\Helpers\TranslateChanges;

class Configuration implements ComputerInterface
{
    use ComputerAware;

    public function __construct(ConfigurationDto $entity, array $changes)
    {
        $this->entity  = $entity;
        $this->changes = $changes;
    }

    public function compute()
    {
        $this->changes['field'] = $this->entity->getLabel();

        if ($this->entity->getType() == 'checkbox') {
            $boolBefore = $this->changes['changes']['value'][0];
            $booAfter   = $this->changes['changes']['value'][1];
            if ((bool)$boolBefore == (bool)$booAfter) {
                return null;
            }
            $this->changes['changes']['value'][0] = TranslateChanges::yesOrNo($this->changes['changes']['value'][0]);
            $this->changes['changes']['value'][1] = TranslateChanges::yesOrNo($this->changes['changes']['value'][1]);
        }

        if ($this->entity->getType() == 'text') {
            $this->changes['changes']['value'][0] = TranslateChanges::emptyOrNot($this->changes['changes']['value'][0]);
            $this->changes['changes']['value'][1] = TranslateChanges::emptyOrNot($this->changes['changes']['value'][1]);
        }

        return $this->changes;
    }
}
