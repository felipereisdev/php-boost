<?php

require __DIR__ . '/vendor/autoload.php';

use FelipeReisDev\PhpBoost\Standalone\Bootstrap;

$bootstrap = Bootstrap::fromEnv();
$bootstrap->start();
