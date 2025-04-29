<?php

namespace Wideti\DomainBundle\Service\Template;

interface TemplateImage
{
    public function getTemplate();
    public function getFile();
    public function getFileName();
    public function getImage();
    public function setImage();
    public function setNullImage();
    public function validateImage();
    public function hasCustomBucket();
    public function hasCustomImage();
    public function getImagesToRemove();
}