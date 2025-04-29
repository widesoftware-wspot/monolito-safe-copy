<?php
namespace Wideti\DomainBundle\Exception;

class InvalidSegmentationSchemaException extends \Exception
{
    protected $message = "Segmentation schema are invalid";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
