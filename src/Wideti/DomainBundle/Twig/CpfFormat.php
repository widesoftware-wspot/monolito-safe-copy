<?php

namespace Wideti\DomainBundle\Twig;

class CpfFormat extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('cpfFormat', array($this, 'applyCpfFormat')),
        );
    }

    private function mask($val, $mask)
    {
        $maskared = '';
        $k = 0;
        for ($i = 0; $i <= strlen($mask)-1; $i++) {
            if ($mask[$i] == '#') {
                if (isset($val[$k])) {
                    $maskared .= $val[$k++];
                }
            } else {
                if (isset($mask[$i])) {
                    $maskared .= $mask[$i];
                }
            }
        }

        return $maskared;
    }

    public function applyCpfFormat($cpf)
    {
        $return = $this->mask($cpf, '###.###.###-##');

        return $return;
    }

    public function getName()
    {
        return 'cpf_format';
    }
}
