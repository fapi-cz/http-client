#!/usr/bin/env php
<?php
declare(strict_types = 1);

\passthru(
	\escapeshellarg(__DIR__ . '/../vendor/bin/phpcbf')
	. ' ' . \escapeshellarg('--standard=' . __DIR__ . '/../tools/cs/ruleset.xml')
	. ' --parallel=1 --extensions=php,phpt --encoding=utf-8 --tab-width=4 --colors -sp'
	. ' '
	. __DIR__ . '/../src'
	. ' '
	. __DIR__ . '/../tests'
);
