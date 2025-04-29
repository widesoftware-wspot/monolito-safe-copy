<?php
namespace Wideti\DomainBundle\Service\EntityLogger\Computers;

use Wideti\DomainBundle\Service\EntityLogger\Helpers\TranslateChanges;

class Users implements ComputerInterface
{
    use ComputerAware;

    public function __construct(\Wideti\DomainBundle\Entity\Users $entity, array $changes)
    {
        $this->entity  = $entity;
        $this->changes = $changes;
    }

    public function compute()
    {
        $this->changes['email'] = $this->entity->getUsername();

        if (isset($this->changes['changes']['status'])) {
            $this->changes['changes']['status'][0] = $this->entity->getStatusAsString($this->changes['changes']['status'][0]);
            $this->changes['changes']['status'][1] = $this->entity->getStatusAsString($this->changes['changes']['status'][1]);
        }

        if (isset($this->changes['changes']['financialManager'])) {
            $this->changes['changes']['financialManager'][0] = TranslateChanges::yesOrNo($this->changes['changes']['financialManager'][0]);
            $this->changes['changes']['financialManager'][1] = TranslateChanges::yesOrNo($this->changes['changes']['financialManager'][1]);
        }

        if (isset($this->changes['changes']['receiveReportMail'])) {
            $this->changes['changes']['receiveReportMail'][0] = TranslateChanges::yesOrNo($this->changes['changes']['receiveReportMail'][0]);
            $this->changes['changes']['receiveReportMail'][1] = TranslateChanges::yesOrNo($this->changes['changes']['receiveReportMail'][1]);
        }

        if (isset($this->changes['changes']['reportMailLanguage'])) {
            $this->changes['changes']['reportMailLanguage'][0] = TranslateChanges::yesOrNo($this->changes['changes']['reportMailLanguage'][0]);
            $this->changes['changes']['reportMailLanguage'][1] = TranslateChanges::yesOrNo($this->changes['changes']['reportMailLanguage'][1]);
        }

        if (isset($this->changes['changes']['role'])) {
            if (!empty($this->changes['changes']['role'][0])) {
                $this->changes['changes']['role'][0] = $this->changes['changes']['role'][0]->getName();
            }

            $this->changes['changes']['role'][1] = $this->changes['changes']['role'][1]->getName();
        }

        return $this->changes;
    }
}
