<?php
/**
 * Created by PhpStorm.
 * User: romani
 * Date: 26/07/17
 * Time: 20:12
 */

namespace Wideti\FrontendBundle\Factory\NasHandlers\Dto;


class Fields
{
    private $apMacField;
    private $guestMacField;
    private $nasUrlPostField;

    public function __construct($apMacField, $guestMacField, $nasUrlPostField)
    {
        $this->apMacField = $apMacField;
        $this->guestMacField = $guestMacField;
        $this->nasUrlPostField = $nasUrlPostField;
    }

    /**
     * @return mixed
     */
    public function getApMacField()
    {
        return $this->apMacField;
    }

    /**
     * @return mixed
     */
    public function getGuestMacField()
    {
        return $this->guestMacField;
    }

    /**
     * @return mixed
     */
    public function getNasUrlPostField()
    {
        return $this->nasUrlPostField;
    }
}
