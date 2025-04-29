<?php

namespace Wideti\DomainBundle\Service\Template;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Wideti\DomainBundle\Entity\Template;

class PartnerLogo implements TemplateImage
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
     * PartnerLogo constructor.
     * @param Template $template
     * @param UploadedFile $file
     */
    public function __construct(Template $template, UploadedFile $file = null)
    {
        $this->template = $template;
        $this->file     = $file;

        if (!is_null($file)) {
            $this->fileName = uniqid(time() . "_", false) . '.' .
                $this->file->guessExtension();
        }
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->template->getPartnerLogo();
    }

    /**
     * @return $this
     */
    public function setImage()
    {
        $this->template->setPartnerLogo($this->fileName);
        return $this;
    }

    /**
     * @return $this
     */
    public function setNullImage()
    {
        $this->template->setPartnerLogoIsNull();
        return $this;
    }

    /**
     * @return Template
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return mixed
     */
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
     * @return null
     */
    public function hasCustomBucket()
    {
        return null;
    }

    /**
     * @return bool
     */
    public function hasCustomImage()
    {
        return false;
    }

    /**
     * @return null
     */
    public function getImagesToRemove()
    {
        return null;
    }
}