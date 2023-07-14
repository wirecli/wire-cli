<?php namespace Wirecli\Commands\Backup;

use Exception;
use ProcessWire\Comment;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Wirecli\Helpers\WsTools as Tools;
use Wirecli\Helpers\WsTables as Tables;

/**
 * Class BackupDatabaseCommand
 *
 * Performs database dump
 *
 * @package Wirecli
 * @author Marcus Herrmann
 * @author Tabea David
 */
class DuplicatorListCommand {

  /**
   * List of packages
   * 
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  public function list(InputInterface $input, OutputInterface $output, string $name) {
    //$this->init($input, $output);
    $tools = new Tools($output);
    $tools->writeBlockCommand($name);
  
    try {
      $duplicator_mod = \ProcessWire\wire('modules')->get('Duplicator');
      $packages = \ProcessWire\Dup_Util::getPackages(\ProcessWire\wire('config')->paths->assets . 'backups', 'zip');
      $return = array();
      foreach ($packages as $pkg) {
        if (strrchr($pkg, '.') != strrchr($duplicator_mod::DUP_PACKAGE_EXTENSION, '.')) continue;
        $packagePath = $duplicator_mod->path .DIRECTORY_SEPARATOR. $pkg;
        $return[] = array(
          'name' => $pkg,
          'path' => $packagePath,
          'size' => \ProcessWire\DUP_Util::human_filesize(\ProcessWire\DUP_Util::filesize($packagePath)),
          'date' => date('Y-m-d H:i:s', filemtime($packagePath)),
          'type' => 'local'
        );
      }

      if (count($return) == 0) {
        $tools->writeBlockBasic("No packages found");
        return Command::SUCCESS;
      }
      $data = [];
      foreach ($return as $package) {
        $data[] = array(
          $package['name'],
          \ProcessWire\wireRelativeTimeStr($package['date']),
          \ProcessWire\Dup_Util::filesize($package['path']),
          $package['type']
        );
      }
      $headers = array('Name', 'Modified', 'Size', 'Type');
      $tables = new Tables($output);  
      $logTables = array($tables->buildTable($data, $headers));
      $tables->renderTables($logTables, false);
      $count = count($data);
      $tools->writeCount($count);
    } catch (Exception $e) {
      $tools->writeBlockError($e->getMessage());
      return Command::FAILURE;
    }
  
    return Command::SUCCESS;
  }
}
