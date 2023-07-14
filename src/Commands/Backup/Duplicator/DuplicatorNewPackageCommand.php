<?php namespace Wirecli\Commands\Backup\Duplicator;

use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Rah\Danpu\Dump;
use Rah\Danpu\Export;
use Wirecli\Helpers\PwConnector;
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
class DuplicatorNewPackageCommand {

  protected $dupmod;
  protected $progressBar; 

  /**
   * Builds a new package
   * 
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  public function new(InputInterface $input, OutputInterface $output, string $name = null) {
    
    $tools = new Tools($output);
    $tools->writeBlockCommand($name);
  
    //$tools->writeInfo('Creating package...');

    try {
      $package = $this->buildPackage($output);
      if ($package) {
        // check package error
        if (count($package['info']['pw']['errors'])) {
          foreach ($package['info']['pw']['errors'] as $error) {
            $tools->writeError($error);
          }
          return Command::FAILURE;
        }

        // return success
        $name = basename($package['info']['pw']['zipfile']);
        $size = \ProcessWire\DUP_Util::human_filesize(\ProcessWire\DUP_Util::filesize($package['info']['pw']['zipfile']));
        $data = array(
          array(
            $name,
            $size,
            'local'
          )
        );
        $tools->nl(3);
        $headers = array('Name', 'Size', 'Type');
        $tables = new Tables($output);  
        $logTables = array($tables->buildTable($data, $headers));
        $tables->renderTables($logTables, false);
        $tools->writeSuccess('Package created');
        $tools->nl();
      } else {
        $tools->writeError('Package creation failed');
        return Command::FAILURE;
      }
    } catch (Exception $e) {
      $tools->writeBlockError($e->getMessage());
      return Command::FAILURE;
    }
  
    return Command::SUCCESS;
  }

  /*
    * build the package
    * return a ZIP file containing the database and ProcessWire stucture zipped files
    * TODO: better management for future use in deployment
    */
  protected function buildPackage(OutputInterface $output)
  {
    $this->dupmod = \ProcessWire\wire('modules')->get('Duplicator');

    // ProgressBar::setPlaceholderFormatterDefinition('current', function (ProgressBar $bar) {
    //   return str_pad($bar->getProgress(), 2, ' ', STR_PAD_LEFT);
    // });
    // ProgressBar::setPlaceholderFormatterDefinition('max', function (ProgressBar $bar) {
    //   return $bar->getMaxSteps();
    // });


    $tools = new Tools($output);
    $tools->nl();
        
    // creates a new progress bar (50 units)
    $progressBar = new ProgressBar($output, 3);
    $progressBar->setBarCharacter('<fg=green>⚬</>');
    $progressBar->setEmptyBarCharacter("<fg=red>⚬</>");
    $progressBar->setProgressCharacter("<fg=green>➤</>");
    $progressBar->setFormat(
      "%status%\n%current%/%max% [%bar%] %percent:3s%%\n  %estimated:-6s%  %memory:6s%"
    );
    $progressBar->setRedrawFrequency(max(1, floor(3 / 1000)));
    $progressBar->setBarWidth(60);
    $progressBar->setMessage("Starting...", 'status');
    $progressBar->start();

    \ProcessWire\DUP_Util::timer('build');

    $defaultOptions = array(
      'filename' => \ProcessWire\DUP_Util::formatFilename(str_replace('-', '_', $this->dupmod->packageName), 'package.zip'),
      'path' => $this->dupmod->path,
    );
    $options = $defaultOptions;
    $packageInfos = array();

    try {
      $dbbackup = $this->dupmod->buildDatabaseBackup();
      if ($dbbackup == false) {
        \ProcessWire\DUP_Logs::log("- an error occured during database backup.");
        return false;
      }
      $packageInfos['db'] = $dbbackup;
      $progressBar->advance(0);
      $progressBar->setMessage("Database backup done...", 'status');

      $pwbackup = $this->dupmod->buildProcessWireBackup();
      if ($pwbackup == false) {
        \ProcessWire\DUP_Logs::log("- an error occured during package build.");
        return false;
      }
      $packageInfos['pw'] = $pwbackup;
      $progressBar->advance(1);
      $progressBar->setMessage("Directory backup done...", 'status');


      if (true) //ATO: Add SQL ZIP to archive
      {
        $zip = new \ZipArchive();
        if ($zip->open($pwbackup['zipfile']) !== true) {
          throw new \ProcessWire\WireException("Unable to open ZIP: {$pwbackup['zipfile']}");
        }

        $zip->addFile($dbbackup['zipfile'], basename($dbbackup['zipfile']));
        $zip->close();
        $zipfile = $pwbackup['zipfile']; //ATO: Fix to properly return new archive
        \ProcessWire\DUP_Util::deleteFile($dbbackup['zipfile']);
      } else {
        $files = array(
          $packageInfos['db']['zipfile'],
          $packageInfos['pw']['zipfile'],
        );
        $zipfile = $options['path'] . DS . $options['filename'];
        $result = \ProcessWire\wireZipFile($zipfile, $files);
        foreach ($files as $file) {
          \ProcessWire\DUP_Util::deleteFile($file);
        }
        
        foreach ($result['errors'] as $error) {
          \ProcessWire\DUP_Logs::log("ZIP add failed: $error");
        }
      }

      if (file_exists($zipfile)) {
        $package['zipfile'] = $zipfile;
        $package['size'] = filesize($zipfile);
        $package['info'] = $packageInfos;
        //$fp = fopen($options['path'] . DS . $options['filename'] . '.json', 'w');
        //fwrite($fp, json_encode($package));
        //fclose($fp);
        \ProcessWire\DUP_Logs::log("- package built successfully in " . \ProcessWire\DUP_Util::timer('build') . "sec");

        
        $progressBar->advance(2);
        $progressBar->setMessage("Package built", 'status');

        return $package;
      } else {
        \ProcessWire\DUP_Logs::log("- package build failed, {$zipfile} doesn't exist");
        return false;
      }

      $progressBar->finish();

    } catch (\Exception $ex) {
      \ProcessWire\DUP_Logs::log($ex->getMessage());
    }
  
    return false;
  }
}
