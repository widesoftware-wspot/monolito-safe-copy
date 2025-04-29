<?php

namespace Wideti\DomainBundle\Service\Social\Facebook;

use Facebook\PersistentData\PersistentDataInterface;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class FacebookPersistentDataHandler implements PersistentDataInterface
{
    use SessionAware;

    protected $sessionPrefix = 'FBRLH_';

    /**
     * Get a value from a persistent data store.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        $this->session->get($this->sessionPrefix . $key);
    }

    /**
     * Set a value in the persistent data store.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this->session->set($this->sessionPrefix . $key, $value);
    }
}
