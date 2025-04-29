<?php

namespace Wideti\WebFrameworkBundle\Aware;

use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\FrontendBundle\Form\SignUpConfirmationType;

/**
 * Symfony Server Setup: - [ setForm, ["@form.factory"] ]
 */
trait FormAware
{
    /**
     * @var FormFactory
     */
    protected $form;

    public function setForm(FormFactory $form)
    {
        $this->form = $form;
    }
}
