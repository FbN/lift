<?php

namespace Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;

class DeployCommand extends Command {
	
	static $rootFolder = null;
	
	protected function rootFolder(){
		return self::$rootFolder?self::$rootFolder:DOCROOT;
	}
		
	protected function configure() {
		$this->setName ( 'apimart:deploy' )
			->setDescription ( 'Syncronizza l\'applicazione con un server remoto')
			->addOption(
					'config',
					'c',
					InputOption::VALUE_REQUIRED,
					'File di configurazione. Se non impostato .deploy.yaml'
			)->addOption(
					'zap',
					'z',
					InputOption::VALUE_NONE,
					'Resetta il file indice senza caricare null'
			)->addOption(
					'dry',
					'd',
					InputOption::VALUE_NONE,
					'Show only files to upload'
			)->addOption(
					'list-new',
					null,
					InputOption::VALUE_NONE,
					'List new files'
			)->addOption(
					'list-changed',
					null,
					InputOption::VALUE_NONE,
					'List new files'
			)->addOption(
					'remote-zap',
					null,
					InputOption::VALUE_NONE,
					'Rebuild the zap list from remote host'
			);
	}
	
	protected function checkForAndMakeDirs($connection, $file) {
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
	
		//go back to the origin directory
		ftp_chdir($connection, $origin);
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		
		$root = $this->rootFolder();
		
		$vendorFolder = $root.'/vendor';		
		
		$zap = $input->getOption('zap');
		$dry = $input->getOption('dry');
		
		$configFile = $input->getOption('config');
		
		$index = array();
		$indexFile = $root.'/.deploy.json';
		if(file_exists($indexFile)){
			$index = json_decode(file_get_contents($indexFile), true);
		}
		
		if(!$configFile){
			$configFile = $root.'/.deploy.yaml';
		} elseif($configFile[0]!='/') {
			$configFile = $root.'/'.$configFile;
		}
		
		$config = array();
		if(file_exists($configFile)){		
			$yaml = new Parser();		
			$config = $yaml->parse(file_get_contents($configFile));
		}
		
		if($input->getOption('remote-zap')){
			$zapScritp = '_deploy-zap.php';
			$myIp =  file_get_contents('http://vista-dev.it/ip.php');
			$configIgnore= serialize(isset($config['ignore'])?$config['ignore']:array());
			echo "genero script...\n";
			$script = <<<EOT
<?php
		
	if (\$_SERVER['REMOTE_ADDR'] != '{$myIp}') {
		die('ACCESSO NEGATO');
	}

	\$root = __DIR__;
	\$queue = array();
	\$index = array();
	\$l = strlen(\$root);
	\$configIgnore = unserialize('$configIgnore');
	foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(\$root)) as \$filename)
	{
		\$rel = substr(\$filename, \$l);
		
		\$ignore = false;
    	foreach (\$configIgnore as \$pattern){
			\$s = '/';
			\$e = '/';
    		if(\$pattern[0]=='^'){
    			\$pattern = substr(\$pattern, 1);
    			\$s = '/^';
    		}
    		if(\$pattern[strlen(\$pattern)-1]=='\$'){
    			\$pattern = substr(\$pattern, 0, -1);
    			\$e = '\$/';
    		}
    		if(preg_match(\$s.preg_quote(\$pattern, '/').\$e, \$rel)===1){
    			\$ignore = true;		    			
    		}
    	}
		    
		if(\$ignore){
		    continue;
		}
			
		\$index[\$rel] = md5(file_get_contents(\$root.\$rel));			
	}
	asort(\$index);
	header('Content-Type: application/json');	
	echo json_encode(\$index, JSON_PRETTY_PRINT);
EOT;

			$hostname = 'ftp://'.$config['ftp']['username'] . ":" . $config['ftp']['password'] . "@" . $config['ftp']['host'] . $config['ftp']['remote'] . '/' . $zapScritp;
			
			/* create a stream context telling PHP to overwrite the file */
			$options = array('ftp' => array('overwrite' => true));
			$stream = stream_context_create($options);
			
