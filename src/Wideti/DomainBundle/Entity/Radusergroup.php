<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="radusergroup")
 * @ORM\Entity()
 */
class Radusergroup
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="username", type="string", length=100)
     */
    private $username;

    /**
     * @ORM\Column(name="groupname", type="string", length=100)
     */
    private $groupname;

    /**
     * @ORM\Column(name="priority", type="string", length=100)
     */
    private $priority;

    /**
     * @ORM\Column(name="observacoes", type="string", length=100)
     */
    private $observacoes;

    /**
     * @ORM\Column(name="data_bloqueio", type="datetime")
     */
    private $dataBloqueio;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getGroupname()
    {
        return $this->groupname;
    }

    public function setGroupname($groupname)
    {
        $this->groupname = $groupname;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    public function getObservacoes()
    {
        return $this->observacoes;
    }

    public function setObservacoes($observacoes)
    {
        $this->observacoes = $observacoes;
    }

    public function getDataBloqueio()
    {
        return $this->dataBloqueio;
    }

    public function setDataBloqueio($dataBloqueio)
    {
        $this->dataBloqueio = $dataBloqueio;
    }
}
