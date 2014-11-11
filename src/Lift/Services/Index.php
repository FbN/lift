<?php

namespace Lift\Services;

class Index extends Service {
	
	static public function unixPath($path) {
		if(DIRECTORY_SEPARATOR=='/') return $path;
		return str_replace(DIRECTORY_SEPARATOR, '/', $path);
	}
	
	protected $file='lift.lock';
	
	protected function getPath(){
		return $this->app['config']['root'].DIRECTORY_SEPARATOR.$this->file;
	}
	
	protected function mustIgnore($path){
		$config = $this->app['config'];
		foreach ($config['ignorePatterns'] as $pattern)
		{
			if(preg_match($pattern, $path)===1)
			{
				return true;
			}
		}
		return false;
	}
	
	protected function scan($root, $do){
		
		$stats = $this->app['stats'];
		
		$stats->reset(Stats::IGNORED);
		$stats->reset(Stats::PROCESSED);
		
		$l = strlen($root);
		$iterator=new \RecursiveDirectoryIterator($root);
		$iterator->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
		foreach (new \RecursiveIteratorIterator($iterator) as $filename)
		{
			$rel = self::unixPath(substr($filename, $l));
				
			if($this->mustIgnore($rel))
			{
				$stats->add(Stats::IGNORED);
				continue;
			}
				
			$stats->add(Stats::PROCESSED);
			
			$do($rel);
				
		}
		
	}
		
	public function localBuild($persist=true)
	{
		$root = $this->app['config']['root'];
		
		$index = array();
		
		$this->scan($root, function($rel) use ($root, &$index) {
			$index[$rel] = [
				"time" => filemtime($root.$rel),
				"crc" => md5(file_get_contents($root.$rel)),
				"size" => filesize($root.$rel)
				];
		});	

	    if($persist) $this->persist($index);
		
	    return $index;
	}
	
	public function diff($index)
	{

		$config = $this->app['config'];		
		
		$stats = $this->app['stats'];
		$stats->resetFileLog();
		$stats->reset(Stats::NEWENTRY);
		$stats->reset(Stats::CHANGED);
		
		$root = $config['root'];
	
		$diffs = array();
	
		$this->scan($root, function($rel) use ($root, $index, &$diffs, $stats, $config) {
			$time = null;
			$size = null;
			$crc  = null;
						
			if(!isset($index[$rel]))
			{
				$stats->add(Stats::NEWENTRY);
				$stats->addNewFile($rel);
				$diffs[$rel]= [
					"time" => filemtime($root.$rel),
					"crc" => $crc?$crc:md5(file_get_contents($root.$rel)),
					"size" => filesize($root.$rel)
				];
			}
			elseif (
 				($index[$rel]['size'] != ($size=filesize($root.$rel))) ||
				($config['check-time'] && ($index[$rel]['time'] != ($time=filemtime($root.$rel)))) ||
				($config['check-size'] && ($index[$rel]['size'] != ($size=filesize($root.$rel)))) ||
				(!($config['check-time']||$config['check-size']) && ($index[$rel]['crc']  != ($crc = md5(file_get_contents($root.$rel))))) 
				)
			{
				$stats->add(Stats::CHANGED);
				$stats->addModFile($rel);
				$diffs[$rel]= [
					"time" => filemtime($root.$rel),
					"crc" => $crc?$crc:md5(file_get_contents($root.$rel)),
					"size" => filesize($root.$rel)
				];
			}
			
		});
		  
		return $diffs;
	
	}
	
	public function compare($workingIndex, $index){
		
		$stats = $this->app['stats'];
		
		$stats->resetFileLog();
		$stats->reset(Stats::NEWENTRY);
		$stats->reset(Stats::CHANGED);
		
		$config = $this->app['config'];
		
		$diff = [];
		
		foreach($workingIndex as $f=>$v){
			if(!array_key_exists($f, $index)){
				$stats->add(Stats::NEWENTRY);
				$stats->addNewFile($f);
				$diff[]=$f;
			} else{
				$vIndex = $index[$f];
				if( ($config['check-time'] && ($v['time'] != $vIndex['time'])) ||
					($config['check-size'] && ($v['size'] != $vIndex['size'])) ||
					($v['crc']  != $vIndex['crc'])
				){
					$stats->add(Stats::CHANGED);
					$stats->addModFile($f);
					$diff[]=$f;
				}
			}
		}
		
		return $diff;
		
	}
	
	public function getIndex()
	{	
		return file_exists($this->getPath())?json_decode(file_get_contents($this->getPath()), true):null;
	}
	
	public function persist($index)
	{
		return file_put_contents($this->getPath(), json_encode($index, JSON_PRETTY_PRINT));
	}
	
	
}