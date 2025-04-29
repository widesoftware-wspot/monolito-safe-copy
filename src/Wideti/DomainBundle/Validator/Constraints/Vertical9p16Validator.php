<?php

namespace Wideti\DomainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class Vertical9p16Validator extends ConstraintValidator
{
    const ASPECT_9_16 = 0.56;
    const FLOAT_PRECISION = 2;

    public static function isValidResolution($width, $height)
    {
        if (!is_numeric($width) || !is_numeric($height)) {
            throw new \InvalidArgumentException("Invalid aspect ratio 9:16 validate, width: {$width}, height: {$height}");
        }

        $intHeight = (int) $height;
        $intWidth = (int) $width;

        if ($intHeight === 0 || $intWidth === 0) {
            throw new \InvalidArgumentException("Division by zero");
        }

        $total = (float) bcdiv($intWidth, $intHeight,self::FLOAT_PRECISION);
        return $total === self::ASPECT_9_16;
    }

    public function validate($fileBackgroundPortraitImage, Constraint $constraint)
    {
        if ($fileBackgroundPortraitImage &&
            $fileBackgroundPortraitImage->getSize() > 0 &&
            $fileBackgroundPortraitImage->getError() == 0)
        {
            $validExtensions = [ 'jpg', 'png', 'jpeg' ];

            if (in_array($fileBackgroundPortraitImage->getClientOriginalExtension(), $validExtensions)) {
                $imageData = getimagesize($fileBackgroundPortraitImage->getPathname());
                $width = $imageData[0];
                $height = $imageData[1];

                if (!self::isValidResolution($width, $height)) {
                    $this->context->addViolation("A resolução {$width} x {$height} não é válida para Aspect Ratio 9:16");
                }
            }
        }
    }
}