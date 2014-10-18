<?php
namespace Lift\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


abstract class LiftCommand extends Command
{
	
	private $lastH;
	
	protected $app;

	protected function execute(InputInterface $input, OutputInterface $output)
	{	
		$this->app = \Lift\Lift::app($input, $output);
		$this->exe();
	}
	
	abstract protected function exe();
	
	protected function writeln($t){
		$this->app['out']->writeln($t);
	}
	
	protected function writeH1($t){
		$t = '*** '.$t.' ***';
		$this->app['out']->writeln("<question>$t</question>");
		$this->lastH = 	$t;	
	}
	
	protected function writeHR(){
		$this->app['out']->writeln("<info>".str_pad('', strlen($this->lastH), '*')."</info>\n");
	}
	

}