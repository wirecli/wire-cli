<?php namespace Wirecli\Commands\Common;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Wirecli\Helpers\PwConnector;

/**
 * Class ServeCommand
 *
 * Example command for passthru()
 *
 * @package Wirecli
 * @link http://php.net/manual/en/function.passthru.php
 * @author Marcus Herrmann
 */
class ViteCommand extends Command {

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this
      ->setName('vite')
      ->setDescription('⚡ Run Vite commands')
      ->addArgument('dev', InputArgument::OPTIONAL, 'Run dev server')
      ->addOption('v', null, InputOption::VALUE_NONE, 'verbose');
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $output->writeln("Starting Vite server...");
    passthru("npm run start");

    return static::SUCCESS;
  }
}
