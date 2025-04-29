<?php

namespace Wideti\DomainBundle\Service\Template;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Wideti\DomainBundle\Entity\Template;

class LandscapeImage implements TemplateImage
{
    use TemplateAware;

    /**
     * @var Template
     */
    private $template;

    /**
     * @var UploadedFile
     */
    private $file;

    /**
     * @var string
     */
    private $fileName;

    /**
     * LandscapeImage constructor.
     * @param Template $template
     * @param UploadedFile $file
     */
    public function __construct(Template $template, UploadedFile $file = null)
    {
        $this->template = $template;
        $this->file     = $file;
        $hash           = $template->getBackgroundImageHash();

        if (!is_null($file)) {
            $this->fileName = "template_{$this->template->getClient()->getDomain()}_" .
                "{$this->template->getId()}{$hash}_horizontal_100.{$this->file->guessExtension()}";
        }
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->template->getBackgroundImage();
    }

    /**
     * @return $this
     */
    public function setImage()
    {
        $this->template->setBackgroundImage($this->fileName);
        return $this;
    }

    /**
     * @return $this
     */
    public function setNullImage()
    {
        $this->template->setBackgroundImageIsNull();
        $this->template->setBackgroundImageHash(null);
        return $this;
    }

    /**
     * @return Template
     */
    public function getTemplate()
    {
        return $this->template;
    }

    public function validateImage()
    {
        return $this->templateService->validateImage($this->file);
    }

    /**
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @return string
     */
    public function hasCustomBucket()
    {
        return 'Template';
    }

    /**
     * @return bool
     */
    public function hasCustomImage()
    {
        return true;
    }

    /**
     * @return array
     */
    public function getImagesToRemove()
    {
        return [
            $this->template->getBackgroundImage(),
            str_replace('_100.', '_80.', $this->template->getBackgroundImage()),
            str_replace('_100.', '_60.', $this->template->getBackgroundImage()),
            str_replace('_100.', '_40.', $this->template->getBackgroundImage()),
        ];
    }
}