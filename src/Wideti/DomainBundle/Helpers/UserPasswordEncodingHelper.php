<?php

namespace Wideti\DomainBundle\Helpers;

use Wideti\DomainBundle\Entity\Users;

trait UserPasswordEncodingHelper
{
    /**
     * @param  Users $user
     * @param $plainPassword
     * @return mixed
     */
    public function encodeUserPassword(Users $user, $plainPassword)
    {
        $encoder = $this->container
                        ->get('security.encoder_factory')
                        ->getEncoder($user);

        return $encoder->encodePassword($plainPassword, $user->getSalt());
    }
}
