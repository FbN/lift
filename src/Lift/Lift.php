<?php

namespace Lift;

use Pimple\Container;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class Lift {
	
	static public function app(InputInterface $input, OutputInterface $output){
		
		$app = new Container();
		
		$app['in'] = $input;
		
		$app['out'] = $output;
		
		$app['configService'] =  function ($c) {
			return new \Lift\Services\Config($c);
		};
		
		$app['indexService'] =  function ($c) {
			return new \Lift\Services\Index($c);
		};
		
		$app['ftpService'] =  function ($c) {
			return new \Lift\Services\Ftp($c);
		};
		
		$app['config'] = function ($c) {
			return $c['configService']->load();
		};
		
		$app['stats'] =  function ($c) {
			return new \Lift\Services\Stats($c);
		};
		
// 		$app['manifest'] = function ($c) {
// 			return $c['configService']->load();
// 		};		
		
		return $app;
		
	}
	
}