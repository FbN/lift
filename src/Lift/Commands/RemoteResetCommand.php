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
		);
	}

	protected function exe()
	{		
		$config = $this->app['config'];
		$host   = $config['host'];
		
		$token = Remote::gen_uuid();
		
		$this->writeH1('Scan and rebuild index from '.$host['host']);
		
		$this->writeln(' build script');
		$hosturi = 'ftp://'.$host['username'] . ":" . $host['password'] . "@" . $host['host'] . $host['folder'] . '/' . $host['remote-script-name'];
		
		$options = array('ftp' => array('overwrite' => true));
		$stream = stream_context_create($options);
		
		$this->writeln(' publish script');
		file_put_contents($hosturi, $this->app['remoteService']->assembleScript($token), 0, $stream);
		
		$this->writeln(' call remote script, this can take a while...');
		
		$this->app['indexService']->persist
		(
				json_decode
				(
						file_get_contents($host['url'].'/'.$host['remote-script-name'].'?token='.$token)
				)
		);
		
		echo " blank script...\n";
		file_put_contents($hosturi, '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Lift!</title></head><body>ˁ˚ᴥ˚ˀ</body><pre></pre></html>', 0, $stream);
	}
}