<?php
namespace Lift\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


abstract class LiftCommand extends Command
{
	
	protected $app;

	protected function execute(InputInterface $input, OutputInterface $output)
	{	
		$this->app = \Lift\Lift::app($input, $output);
		$this->exe();
	}
	
	abstract protected function exe();
	

}