<?php
namespace Lift\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class UploadCommand extends LiftCommand
{
	
	protected function configure()
	{
		$this
		->setName('up')
		->setDescription('Sync to server')
		->addOption(
				'defaut-host',
				'H',
				InputOption::VALUE_REQUIRED,
				'Host name to upload.'
		)->addOption(
				'remote-reindex',
				null,
				InputOption::VALUE_NONE,
				'Rebuild the index from remote host.'
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
		);
	}

	protected function exe()
	{
		
		$app = $this->app;
		$config = $app['config'];
		
		$ftp = $app['ftpService'];
		$indexService = $app['indexService'];
		$remoteService = $app['remoteService'];
		
		$index = [];
		
		if($config['remote-reindex']) {
			$index = $remoteService->remoteReindex($this);
			if(!$app['config']['pretend']){
				$indexService->persist($remoteService->remoteReindex($this));
			}	
		} else {
			$index = $indexService->getIndex();
		}
		
		if(!$index) $index = [];
		
		$ftp->connect();
		$index = $ftp->uploadDiff(
				$index,
				$indexService->diff($index),
				$this
			);		
		
		if(!$app['config']['pretend']) $indexService->persist($index);
		
		$ftp->close();
		
		$this->app['stats']->report();
	}
	
}