<?php


namespace Wideti\DomainBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="legal_kind")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\LegalKindsRepository")
 */
class LegalKinds
{
    const LEGITIMO_INTERESSE = 'legitimo_interesse';
    const TERMO_CONSENTIMENTO = 'termo_consentimento';

    /**
     * @ORM\Column(name="`key`", type="string", length=255)
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $key;

    /**
     * @ORM\Column(name="kind", type="string", length=100, nullable=false)
     */
    private $kind;

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getKind()
    {
        return $this->kind;
    }
}