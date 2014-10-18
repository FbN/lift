<?php

namespace Lift\Services;

class Config extends Service {
	
	protected $configFile = 'lift.json';
	
	public function load()
	{
	
		if(!file_exists($this->configFile)) throw new \RuntimeException("Oi! Config file not found");
	
		$config = json_decode(file_get_contents($this->configFile), true);
	
		if($config===null) throw new \RuntimeException("Oh No! You json config got problems");
	
		if(!isset($config['hosts']) || !$config['hosts'])
		{
			throw new \RuntimeException("Oi! Configure at least one host in config file");
		}
	
		// input config
		$config = array_merge
		(
				$config,
				$this->app['in']->getOptions(),
				$this->app['in']->getArguments()
		);
		
		// runtime config
		
		$config['root'] = getcwd();
		
		$config['host'] = isset($config['defaut-host'])?$config['hosts'][$config['defaut-host']]:current($config['hosts']);
		
		$config['ignorePatterns'] = [];
		
		// ignore patterns
		
		if(isset($config['ignore']))
		{
			foreach ($config['ignore'] as $pattern)
			{
				$s = '/';
				$e = '/';
				if($pattern[0]=='^')
				{
					$pattern = substr($pattern, 1);
					$s = '/^';
				}
				if($pattern[strlen($pattern)-1]=='$')
				{
					$pattern = substr($pattern, 0, -1);
					$e = '$/';
				}
				$config['ignorePatterns'][]=$s.preg_quote($pattern, '/').$e;
			}
		}
	
		return $config;
	}
	
}