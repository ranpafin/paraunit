<?php

declare(strict_types=1);

if (! ini_get('date.timezone') && ! date_default_timezone_get()) {
    date_default_timezone_set('UTC');
}

// HOTFIX -- needed to fool the Symfony's WebTestCase
$_SERVER['argv'][0] = 'phpunit';

$application = \Paraunit\Bin\Paraunit::createApplication();
$application->run();
