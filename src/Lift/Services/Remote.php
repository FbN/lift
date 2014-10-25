<?php

namespace Lift\Services;

class Remote extends Service {
	
	private $parts = [
		'vendor/pimple/pimple/src/Pimple/Container.php',
		'src/Lift/Services/Service.php',
		'src/Lift/Services/Index.php',
		'src/Lift/Services/Stats.php'
		];
	
	const BATCHSIZE = 10;
	
	protected function scriptPart($src){
		return "// === ".$src." === ".preg_replace('/^\<\?php.*\n/', "\n", file_get_contents(DEVICE.$src));
	}
	
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
	
	public function assembleScript($token){
		
		$config = $this->app['config'];
		
		$root = $config['root'];
		
		$script = "<?php \n";
		
		foreach ($this->parts as $src){
			$script .= $this->scriptPart($src);
		}
		
		$ignoreParts = serialize($config['ignorePatterns']);

		$script.= "\n".<<<EOT
		
	if(\$_GET['token']!=='$token') die('<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Lift!</title></head><body>ლ(ಠ益ಠლ)</body><pre></pre></html>');
		
	\$app = new \Pimple\Container();
		
	\$app['indexService'] =  function (\$c) {
		return new \Lift\Services\Index(\$c);
	};
		
	\$app['stats'] =  function (\$c) {
		return new \Lift\Services\Stats(\$c);
	};
		
	\$app['config'] = array(
		'root' => __DIR__,
		'ignorePatterns' => unserialize('$ignoreParts')
	);
	
	\$index = \$app['indexService']->localBuild();
	asort(\$index);
	header('Content-Type: application/json');	
	echo json_encode(\$index, JSON_PRETTY_PRINT);

EOT;
		//file_put_contents('r.php', $script);
		
		return $script;
	}
	
	public function remoteReindex($command){
		$config = $this->app['config'];
		$host   = $config['host'];
		
		$token = Remote::gen_uuid();
		
		$command->writeH1('Scan and rebuild index from '.$host['host']);
		
		$command->writeln(' build script');
		$hosturi = 'ftp://'.$host['username'] . ":" . $host['password'] . "@" . $host['host'] . $host['folder'] . '/' . $host['remote-script-name'];
		
		$options = array('ftp' => array('overwrite' => true));
		$stream = stream_context_create($options);
		
		$command->writeln(' publish script');
		file_put_contents($hosturi, $this->assembleScript($token), 0, $stream);
		
		$command->writeln(' call remote script, this can take a while...');
		
		$index = json_decode( file_get_contents($host['url'].'/'.$host['remote-script-name'].'?token='.$token), true );
		
		$command->writeln( " blank script...");
		file_put_contents($hosturi, '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Lift!</title></head><body>ˁ˚ᴥ˚ˀ</body><pre></pre></html>', 0, $stream);
		
		return $index;
	}
	
	protected function remoteBatchIndex($batch){
		
		$config = $this->app['config'];
		$host   = $config['host'];
		$hosturi = $host['url'].'/_r.php';

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
		$stats = $this->app['stats'];
		
		$stats->reset(Stats::NEWENTRY);
		$stats->reset(Stats::CHANGED);
		
		$batch = [];
		$diffs = [];
		$indexService = $this->app['indexService'];
		foreach ($index as $f=>$vals){
			$batch[$f]=$vals;
			if(count($batch)>self::BATCHSIZE){				
				$diffs = array_merge(
						$diffs, 
						$indexService->compare($batch, $this->remoteBatchIndex(array_keys($batch)))
				);					
				$batch=[];
			}
		}
		return $diffs;
	}
	
}