			echo "carico script...\n";
			file_put_contents($hostname, $script, 0, $stream);
			echo "eseguo scan...\n";
			file_put_contents($indexFile, file_get_contents('http://'.$config['ftp']['host']. '/' . $zapScritp));
			echo "blank script...\n";
			file_put_contents($hostname, '<pre>(^_^;)</pre>', 0, $stream);
			return;
		}
		 
		$l = strlen($root);
		
		$queue 		= array();
		$queueNew   = array();
		$counters = array(
			'found' => 0,
			'ignored' => 0,
			'changed' => 0,
			'new' => 0
			);
		
		echo "scanning...\n";
				
		foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root)) as $filename)
		{
			$counters['found']++;
			$rel = substr($filename, $l);
			$ignore = false;
		    if(isset($config['ignore'])){
		    	foreach ($config['ignore'] as $pattern){
					$s = '/';
					$e = '/';
		    		if($pattern[0]=='^'){
		    			$pattern = substr($pattern, 1);
		    			$s = '/^';
		    		}
		    		if($pattern[strlen($pattern)-1]=='$'){
		    			$pattern = substr($pattern, 0, -1);
		    			$e = '$/';
		    		}
		    		if(preg_match($s.preg_quote($pattern, '/').$e, $rel)===1){
		    			$ignore = true;		    			
		    		}
		    	}
		    }
		    
		    if($ignore){
		    	$counters['ignored']++;
		    	continue;
		    } 
			//echo "$rel\n";

			if($zap){
				$index[$rel] = md5(file_get_contents($root.$rel));
			}
			
			if(!isset($index[$rel])){
				$counters['new']++;
				$queueNew[]=$rel;
				$queue[]=$rel;
			} elseif ($index[$rel]!=md5(file_get_contents($root.$rel))) {
				$counters['changed']++;
				$queue[]=$rel;
			}
			
		}
		
		echo "\n=== STAT ===\n";
		echo "visited: ".$counters['found']."\n";
		echo "ignored: ".$counters['ignored']."\n";
		echo "new    : ".$counters['new']."\n";
		echo "changed: ".$counters['changed']."\n";
		echo "to sync: ".($counters['changed']+$counters['new'])."\n";
		echo  "=============\n";
		
		if($zap){
			asort($index);
			file_put_contents($indexFile, json_encode($index, JSON_PRETTY_PRINT));
			return;
		}
		
		if($dry){
			return;
		}
		
		if($input->getOption('list-new')){
			foreach ($queueNew as $f){
				echo $f."\n";
			}
			return;
		}
		
		if($input->getOption('list-changed')){
			foreach ($queue as $f){
				echo $f."\n";
			}
			return;
		}
		
		if(!$queue){ return; }
			
		echo "\nReady to sync... You have 10 seconds to CTRL-C and abord.\n";
		for($i=10;$i>0;$i--){
			sleep(1);
			echo $i.'. ';
		}
		echo "\n";
		
		$ftp = ftp_connect($config['ftp']['host']) or die("Could not connect to $ftp_server");
		if($ftp===false){
			die('cant connect');
		}
		if(!ftp_login($ftp, $config['ftp']['username'], $config['ftp']['password'])){
			die('cant login');
		}
		ftp_pasv($ftp, true);
		
		$remote = $config['ftp']['remote'];
		$failed = array();
		asort($queue);
		
		$processed = 0;
		$tot = count($queue);
		$working = array(  '┏(^_^)┛', '┗(^_^﻿)┓', '┗(^_^)┛' , '┏(^_^)┓');
		foreach ($queue as $f){
			$processed++;
			$this->checkForAndMakeDirs($ftp,  $remote.$f);
			if (ftp_put($ftp, $remote.$f, $root.$f, FTP_BINARY)) {
				$index[$f] = md5(file_get_contents($root.$f));
				//echo "=> $f\n";				
				echo "  ".$working[array_rand($working)]." ".round((100/$tot*$processed),1)."%        \r";
			} else {
				echo "There was a problem while uploading $f\n";
				$failed []= $f;
			}			
		}
		
		file_put_contents($indexFile, json_encode($index, JSON_PRETTY_PRINT));
		
		ftp_close($ftp);
		
	}
	
}
