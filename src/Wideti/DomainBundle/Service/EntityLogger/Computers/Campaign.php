<?php
namespace Wideti\DomainBundle\Service\EntityLogger\Computers;

use Doctrine\ORM\UnitOfWork;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Service\EntityLogger\Helpers\TranslateChanges;

class Campaign implements ComputerInterface
{
    use ComputerAware;

    /**
     * @var UnitOfWork
     */
    protected $uow;

    public function __construct(\Wideti\DomainBundle\Entity\Campaign $entity, array $changes)
    {
        $this->entity  = $entity;
        $this->changes = $changes;
    }

    public function compute()
    {
        $this->changes['campaign'] = $this->entity->getName();

        if (isset($this->changes['changes']['preLoginImageTime'])) {
            $this->changes['changes']['preLoginImageTime'][1] = (int)$this->changes['changes']['preLoginImageTime'][1];
        }

        if (isset($this->changes['changes']['posLoginImageTime'])) {
            $this->changes['changes']['posLoginImageTime'][1] = (int)$this->changes['changes']['posLoginImageTime'][1];
        }

        if (isset($this->changes['changes']['status'])) {
            $this->changes['changes']['status'][0] =
                $this->entity->getStatusAsString($this->changes['changes']['status'][0]);

            $this->changes['changes']['status'][1] =
                $this->entity->getStatusAsString($this->changes['changes']['status'][1]);
        }

        if (isset($this->changes['changes']['inAccessPoints'])) {
            $before = $this->changes['changes']['inAccessPoints'][0];
            $after  = $this->changes['changes']['inAccessPoints'][1];

            if ($before == 0 || $before == '') {
                $before = "Todos";
            } else {
                $before = "Específicos";
            }
            if ($after == 0 || $after == '') {
                $after = "Todos";
            } else {
                $after = "Específicos";

                $newAps = [];

                foreach ($this->entity->getAccessPoints() as $ap) {
                    $newAps[] = $ap->getFriendlyName();
                }

                $after .= " (".implode(", ", $newAps).")";
            }

            $this->changes['changes']['inAccessPoints'][0] = $before;
            $this->changes['changes']['inAccessPoints'][1] = $after;

            unset($before);
            unset($after);
        }

        if (isset($this->changes['changes']['endDate'])) {
            if (!empty($this->changes['changes']['endDate'][0])) {
                $this->changes['changes']['endDate'][0] = $this->changes['changes']['endDate'][0]->format('d/m/Y');
            }

            $this->changes['changes']['endDate'][1] = $this->changes['changes']['endDate'][1]->format('d/m/Y');
        }

        if (isset($this->changes['changes']['template'])) {
            if (!empty($this->changes['changes']['template'][0])) {
                $this->changes['changes']['template'][0] = $this->changes['changes']['template'][0]->getName();
            }

            if (!empty($this->changes['changes']['template'][1])) {
                $this->changes['changes']['template'][1] = $this->changes['changes']['template'][1]->getName();
            }
        }

        $snapshotAps = [];
        $changedAps  = [];

        foreach ($this->uow->getScheduledCollectionUpdates() as $collection) {
            foreach ($collection->getSnapshot() as $snapshot) {
                if ($snapshot instanceof AccessPoints) {
                    $snapshotAps[] = $snapshot->getFriendlyName();
                }
            }

            foreach ($collection->getInsertDiff() as $diff) {
                if ($diff instanceof AccessPoints) {
                    $changedAps[] = $diff->getFriendlyName();
                }
            }
        }

        if (count($changedAps) > 0) {
            if (count($snapshotAps) == 0) {
                $this->changes['changes']['accessPoints'][0] = "Nenhuma";
            } else {
                $this->changes['changes']['accessPoints'][0] = implode(', ', $snapshotAps);
            }
            $this->changes['changes']['accessPoints'][1] = implode(', ', $changedAps);
        }

        return $this->changes;
    }

    public function setUow($uow)
    {
        $this->uow = $uow;
    }
}