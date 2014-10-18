<?php
namespace Lift\Services;

class Stats extends Service {
	
	const IGNORED = 'ignored';
	const PROCESSED = 'processed';
	const NEWENTRY = 'new entry';
	const CHANGED = 'changed';
	
	protected $counters = [];
	
	public function add($counter){
		if(!isset($this->counters[$counter])) $this->reset($counter);
		$this->counters[$counter]++;
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
		$out->writeln('<question>*** Stats ***</question>');
		foreach ($this->counters as $c=>$v){
			$out->writeln("<info>$c</info>: $v");
		}
	}
	
}