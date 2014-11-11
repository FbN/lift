<?php

namespace Lift\Services;

class Remote extends Service {
	
	private $remoteScript = '____lift.php';
	
	private $parts = [
		'vendor/pimple/pimple/src/Pimple/Container.php',
		'src/Lift/Services/Service.php',
		'src/Lift/Services/Index.php',
		];
	
	const BATCHSIZE = 10;
	
	static function gen_uuid() {
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
				// 32 bits for "time_low"
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
	
				// 16 bits for "time_mid"
				mt_rand( 0, 0xffff ),
	
				// 16 bits for "time_hi_and_version",
				// four most significant bits holds version number 4
				mt_rand( 0, 0x0fff ) | 0x4000,
	
				// 16 bits, 8 bits for "clk_seq_hi_res",
				// 8 bits for "clk_seq_low",
				// two most significant bits holds zero and one for variant DCE1.1
				mt_rand( 0, 0x3fff ) | 0x8000,
	
				// 48 bits for "node"
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
		);
	}
	
	protected function remoteBatchIndex($batch, $token){
		
		$config = $this->app['config'];
		$host   = $config['host'];
		$hosturi = $host['url'].'/'.$host['remote-script-name'].'?token='.$token;

		// Create the context for the request
		$context = stream_context_create(array(
				'http' => array(
						// http://www.php.net/manual/en/context.http.php
						'method' => 'POST',
						'header'  => 'Content-type: application/json',
						'content' => json_encode(array('files'=>$batch))
				)
		));
		
		// Send the request
		$response = file_get_contents($hosturi, FALSE, $context);
		
		// Check for errors
		if($response === FALSE){
			throw new \RuntimeException('Error remote check');
		}
		
		// Decode the response
		$response = json_decode($response, true);
		if($response['index']===null){
			die(var_dump(json_encode(array('files'=>$batch))));
		}
		return $response['index'];
	}
	
	public function remoteDiff($index){
		
		$config = $this->app['config'];
		$host   = $config['host'];
		$stats = $this->app['stats'];
		
		$stats->reset(Stats::NEWENTRY);
		$stats->reset(Stats::CHANGED);
		
		$batch = [];
		$diffs = [];
		$indexService = $this->app['indexService'];
		
		$token = Remote::gen_uuid();
		
		/* remote script setup */
		$hosturi = 'ftp://'.$host['username'] . ":" . $host['password'] . "@" . $host['host'] . $host['folder'] . '/' . $host['remote-script-name'];
		$options = array('ftp' => array('overwrite' => true));
		$stream = stream_context_create($options);
		file_put_contents(
			$hosturi, 
			str_replace(
				'##token##', 
				$token, 
				file_get_contents($this->remoteScript)
				), 
			0, 
			$stream
		);
		
		/* batch compare */
		foreach ($index as $f=>$vals){
			$batch[$f]=$vals;
			if(count($batch)>self::BATCHSIZE){				
				$diffs = array_merge(
						$diffs, 
						$indexService->compare($batch, $this->remoteBatchIndex(array_keys($batch), $token))
				);					
				$batch=[];
			}
		}
		
		/* wipe off script */
		unlink($hosturi, $stream);
		
		return $diffs;
	}
	
}