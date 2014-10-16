<?php

require 'vendor/autoload.php';

use Symfony\Component\Console\Application;

$console = new Application();

$console->add(new Lift\Commands\UploadCommand());
$console->add(new Lift\Commands\ResetCommand());

$console->run();