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
				'pretend',
				'p',
				InputOption::VALUE_NONE,
				'Pretend, do nothing.'
		);
	}

	protected function exe()
	{
		
		$app = $this->app;
		
		$ftp = $app['ftpService'];
		$indexService = $app['indexService'];
		
		$index = $indexService->getIndex();
		
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