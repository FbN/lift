<?php
namespace Lift\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class DiffCommand extends LiftCommand
{
	
	protected function configure()
	{
		$this
		->setName('diff')
		->setDescription('check for diffs beetwen working copy and index')
		->addOption(
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
		$indexService = $this->app['indexService']; 
		$index = $indexService->loadOrLocalBuild();
		$diffs = $indexService->diff( $index );
		if($this->app['config']['list-new']){
			$this->writeH1('New files'); 
			foreach (array_diff_key($diffs, $index) as $rel=>$v){
					$this->writeln("$rel");
			}
			$this->writeHR();
		}
		if($this->app['config']['list-modified']){
			$this->writeH1('Changed files');
			foreach (array_intersect_key($diffs, $index) as $rel=>$v){
				$this->writeln("$rel");
			}
			$this->writeHR();
		}
		$this->app['stats']->report();
	}
}