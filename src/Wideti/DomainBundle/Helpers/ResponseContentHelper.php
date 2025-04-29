<?php

namespace Wideti\DomainBundle\Helpers;

use Symfony\Component\HttpFoundation\Response;
use Wideti\DomainBundle\Service\Report\ReportFormat;

class ResponseContentHelper
{
    /**
     * @param $filePath
     * @param string $format
     * @return mixed
     */
    public function getDownloadResponseByFileFormat($filePath, $format = ReportFormat::CSV)
    {
        $response = new Response();
        $method = strtolower($format);

        return $this->$method($response, $filePath, $format);
    }

    /**
     * @param $response
     * @param $filePath
     * @param $format
     * @return mixed
     */
    private function csv(Response $response, $filePath, $format)
    {
        $response->headers->set('Content-Encoding', 'UTF-8');
        $response->headers->set('Content-type', 'text/csv; charset=UTF-8');
        $response
            ->headers
            ->set('Content-Disposition', sprintf('attachment; filename="export.' . strtolower($format) . '"'));

        $response->setContent(file_get_contents($filePath));

        return $response;
    }


    /**
     * @param Response $response
     * @param $filePath
     * @param $format
     * @return Response
     */
    private function xlsx(Response $response, $filePath, $format)
    {
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response
            ->headers
            ->set('Content-Disposition', sprintf('attachment; filename="export.' . strtolower($format) . '"'));
        $response->headers->set('Cache-Control', 'max-age=0');
        $response->headers->set('Cache-Control', 'max-age=1');
        $response->headers->set('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT');
        $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s').' GMT');
        $response->headers->set('Cache-Control', 'cache, must-revalidate');
        $response->headers->set('Pragma', 'public');

        $response->setContent(file_get_contents($filePath));

        return $response;
    }

    private function pdf(Response $response, $filePath, $format)
    {
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', "attachment;filename=export.pdf");
        $response->setContent(file_get_contents($filePath));
        return $response;
    }
}
