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
		);
	}

	protected function exe()
	{
		
		$app = $this->app;
		$app['out']->writeln($app['config']['host']);

	}
}