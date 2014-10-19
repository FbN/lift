<?php
namespace Lift\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Lift\Services\Remote;


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
		);
	}

	protected function exe()
	{		
		$this->app['indexService']->persist
		(
				$this->app['remoteService']->remoteReindex($this)
		);
		$this->app['stats']->report();
		
	}
}