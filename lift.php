<?php

if(!defined('DEVICE')) define('DEVICE','');

require 'vendor/autoload.php';

use Symfony\Component\Console\Application;

$console = new Application();

$console->add(new Lift\Commands\UploadCommand());
$console->add(new Lift\Commands\ResetCommand());
$console->add(new Lift\Commands\DiffCommand());
$console->add(new Lift\Commands\BuildCommand());
$console->add(new Lift\Commands\RemoteResetCommand());

$console->run();