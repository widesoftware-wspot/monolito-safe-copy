<?php

namespace Wideti\DomainBundle\Helpers;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use Aws\S3\S3Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\Report\ReportFormat;
use Wideti\DomainBundle\Service\Report\ReportType;
use Rhumsaa\Uuid\Uuid;

class FileUpload extends \SplFileInfo
{
    /**
     * @var S3Client
     */
    protected $s3;

    /**
     * @var string api key from AWS
     * @var string secret key from AWS
     */
    protected $aws_key;
    protected $aws_secret;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * FileUpload constructor.
     * @param $aws_key
     * @param $aws_secret
     * @param $container
     */
    public function __construct($aws_key, $aws_secret, $container)
    {
        $this->aws_key      = $aws_key;
        $this->aws_secret   = $aws_secret;
        $this->container    = $container;

        $s3Credentials  = new \Aws\Credentials\Credentials($aws_key, $aws_secret);

        $this->s3 = new S3Client([
            'version'   => '2006-03-01',
            'credentials'    => $s3Credentials,
            'region'    => 'sa-east-1'
        ]);
    }

    /**
     * create a unique name for file
     *
     * @param $file UploadedFile
     *
     * @return string
     */
    public function generateFileName($file)
    {
        return uniqid(time()."_", false).'.'.$file->guessExtension();
    }

    /**
     * Upload a file to AWS S3 or local folder
     *
     * @param object|\Symfony\Component\HttpFoundation\File\UploadedFile $file object UploadedFile
     * @param $fileName
     * @param $bucket
     * @param $folder string
     *
     * @return \Aws\Result
     */
    public function uploadFile(\Symfony\Component\HttpFoundation\File\UploadedFile $file, $fileName, $bucket, $folder)
    {
        return $this->uploadFileToS3($file->getRealPath(), $fileName, $bucket, $folder, $file);
    }

    /**
     * @param $originPath
     * @param $fileName
     * @param $bucket
     * @param $folder
     * @param UploadedFile $file
     * @return \Aws\Result
     */
    private function uploadFileToS3(
        $originPath,
        $fileName,
        $bucket,
        $folder,
        \Symfony\Component\HttpFoundation\File\UploadedFile $file
    ) {
        return $this->s3->putObject([
            'ACL'            => 'public-read',
            'Bucket'         => $bucket,
            'Key'            => $folder.'/'.$fileName,
            'SourceFile'     => $originPath,
            'ContentType'    => $file->getClientMimeType()
        ]);
    }

    /**
     * @param $fileName
     * @param $bucket
     * @param $folder
     * @return \Aws\Result
     */
    public function deleteFile($fileName, $bucket, $folder)
    {
        return $this->s3->deleteObject([
            'Bucket' => $bucket,
            'Key'    => $folder.'/'.$fileName,
        ]);
    }

    /**
     * @param $folder string
     *
     */
    public function deleteAllFiles($bucket, $folder)
    {
        $this->s3->deleteMatchingObjects($bucket, $folder);
    }

    /**
     * @param $bucket
     * @param $folder
     * @param $fileName
     * @return bool
     */
    public function existsFile($bucket, $folder, $fileName)
    {
        return $this->s3->doesObjectExist(
            $bucket,
            $folder.'/'.$fileName
        );
    }

    public function getAllFiles($bucket, $prefix)
    {
        $response = $this->s3->listObjects([
            'Bucket' => $bucket,
            'Prefix' => $prefix."/"
        ]);

        $files = $response->get('Contents');

        if (!$files) {
            return false;
        }

        $filesContent = [];

        foreach ($files as $file) {
            $filename    = explode('/', $file['Key']);
            $downloadUrl = $this->getUrl(end($filename), $bucket, $prefix, '2 days');

            array_push($filesContent, [
                'filename'    => end($filename),
                'downloadUrl' => $downloadUrl
            ]);
        }

        return $filesContent;
    }

    /**
     * @param $fileName
     * @param $bucket
     * @param $folder
     * @param null $expires
     * @return string
     */
    public function getUrl($fileName, $bucket, $folder, $expires = null)
    {
        if ($this->existsFile($bucket, $folder, $fileName)) {
            $url = $this->s3->getObjectUrl(
                $bucket,
                $folder.'/'.$fileName
            );

            return $url;
        }
    }

