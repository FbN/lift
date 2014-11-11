<?php
namespace Lift\Services;

class Stats extends Service {
	
	const IGNORED = 'ignored';
	const PROCESSED = 'processed';
	const NEWENTRY = 'new entry';
	const CHANGED = 'changed';
	const UPLOADOK = 'uploaded';
	const UPLOADKO = 'upload failed';
	
	protected $counters = [];
	
	protected $modFiles = [];
	
	protected $newFiles = [];
		
	public function add($counter){
		if(!isset($this->counters[$counter])) $this->reset($counter);
		$this->counters[$counter]++;
	}
	
	public function resetFileLog(){ 
		$this->modFiles=[];
		$this->newFiles=[];
	}
	
	public function addModFile($f){
		$this->modFiles[$f] = true;
	}
	
	public function addNewFile($f){
		$this->newFiles[$f] = true;
	}
	
	public function getModFiles(){
		return array_keys($this->modFiles);
	}
	
	public function getNewFiles(){
		return array_keys($this->newFiles);
	}
	
	public function reset($counter){
		$this->counters[$counter]=0;
	}
	
	public function get($counter){
		return isset($this->counters[$counter])?$this->counters[$counter]:0;
	}
	
	public function key($name){
		return constant("\\Lift\\Services\\Stats::".$name);
	}
	
	public function report(){
		$out = $this->app['out'];
		$config = $this->app['config'];
		
		$out->writeln('<question>*** New Files  ***</question>');
		foreach ($this->getNewFiles() as $f){
			$out->writeln($f);
		}		
		$out->writeln('<question>*** Modified Files ***</question>');
		foreach ($this->getModFiles() as $f){
			$out->writeln($f);
		}
		
		$out->writeln('<question>*** Stats ***</question>');
		foreach ($this->counters as $c=>$v){
			$out->writeln("<info>$c</info>: $v");
		}
	}
	
}