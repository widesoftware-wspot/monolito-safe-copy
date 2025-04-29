<?php

namespace Wideti\DomainBundle\Service\BouncedValidation;

interface BouncedValidation
{
	public function isValid($email);
}
