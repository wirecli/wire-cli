<?php namespace Wirecli\Commands\Backup;

use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Rah\Danpu\Dump;
use Rah\Danpu\Export;
use Symfony\Component\Console\Input\InputArgument;
use Wirecli\Commands\Backup\Duplicator\DuplicatorNewPackageCommand;
use Wirecli\Helpers\PwConnector;
use Wirecli\Helpers\WsTools as Tools;
use Wirecli\Helpers\WsTables as Tables;

include_once __DIR__ . '/Duplicator/DuplicatorListCommand.php';
include_once __DIR__ . '/Duplicator/DuplicatorNewPackageCommand.php';

/**
 * Class BackupDatabaseCommand
 *
 * Performs database dump
 *
 * @package Wirecli
 * @author Marcus Herrmann
 * @author Tabea David
 */
class BackupDuplicatorCommand extends PwConnector {

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this
      ->setName('backup:duplicator')
      ->setDescription('Performs Duplicator operation on packages')
      ->addArgument('list', InputArgument::OPTIONAL, 'List packages on local filesystem')
      ->addArgument('new', InputArgument::OPTIONAL, 'Backup database and file as zip package');
  }

  /**
   * List of packages
   * 
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->init($input, $output);
    $cmdName = $this->getName();

    $return = null;

    $arg = $input->getArgument('list');
    if ($arg === 'list') {
      $cmdName = sprintf('%s:%s', $cmdName, 'list');
      $packageList = new DuplicatorListCommand();
      $return = $packageList->list($input, $output, $cmdName);
    } 
    elseif ($input->getArgument('new')) {
      $cmdName = sprintf('%s:%s', $cmdName, 'new');
      $packageNew = new DuplicatorNewPackageCommand();
      $return = $packageNew->new($input, $output, $cmdName);
    }
    else {
      $output->writeln('No argument given');
      return static::FAILURE;
    }
      
    return $return;
  }
}
