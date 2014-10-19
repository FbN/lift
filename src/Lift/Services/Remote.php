<?php

namespace Lift\Services;

class Remote extends Service {
	
	private $parts = [
		'vendor/pimple/pimple/src/Pimple/Container.php',
		'src/Lift/Services/Service.php',
		'src/Lift/Services/Index.php',
		'src/Lift/Services/Stats.php'
		];
	
	protected function scriptPart($src){
		return "// === ".$src." === ".preg_replace('/^\<\?php.*\n/', "\n", file_get_contents($src));
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
	
}