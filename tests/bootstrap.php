<?php

declare(strict_types=1);
$loader = require dirname(__DIR__, 3).'/vendor/autoload.php';
$loader->addPsr4('Survos\\OutreachBundle\\', dirname(__DIR__).'/src/');
$loader->addPsr4('Survos\\Kit\\', dirname(__DIR__, 2).'/kit-bundle/src/');
