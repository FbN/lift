<?php
namespace Lift\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class RemoteResetCommand extends LiftCommand
{
	
	protected function configure()
	{
		$this
		->setName('remote-reset')
		->setDescription('reset local index from remote files')
		->addOption(
				'defaut-host',
				'H',
				InputOption::VALUE_REQUIRED,
				'Host name to upload.'
		);
	}

	protected function exe()
	{
		$this->app['indexService']->localBuild();
		$this->app['stats']->report();
	}
}