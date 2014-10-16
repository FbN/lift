<?php
namespace Lift\Services;

abstract class Service {
	
	protected $app;
	
	function __construct($app){ $this->app = $app; }
	
}