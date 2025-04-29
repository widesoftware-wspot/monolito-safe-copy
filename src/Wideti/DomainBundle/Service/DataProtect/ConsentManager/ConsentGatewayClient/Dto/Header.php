<?php


namespace Wideti\DomainBundle\Service\DataProtect\ConsentManager\ConsentGatewayClient\Dto;


use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kind;

class Header implements BaseParam
{
    /**
     * @var Kind
     */
    private $xKind;
    private $xKindId;

	/**
	 * @var array
	 */
	private $traceHeaders = [];

    private function __construct()
    {
    }

    public function addXKind(Kind $kind){
        $this->xKind = $kind;
        return $this;
    }

    public function addXKindId($xKindId){
        $this->xKindId = $xKindId;
        return $this;
    }

    public function addTraceHeaders($headers) {
    	$this->traceHeaders = $headers;
    	return $this;
	}

    public static function build(){
        return new Header();
    }

    public function get(){
        if (is_null($this->xKind)){
            throw new \RuntimeException("Value xKind cannot be null");
        }
        if (is_null($this->xKindId) || $this->xKindId == ""){
            throw new \RuntimeException("Value xKindId cannot be null");
        }

        $kindHeaders = [
			"x-kind"=> $this->xKind->getValue(),
			"x-kind-id"=> $this->xKindId
		];

        return array_merge($kindHeaders, $this->traceHeaders);
    }
}