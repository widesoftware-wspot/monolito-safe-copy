<?php

namespace Wideti\DomainBundle\Service\ClientLogs\Dto;

use JsonSerializable;

class ClientLogDto implements JsonSerializable
{
    const ORIGIN_API            = 'api';
    const ORIGIN_WEBHOOK        = 'webhook';
    const ORIGIN_CRON           = 'cron';
    const ORIGIN_PANEL_WSPOT    = 'panel';

    const ACTION_ACCOUNT_CANCELLED      = 'Desativando conta do cliente no Superlógica';
    const ACTION_CREATE_CLIENT          = 'Criando cliente pelo painel';
    const ACTION_INACTIVATING_CLIENT    = 'Inativou a conta do cliente';
    const ACTION_ACTIVATING_CLIENT      = 'Ativou a conta do cliente';

    private $clientId;
    private $author;
    private $method;
    private $url;
    private $action;
    private $response;
    private $date;

    /**
     * @return mixed
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param mixed $clientId
     * @return ClientLogDto
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param mixed $author
     * @return ClientLogDto
     */
    public function setAuthor($author)
    {
        $this->author = strtolower($author);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param mixed $method
     * @return ClientLogDto
     */
    public function setMethod($method)
    {
        $this->method = strtolower($method);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     * @return ClientLogDto
     */
    public function setUrl($url)
    {
        $this->url = strtolower($url);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param mixed $action
     * @return ClientLogDto
     */
    public function setAction($action)
    {
        $this->action = strtolower($action);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param mixed $response
     * @return ClientLogDto
     */
    public function setResponse($response)
    {
        $this->response = strtolower($response);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param mixed $date
     * @return ClientLogDto
     */
    public function setDate($date)
    {
        $this->date = $date;
        return $this;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        $parameters = get_object_vars($this);
        $result = [];
        foreach ($parameters as $param => $value) {
            $result[$param] = $this->{$param};

        }
        return $result;
    }
}
