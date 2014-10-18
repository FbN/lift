<?php
namespace Lift\Services;

class Ftp extends Service {
	
	protected $connection;
	
	public function connect(){
		$config = $this->app['config'];
		$host = $config['host'];
		$ftp = ftp_connect($host['host']);
		if($ftp===false){
			throw new \RuntimeException("Could not connect to ftp server ".$host['host']);
		}
		if(!ftp_login($ftp, $host['username'], $host['password'])){
			throw new \RuntimeException("Could not login to ftp server ".$host['host']);
		}
		
		ftp_pasv($ftp, true);
		
		$origin = ftp_pwd($ftp);
		if(!ftp_chdir($ftp, $host['folder'])){
			throw new \RuntimeException("Cannot change to remote folder ".$host['folder']);
		} else {
			ftp_chdir($ftp, $origin);
		}
		
		$this->connection = $ftp;
	}
	
	public function close(){
		if(!$this->connection) throw new \RuntimeException("Invalid connection");
		return ftp_close( $this->connection );
	}
	
	protected function checkForAndMakeDirs($connection, $file) 
	{
		$origin = ftp_pwd($connection);
		$parts = explode("/", dirname($file));
	
		foreach ($parts as $curDir) {
			if($curDir){
				// Attempt to change directory, suppress errors
				if (@ftp_chdir($connection, $curDir) === false) {
					ftp_mkdir($connection, $curDir); //directory doesn't exist - so make it
					ftp_chdir($connection, $curDir); //go into the new directory
				}
			}
		}
		ftp_chdir($connection, $origin);
	}
	
	public function uploadDiff($index, $diffs, $command)
	{
		$stats = $this->app['stats'];
		$pretend = $this->app['config']['pretend'];
		
		$stats->reset(Stats::UPLOADKO);
		$stats->reset(Stats::UPLOADOK);
		
		$config = $this->app['config'];
		$host = $config['host'];
		
		if(!$this->connection) throw new \RuntimeException("Invalid connection");
		
		$size = 0;
		$size= array_reduce($diffs, function($size, $v){
			return $size+=$v['size'];
		});
		
		$processed = 0;
		$fails = [];
		
		foreach ($diffs as $ref=>$v){
			
			$command->writeSpinning($processed,$size);
			
			$r = $pretend?true:@ftp_put( $this->connection , $host['folder'].$ref, $config['root'].$ref , FTP_BINARY );
			
			if(!$r)
			{
				$this->checkForAndMakeDirs($this->connection, $host['folder'].$ref);
				$r = @ftp_put( $this->connection , $host['folder'].$ref, $config['root'].$ref , FTP_BINARY );
			}
			
			if($r){
				// update index
				$index[$ref] = $v;
				$stats->add(Stats::UPLOADOK);				
			} else {
				// failed
				$stats->add(Stats::UPLOADKO);
				$fails[]= $ref;
			}
			
			$processed += $v['size'];
			
		}
		
		$command->writeHR();
		
		return $index;
	}
	
}