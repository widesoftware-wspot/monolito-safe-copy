<?php

namespace Wideti\DomainBundle\Service\Analytics\AnalysisTarget;

use Wideti\DomainBundle\Service\Analytics\Dto\EventDto;

interface Analyzer
{
	public function transform(EventDto $dto);
}
