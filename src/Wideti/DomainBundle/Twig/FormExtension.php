<?php

namespace Wideti\DomainBundle\Twig;

class FormExtension extends \Twig_Extension
{
    public function getFunctions()
    {
        return array(
            'form_color' => new \Twig_SimpleFunction(
                'Symfony\Bridge\Twig\Node\SearchAndRenderBlockNode',
                array('is_safe' => array('html'))
            ),
            'form_upload' => new \Twig_SimpleFunction(
                'Symfony\Bridge\Twig\Node\SearchAndRenderBlockNode',
                array('is_safe' => array('html'))
            ),
        );
    }

    public function getName()
    {
        return 'wspot.twig.form_color';
    }
}
