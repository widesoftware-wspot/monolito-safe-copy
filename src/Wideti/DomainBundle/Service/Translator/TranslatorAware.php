<?php

namespace Wideti\DomainBundle\Service\Translator;

use Symfony\Component\Translation\TranslatorInterface;

/**
 *
 * Usage: - [ setTranslatorService, ["@translator"] ]
 */
trait TranslatorAware
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $service
     */
    public function setTranslatorService(TranslatorInterface $service)
    {
        $this->translator = $service;
    }
}
