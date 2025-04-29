<?php

namespace Wideti\DomainBundle\Twig;

class FormatByteExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('format_bytes', array($this, 'convertByteToGBorMB')),
        );
    }

    public function convertByteToGBorMB($bytes)
    {
        $mb = ($bytes / 1024 / 1024);

        if ($mb >= 100000000) {
            $result = number_format(($mb/1024/1024/1024), 2, '.', '').' PB';
        } else if ($mb >= 1000000 && $mb <= 100000000) {
            $result = number_format(($mb/1024/1024), 2, '.', '').' TB';
        } else if ($mb >= 1024 && $mb <= 1000000) {
            $result = number_format(($mb/1024), 2, '.', '').' GB';
        } else if (substr($mb, 0, 1) != 0) {
            $result = number_format($mb, 0, '.', '').' MB';
        } else {
            $result = number_format($mb, 2, '.', '').' MB';
        }

        return $result;
    }

    public function getName()
    {
        return 'convert_byte_to_gb_or_mb';
    }
}
