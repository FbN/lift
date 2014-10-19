<?php
namespace Lift\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class BuildCommand extends LiftCommand
{
	
	const STUB = <<<EOT
<?php
	Phar::mapPhar();			
	Phar::mount('lift.json', __DIR__ . '/lift.json');			
	Phar::mount('lift.lock', __DIR__ . '/lift.lock');
	include "phar://lift.phar/lift.php";
	__HALT_COMPILER();
EOT;
	
	protected function configure()
	{
		$this
		->setName('build')
		->setDescription('Create the project phar archive');
	}

	protected function exe()
	{
		$app = $this->app;
		
		$this->writeH1('Building phar');
		$phar = new \Phar(
			"lift.phar",
			\FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::KEY_AS_FILENAME, 
			"lift.phar"
			);
		
		$phar->buildFromDirectory("./",'/.php$/');
		
		$phar->setStub(self::STUB);		
		$this->writeln('lift.phar');
		$this->writeln('files packd: '.$phar->count());
		$this->writeHR();
	}
	
}