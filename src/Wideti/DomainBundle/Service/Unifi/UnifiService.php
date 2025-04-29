<?php

namespace Wideti\DomainBundle\Service\Unifi;

use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Symfony\Component\HttpFoundation\Response;

class UnifiService
{
    use TwigAware;

    /**
     * Generate default files to be placed into Unifi Device
     * Check if you have zip library in your system. To install:
     *  - pecl install zip
     *  - Add /usr/lib/php5/20121212/zip.so in you php.ini
     * @param $domain
     * @return Response
     */
    public function generateConfigFiles($domain)
    {
        $response   = new Response();
        $zipFile    = $this->generateZipFiles($domain);

        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$domain.'.zip"');
        readfile($zipFile);

        return $response;
    }

    /**
     * Generate a Ziped file on disk with alteracoes.html and auth.html and index.html
     * @param $domain
     * @return string - File Path
     */
    public function generateZipFiles($domain)
    {
        $zip        = new \ZipArchive();
        $zipName    = "/tmp/{$domain}_files.zip";

        $zip->open($zipName, \ZipArchive::CREATE);

        $isWhiteLabel = false;
        $isWhiteLabel = strpos($domain, '.');

        $zip->addFromString(
            'auth.html',
            $this->renderView('DomainBundle:unifi:auth.html.twig', array('subdomain' => $domain,
                'isWhiteLabel'=>$isWhiteLabel))
        );

        $zip->addFromString(
            'index.html',
            $this->renderView('DomainBundle:unifi:index.html.twig', array('subdomain' => $domain,
                'isWhiteLabel'=>$isWhiteLabel))
        );

        $zip->close();

        return $zipName;
    }
}
