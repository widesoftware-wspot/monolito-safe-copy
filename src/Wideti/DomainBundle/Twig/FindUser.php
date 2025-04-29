<?php

namespace Wideti\DomainBundle\Twig;

use Wideti\DomainBundle\Entity\Users;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;

class FindUser extends \Twig_Extension
{
    use EntityManagerAware;

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('find_user', array($this, 'getUsername')),
        );
    }

    public function getUsername($id)
    {
        /**
         * @var Users $user
         */
        $user = $this->em->getRepository("DomainBundle:Users")->findOneById($id);

        if ($user) {
            return $user->getUsername();
        }

        return "N/I";
    }

    public function getName()
    {
        return 'find_user';
    }
}
