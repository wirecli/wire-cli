<?php namespace Wirecli\Commands\Common;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
class ServeCommand extends PwConnector {

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this
      ->setName('serve')
      ->setDescription('Serve ProcessWire via built in PHP webserver');
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->checkForProcessWire($output);
    $output->writeln("Starting PHP server at localhost:8000");
    passthru("php -S localhost:8000");

    return static::SUCCESS;
  }
}
