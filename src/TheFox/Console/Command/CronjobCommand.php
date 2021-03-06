<?php

namespace TheFox\Console\Command;

use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TheFox\PhpChat\Cronjob;

class CronjobCommand extends BasicCommand{
	
	private $cronjob;
	
	public function getLogfilePath(){
		return 'log/cronjob.log';
	}
	
	public function getPidfilePath(){
		return 'pid/cronjob.pid';
	}
	
	protected function configure(){
		$this->setName('cronjob');
		$this->setDescription('Run the Cronjob.');
		$this->addOption('daemon', 'd', InputOption::VALUE_NONE, 'Run in daemon mode.');
		$this->addOption('cycle', 'c', InputOption::VALUE_NONE, 'Run only one cycle.');
		$this->addOption('ping', 'p', InputOption::VALUE_NONE, 'Run only one ping nodes cycle.');
		$this->addOption('msg', 'm', InputOption::VALUE_NONE, 'Run only one cycle with Msgs.');
		$this->addOption('nodes', 'o', InputOption::VALUE_NONE, 'Run only one cycle with Nodes New DB.');
		$this->addOption('bootstrap', 'b', InputOption::VALUE_NONE, 'Run only one cycle with Boostrap Nodes.');
		$this->addOption('shutdown', 's', InputOption::VALUE_NONE, 'Shutdown.');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output){
		$this->executePre($input, $output);
		
		$this->log->info('cronjob start');
		$this->cronjob = new Cronjob();
		$this->cronjob->setLog($this->log);

		try{
			$this->cronjob->init();
		}
		catch(Exception $e){
			$this->log->error('init: '.$e->getMessage());
			exit(1);
		}
		
		if($input->hasOption('cycle') && $input->getOption('cycle')){
			$this->cronjob->cycle();
		}
		elseif($input->hasOption('msg') && $input->getOption('msg')){
			$this->cronjob->cycleMsg();
		}
		elseif($input->hasOption('ping') && $input->getOption('ping')){
			$this->cronjob->cyclePingNodes();
		}
		elseif($input->hasOption('nodes') && $input->getOption('nodes')){
			$this->cronjob->cycleNodesNew();
		}
		elseif($input->hasOption('bootstrap') && $input->getOption('bootstrap')){
			$this->cronjob->cycleBootstrapNodes();
		}
		else{
			try{
				$this->cronjob->loop();
			}
			catch(Exception $e){
				$this->log->error('loop: '.$e->getMessage());
				exit(1);
			}
		}
		
		$this->executePost();
		$this->log->info('exit');
	}
	
	public function signalHandler($signal){
		$this->exit++;
		
		switch($signal){
			case SIGTERM:
				$this->log->notice('signal: SIGTERM');
				break;
			case SIGINT:
				print PHP_EOL;
				$this->log->notice('signal: SIGINT');
				break;
			case SIGHUP:
				$this->log->notice('signal: SIGHUP');
				break;
			case SIGQUIT:
				$this->log->notice('signal: SIGQUIT');
				break;
			case SIGKILL:
				$this->log->notice('signal: SIGKILL');
				break;
			case SIGUSR1:
				$this->log->notice('signal: SIGUSR1');
				break;
			default:
				$this->log->notice('signal: N/A');
		}
		
		$this->log->notice('main abort ['.$this->exit.']');
		
		if($this->cronjob){
			$this->cronjob->setExit($this->exit);
		}
		if($this->exit >= 2){
			exit(1);
		}
	}
	
}
