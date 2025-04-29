<?php

namespace Wideti\DomainBundle\Service\WSpotFaker;

use Wideti\DomainBundle\Document\CustomFields\Field;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\CustomField\CustomFieldHelper;

class WSpotFakerService implements WSpotFakerServiceInterface
{
    /**
     * @var WSpotFakerManager
     */
    private $wspotFakerManager;

    /**
     * WSpotFakerService constructor.
     * @param WSpotFakerManager $wspotFakerManager
     */
    public function __construct(WSpotFakerManager $wspotFakerManager) {
        $this->wspotFakerManager = $wspotFakerManager;
    }

    public function execute(Client $client = null, $action)
    {
        if (!$client) throw new \InvalidArgumentException('Client cannot be null');
        if (!in_array($action, [ 'create', 'clear' ])) return false;

        return $this->wspotFakerManager->{$action}($client);
    }

    public static function generateValueToCustomFields($fields)
    {
        $filledFields = [];

        /**
         * @var $field Field
         */
        foreach ($fields as $field) {
            $filledFields[$field->getIdentifier()] = CustomFieldHelper::generateValueByIdentifier($field->getIdentifier());
        }

        return $filledFields;
    }


}
