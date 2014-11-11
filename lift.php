<?php

if(!defined('DEVICE')) define('DEVICE','');

require 'vendor/autoload.php';

use Symfony\Component\Console\Application;

$console = new Application();

$console->add(new Lift\Commands\BuildCommand());
$console->add(new Lift\Commands\SyncCommand());

$console->run();