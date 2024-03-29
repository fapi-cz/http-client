<?php declare(strict_types = 1);

use Tester\Environment;

// The Nette Tester command-line runner can be
// invoked through the command: ../vendor/bin/tester .

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install Nette Tester using `composer install`';
	exit(1);
}

require_once __DIR__ . '/Fapi/HttpClientTests/MockHttpServer/HttpServerException.php';

// configure environment
Environment::setup();
date_default_timezone_set('Europe/Prague');

// configure locks dir
define('LOCKS_DIR', __DIR__ . '/locks');
@mkdir(LOCKS_DIR);
