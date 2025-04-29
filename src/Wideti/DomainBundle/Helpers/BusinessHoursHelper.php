<?php

namespace Wideti\DomainBundle\Helpers;

use Wideti\DomainBundle\Entity\BusinessHours;

abstract class BusinessHoursHelper
{
    public static function getHours($entity, $slug)
    {
	    /**
	     * @var BusinessHours $entity
	     */
    	$items = $entity->getItems();
			file_put_contents("/sites/wspot.com.br/app/logs/dev.log", "buscando horarios para slug: " . $slug . PHP_EOL, FILE_APPEND);
			file_put_contents("/sites/wspot.com.br/app/logs/dev.log", "items: " . json_encode($items) . PHP_EOL, FILE_APPEND);
			file_put_contents("/sites/wspot.com.br/app/logs/dev.log", "itemsslug: " . json_encode($items[$slug]) . PHP_EOL, FILE_APPEND);

    	return $items[$slug];
    }
}