    public function uploadReports($bucket, Client $client, $reportType, $localFile, $format = ReportFormat::CSV)
    {
        $uuid = Uuid::uuid4();
        $hash = md5($uuid->toString());

        $file       = $hash . "." . strtolower($format);
        $filePath   = $client->getId() . "/" . $reportType . "/" . $file;

        $result = $this->s3->putObject([
            'Bucket'                => $bucket,
            'Key'                   => $filePath,
            'SourceFile'            => $localFile,
            'ACL'                   => 'private',
            'ContentType'           => 'application/octet-stream',
            'ContentEncoding'       => 'utf-8',
            'Content-Disposition'   => "attachment; filename=$file"
        ]);    

        $result['delete_date'] = date('Y-m-d', strtotime('+2 day'));

        return $result;
    }

    public function moveBetweenFolders($bucket, $oldFolder, $newFolder, $newFilename = null, $specificFile = null)
    {
        $response = $this->s3->listObjects([
            'Bucket' => $bucket,
            'Prefix' => $oldFolder."/"
        ]);

        $files = $response->getPath('Contents');

        if (!$files) {
            return false;
        }

        $batch = [];

        foreach ($files as $file) {
            $filename = explode('/', $file['Key']);

            if ((!is_null($specificFile)) && ($filename[1] != $specificFile)) {
                continue;
            }

            $file = is_null($newFilename) ? $filename[1] : $newFilename;

            $batch[] = $this->s3->getCommand('CopyObject', [
                'Bucket'     => $bucket,
                'Key'        => "{$newFolder}/{$file}",
                'CopySource' => "{$bucket}/{$oldFolder}/{$filename[1]}",
                'ACL'        => 'public-read'
            ]);
        }

        try {
            foreach ($batch as $command){
                $this->s3->execute($command);
            }

            if (!is_null($specificFile)) {
                $this->deleteFile($specificFile, $bucket, $oldFolder);
            } else {
                $this->deleteAllFiles($bucket, "{$oldFolder}/");
            }
        } catch (\Exception $ex) {
            throw new \Exception($ex->getMessage());
        }

        return true;
    }

    public function copyFileBetweenFolders($bucket, $origin, $destination)
    {
        $response = $this->s3->listObjects([
            'Bucket' => $bucket,
            'Prefix' => $origin
        ]);

        $files = $response->getPath('Contents');

        if (!$files) {
            return false;
        }

        $batch = [];

        foreach ($files as $file) {
            $filename = explode($origin . '/', $file['Key']);

            if ($filename[1] === '') continue;

            $batch[] = $this->s3->getCommand('CopyObject', [
                'Bucket'     => $bucket,
                'Key'        => "{$destination}/{$filename[1]}",
                'CopySource' => "{$bucket}/{$origin}/{$filename[1]}",
                'ACL'        => 'public-read'
            ]);
        }

        try {
            foreach ($batch as $command) {
                $this->s3->execute($command);
            }
        } catch (\Exception $ex) {
            throw new $ex;
        }

        return true;
    }

    /**
     * @param $folder
     * @param $bucket
     * @param $fileName
     * @return string[]|null
     */
    public function generateSignedReportUrl($folder, $bucket, $fileName)
    {
        if ($this->existsFile($bucket, $folder, $fileName)) {
            $cmd = $this->s3->getCommand('GetObject', [
                'Bucket' => $bucket,
                'Key' => $folder . '/' . $fileName
            ]);

            $request = $this->s3->createPresignedRequest($cmd, "+30 seconds");

            return ['url' => (string)$request->getUri()];

        }
        return null;
    }

    /**
     * @param $bucket
     * @param Client $client
     * @param $reportType
     * @param $localFile
     * @param $filename
     * @param string $format
     * @return \Aws\Result
     */
    public function uploadCLIReports($bucket, Client $client, $reportType, $localFile, $filename,  $format = ReportFormat::CSV)
    {
        $file       = $filename;
        $filePath   = $client->getId() . "/" . $reportType . "/" . $file;

        $result = $this->s3->putObject([
            'Bucket'                => $bucket,
            'Key'                   => $filePath,
            'SourceFile'            => $localFile,
            'ACL'                   => 'private',
            'ContentType'           => 'application/octet-stream',
            'ContentEncoding'       => 'utf-8',
            'Content-Disposition'   => "attachment; filename=$file"
        ]);

        $result['delete_date'] = date('Y-m-d', strtotime('+2 day'));

        return $result;
    }
}
