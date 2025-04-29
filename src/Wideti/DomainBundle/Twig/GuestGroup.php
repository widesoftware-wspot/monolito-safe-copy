<?php

namespace Wideti\DomainBundle\Twig;

use Wideti\WebFrameworkBundle\Aware\MongoAware;

class GuestGroup extends \Twig_Extension
{
    use MongoAware;

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('guest_group', array($this, 'getGuestGroup')),
        );
    }

    public function getGuestGroup($guest)
    {
        if ($guest->getGroup()) {
            $group = $this->mongo->getRepository('DomainBundle:Group\Group')->findOneByShortcode($guest->getGroup());
            if ($group) {
	            return $group->getName();
            }
        }
        return '';
    }

    public function getName()
    {
        return 'guest_group';
    }
}
