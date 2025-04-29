<?php
namespace Wideti\FrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class TelType extends AbstractType
{
    /**
     * @author  Joe Sexton <joe@webtipblog.com>
     * @return  string
     */
    public function getBlockPrefix()
    {
        return 'tel';
    }

    /**
     * @author  Joe Sexton <joe@webtipblog.com>
     * @return  string
     */
    public function getParent()
    {
        return TextType::class;
    }
}