<?php

namespace Wideti\DomainBundle\Helpers;

use Wideti\DomainBundle\Entity\Segmentation;
use Wideti\DomainBundle\Exception\InvalidDocumentException;
use Wideti\DomainBundle\Service\Segmentation\Filter\Dto\FilterDto;
use Wideti\DomainBundle\Service\Segmentation\Filter\Dto\FilterItemDto;

class SegmentationHelper
{
    /**
     * @param Segmentation $segmentation
     * @return Segmentation
     * @throws InvalidDocumentException
     */
    public static function validate(Segmentation $segmentation)
    {
        if (!$segmentation) {
            throw new InvalidDocumentException();
        }

        if (!$segmentation->getTitle()) {
            throw new InvalidDocumentException('Segmentation title is missing');
        }

        if (!$segmentation->getFilter() || empty($segmentation->getFilter()) || $segmentation->getFilter() == '[]') {
            throw new InvalidDocumentException('Segmentation filter is missing');
        }

        return $segmentation;
    }

    public static function convertToFilterDto($clientId, Segmentation $segmentation)
    {
        $segmentation = json_decode($segmentation->getFilter(), true)[0];

        $filter = new FilterDto();
        $filter
            ->setType($segmentation['type'])
            ->setClient($clientId)
        ;

        foreach ($segmentation as $k => $value) {
            if ($k === 'type') continue;
            foreach ($value as $key => $item) {
                $filterItem = new FilterItemDto();
                $filterItem
                    ->setIdentifier($item['identifier'])
                    ->setEquality($item['equality'])
                    ->setType($item['type'])
                    ->setValue($item['value'])
                ;

                $filter->addItem($filterItem);
            }
        }

        return $filter;
    }

    /**
     * @param $savedFilter
     * @param $defaultSchema
     * @return array
     * @throws \Exception
     */
    public static function convertToSchema($savedFilter, $defaultSchema)
    {
        try {
            $schema = [];

            $schema['type'] = $savedFilter['filter'][0]['type'];

            foreach ($savedFilter['filter'][0] as $key=>$value) {
                if ($key === 'type') continue;

                $schema[$key] = [
                    'label' => 'Visitantes'
                ];

                foreach ($value as $itemKey=>$itemValue) {
                    $equality = $defaultSchema[$key]['fields'][$itemKey]['equality'];

                    $schema[$key]['fields'][$itemKey] = [
                        'label'     => $defaultSchema[$key]['fields'][$itemKey]['label'],
                        'type'      => $itemValue['type'],
                        'equality'  => $equality,
                        'value'     => $itemValue['value'],
                    ];

                    unset($equality);
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $schema;
    }
}
