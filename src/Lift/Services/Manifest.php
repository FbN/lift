<?php

namespace Lift\Services;

class Manifest extends Service {
	
	protected $file='lift.lock';
	
	protected function getPath(){
		return $this->app['config']['root'].DIRECTORY_SEPARATOR.$this->file;
	}
		
	public function localBuild()
	{
		$config = $this->app['config'];
		
		$root = $config['root'];
		
		$l = strlen($root);		
		
		$queue = array();
		$counters = array(
				'found' => 0,
				'ignored' => 0,
				'changed' => 0,
				'new' => 0
		);
		$iterator=new \RecursiveDirectoryIterator($root);
		$iterator->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
		foreach (new \RecursiveIteratorIterator($iterator) as $filename)
		{	
			$rel = substr($filename, $l);
			
			$ignore = false;
						
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
					if(preg_match($s.preg_quote($pattern, '/').$e, $rel)===1)
					{
						$ignore = true;
					}
				}
			}
		
			if($ignore)
			{
				$counters['ignored']++;
				continue;
			}
			
			$index[$rel] = md5(file_get_contents($root.$rel));
					
	    }
	    
	    file_put_contents($this->getPath(), json_encode($index, JSON_PRETTY_PRINT));
		
	}
	
	public function get(){
		$p = $this->config['root'].PATH_SEPARATOR.$this->file;
		
		if(!file_exists($p)) $this->localBuild();
			 
	}
	
}