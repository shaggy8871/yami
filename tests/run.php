<?php declare(strict_types=1);

error_reporting(-1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
date_default_timezone_set('UTC');

$loader = require dirname(__DIR__) . '/vendor/autoload.php';
$loader->add('Tests\\', __DIR__);

session_start();