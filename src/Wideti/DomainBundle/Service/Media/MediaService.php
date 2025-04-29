<?php

namespace Wideti\DomainBundle\Service\Media;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Wideti\DomainBundle\Entity\Campaign;

interface MediaService
{
    public function upload(UploadedFile $file, $bucket, $folder, array $options);
    public function remove(Campaign $campaign);
}
