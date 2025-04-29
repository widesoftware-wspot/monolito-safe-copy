<?php

namespace Wideti\DomainBundle\Cli\Runnable;

use Wideti\DomainBundle\Cli\ScriptClass\AddCoovachilliVendor;

require_once "../../../../../vendor/autoload.php";

$scriptClass = new AddCoovachilliVendor(isset($argv[1]) ? $argv[1] : "prod");
$scriptClass->run();