<?php
namespace Lift\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class SyncCommand extends LiftCommand
{
	
	protected function configure()
	{
		$this
		->setName('sync')
		->setDescription('Sync to server')
		->addOption(
				'defaut-host',
				'H',
				InputOption::VALUE_REQUIRED,
				'Host name to upload.'
		)->addOption(
				'remote-index',
				null,
				InputOption::VALUE_NONE,
				'Dom\'t trust index. Remote check files status.'
		)->addOption(
				'pretend',
				'p',
				InputOption::VALUE_NONE,
				'Pretend, do nothing.'
		)->addOption(
				'check-time',
				null,
				InputOption::VALUE_NONE,
				'Lift compare files by md5 checksums. If you can trust your files modification time, you can speedup the upload. Can be used with check-size.'
		)->addOption(
        	'list-new',
        	null,
        	InputOption::VALUE_NONE,
        	'New files')
        ->addOption(
   			'list-modified',
        	null,
        	InputOption::VALUE_NONE,
        	'Changed files');
	}

	protected function exe()
	{
		$app = $this->app;
		$config = $app['config'];
		
		$ftp = $app['ftpService'];
		$indexService = $app['indexService'];
		$remoteService = $app['remoteService'];
		
		$index = [];
		$diffs = [];
		
		$ftp->connect();
		
		if($config['remote-index']) {
			$index = $indexService->localBuild(false);
			$diff = $remoteService->remoteDiff($index);
			$diffs = [];
			foreach ($diff as $f){
				$diffs[$f] = $index[$f];
			}
			$index = $ftp->uploadDiff(
					$index,
					$diffs,
					$this
			);
		} else {
			$index = $indexService->getIndex();
			if($index===null) $index = [];
			$index = $ftp->uploadDiff(
					$index,
					$diffs=$indexService->diff($index),
					$this
			);
		}
		
		if(!$app['config']['pretend']) $indexService->persist($index);
		
		$ftp->close();
		
		$this->app['stats']->report();
	}
	
}