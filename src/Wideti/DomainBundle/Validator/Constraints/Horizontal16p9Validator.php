<?php

namespace Wideti\DomainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class Horizontal16p9Validator extends ConstraintValidator
{
    const ASPECT_16_9 = 1.77;
    const FLOAT_PRECISION = 2;

    /**
     * @param float $width
     * @param float $height
     * @return bool
     */
    public static function isValidResolution($width, $height)
    {
        if (!is_numeric($width) || !is_numeric($height)) {
            throw new \InvalidArgumentException("Invalid argument to check aspect ratio, Width: {$width}, Height: {$height}");
        }

        $intHeight = (int) $height;
        $intWidth = (int) $width;

        if ($intHeight === 0 || $intWidth === 0) {
            throw new \InvalidArgumentException("Division by zero");
        }

        $total = (float) bcdiv($intWidth, $intHeight,self::FLOAT_PRECISION);
        return $total === self::ASPECT_16_9;
    }

    public function validate($fileBackgroundImage, Constraint $constraint)
    {
        if ($fileBackgroundImage &&
            $fileBackgroundImage->getSize() > 0 &&
            $fileBackgroundImage->getError() == 0)
        {
            $validExtensions = [ 'jpg', 'png', 'jpeg' ];

            if (in_array($fileBackgroundImage->getClientOriginalExtension(), $validExtensions)) {
                $imageData = getimagesize($fileBackgroundImage->getPathname());
                $width = $imageData[0];
                $height = $imageData[1];

                if (!self::isValidResolution($width, $height)) {
                    $this->context->addViolation("A resolução {$width} x {$height} " .
                        "não é válida para o Aspect Ratio 16:9");
                }
            }
        }
    }
}