<?php
namespace Wideti\DomainBundle\Document\CustomFields\Transformer;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class PhoneNumberTransformer implements DataTransformerInterface
{
    private $manager;

    public function __construct(ObjectManager $manager = null)
    {
        if ($manager === null) {
            return;
        }
        $this->manager = $manager;
    }

    public function transform($phone)
    {
        if ($phone === null) {
            return $phone;
        }
        return preg_replace("/[^0-9]/", "", $phone);
    }

    public function reverseTransform($phone)
    {
        if ($phone === null) {
            return $phone;
        }
        return preg_replace("/[^0-9]/", "", $phone);
    }
}