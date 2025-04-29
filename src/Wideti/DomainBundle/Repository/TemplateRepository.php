<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\Client;

class TemplateRepository extends EntityRepository
{
    public function defaultTemplate($client)
    {
        $qb = $this->createQueryBuilder('t');

        $qb->where('t.client = :client');
        $qb->setParameter('client', $client);
        $qb->setMaxResults(1);

        $template = $qb->getQuery()->getSingleResult();

        return $template;
    }

    public function getTemplateByCampaign($id)
    {
        $qb = $this->createQueryBuilder('t');

        $qb->where('t.id = :id');
        $qb->setParameter('id', $id);

        $template = $qb->getQuery()->getSingleResult();

        return $template;
    }

    public function getTemplateByAccessPoint($id)
    {
        $qb = $this->createQueryBuilder('t');

        $qb->where('t.id = :id');
        $qb->setParameter('id', $id);

        $template = $qb->getQuery()->getSingleResult();

        return $template;
    }

    public function getTemplateByAccessPointGroup($id)
    {
        $qb = $this->createQueryBuilder('t');

        $qb->where('t.id = :id');
        $qb->setParameter('id', $id);

        $template = $qb->getQuery()->getSingleResult();

        return $template;
    }

    public function getClientIdsThatHaveCreatedTemplates()
    {
        $query = "SELECT client_id
                  FROM template
                  INNER JOIN clients ON template.client_id = clients.id
                  WHERE template.updated <> template.created AND clients.status = 1
                  GROUP BY clients.company ASC
                  HAVING COUNT(template.client_id) >= 1";
        $connection = $this->getEntityManager()->getConnection();
        $statement = $connection->prepare($query);
        $statement->execute();
        $result = $statement->fetchAll();

        return $result;
    }

    public function exists($id, Client $client)
    {
        $qb = $this->createQueryBuilder('template')
            ->select('template')
            ->where('template.client = :client')
            ->andWhere('template.id = :id')
            ->setParameter('client', $client)
            ->setParameter('id', $id);

        $result = $qb
            ->getQuery()
            ->execute();

        return !empty($result);
    }


}



