<?php
/**
 * Created by PhpStorm.
 * User: romani
 * Date: 02/12/16
 * Time: 14:56
 */

namespace Wideti\DomainBundle\Service\ApiEntityValidator\JsonFieldsSchema;


interface ApiSchema
{
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';

    /**
     * @return int
     */
    public function countFieldsLeft();

    /**
     * @return array
     */
    public function getAllFieldsLeft();
}