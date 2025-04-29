<?php


namespace Wideti\DomainBundle\Service\Jaeger;

use Symfony\Component\HttpFoundation\Request;

class TracerHeaders
{
	private function __construct(){}

	public static function from(Request $request) {
		$tracerHeaders = [
			'x-request-id',
			'x-b3-traceid',
			'x-b3-spanid',
			'x-b3-parentspanid',
			'x-b3-sampled',
			'x-b3-flags',
			'x-ot-span-context',
		];
		$requestHeaders = $request->headers;

		$extractedHeaders = [];
		foreach ($tracerHeaders as $key) {
			$value = $requestHeaders->get($key);
			if (!empty($value)) {
				$extractedHeaders[$key] = $value;
			}
		}

		return $extractedHeaders;
	}
}
