#!/usr/bin/env php
<?php
declare(strict_types = 1);

\passthru(
	\escapeshellarg(
		__DIR__ . '/../vendor/bin/phpstan'
	)
	. ' analyse'
	. ' -c ' . \escapeshellarg(
		__DIR__ . '/../tools/phpstan/phpstan.neon'
	)
	. ' -l 7'
	. ' --memory-limit=256M'
	. ' '
	. __DIR__ . '/../src '
	. __DIR__ . '/../tests',
	$return
);

exit($return);
