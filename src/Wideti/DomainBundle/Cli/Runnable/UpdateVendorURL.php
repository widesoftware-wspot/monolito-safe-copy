<?php
namespace Wideti\DomainBundle\Cli\Runnable;

use Wideti\DomainBundle\Cli\ScriptClass\UpdateVendorManualURI;

require_once "../../../../../vendor/autoload.php";

$scriptClass = new UpdateVendorManualURI(isset($argv[1]) ? $argv[1] : "prod");
$scriptClass->run();