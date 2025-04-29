<?php

namespace Wideti\DomainBundle\Service\Mikrotik;

use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Symfony\Component\HttpFoundation\Response;

class MikrotikService
{
    use TwigAware;

    /**
     * Generate default files to be placed into Mikrotik Device
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
     * Generate a Ziped file on disk with alogin.html and login.html
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
            'alogin.html',
            $this->renderView('DomainBundle:Mikrotik:alogin.html.twig', array('subdomain' => $domain,
                'isWhiteLabel'=>$isWhiteLabel))
        );

        $zip->addFromString(
            'login.html',
            $this->renderView('DomainBundle:Mikrotik:login.html.twig', array('subdomain' => $domain,
                'isWhiteLabel'=>$isWhiteLabel))
        );

        $zip->close();

        return $zipName;
    }
}
