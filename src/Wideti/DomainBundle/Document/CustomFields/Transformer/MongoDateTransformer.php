<?php
namespace Wideti\DomainBundle\Document\CustomFields\Transformer;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class MongoDateTransformer implements DataTransformerInterface
{
    private $manager;

    public function __construct(ObjectManager $manager = null)
    {
        if ($manager === null) {
            return;
        }
        $this->manager = $manager;
    }

    public function transform($mongoDate)
    {
        if ($mongoDate === null) {
            return $mongoDate;
        }
        if (!$mongoDate instanceof \MongoDate) {
            throw new TransformationFailedException("Expected MongoDate " . get_class($mongoDate) . " received");
        }
        return new \DateTime(date('Y-m-d H:i:s', $mongoDate->sec));
    }

    public function reverseTransform($dateTime)
    {
        if ($dateTime === null) {
            return $dateTime;
        }
        if (!$dateTime instanceof \DateTime) {
            throw new TransformationFailedException("Expected DateTime " . get_class($dateTime) . " received");
        }
        return new \MongoDate(strtotime($dateTime->format("Y-m-d H:i:s")));
    }
}