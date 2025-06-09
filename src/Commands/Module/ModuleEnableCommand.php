<?php namespace Wirecli\Commands\Module;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wirecli\Helpers\PwModuleTools;
use Wirecli\Helpers\WsTools as Tools;

/**
 * Class ModuleEnableCommand
 *
 * Enables provided module(s)
 *
 * @package Wirecli
 * @author Tabea David <info@justonestep.de>
 */
class ModuleEnableCommand extends PwModuleTools {

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this
      ->setName('module:enable')
      ->setDescription('Enables provided module(s)')
      ->addArgument('modules', InputArgument::OPTIONAL, 'Provide one or more module class name, comma separated: Foo,Bar')
      ->addOption('github', null, InputOption::VALUE_OPTIONAL, 'Download module via github. Use this option if the module isn\'t added to the ProcessWire module directory.')
      ->addOption('branch', null, InputOption::VALUE_OPTIONAL, 'Optional. Define specific branch to download from.');
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->init($input, $output);
    $this->tools = new Tools($output);
    $this->tools->setHelper($this->getHelper('question'))
      ->setInput($input)
      ->writeBlockCommand($this->getName());

    // Refresh modules to discover any manually copied modules
    \ProcessWire\wire('modules')->refresh();

    $modules = $this->tools->ask($input->getArgument('modules'), 'Modules', null, false, null, 'required');
    if (!is_array($modules)) $modules = explode(',', $modules);

    foreach ($modules as $module) {
      $module = trim($module);
      
      // if module doesn't exist, download the module
      if (!$this->checkIfModuleExists($module)) {
        $this->tools->writeComment("Cannot find '{$module}' locally, trying to download...");
        $this->tools->nl();
        
        try {
          $this->passOnToModuleDownloadCommand($module, $output, $input);
          // Refresh modules after download to make sure the newly downloaded module is discoverable
          \ProcessWire\wire('modules')->refresh();
        } catch (\Exception $e) {
          $this->tools->writeError("Failed to download module '{$module}': " . $e->getMessage());
          continue;
        }
      }

      // check whether module is already installed
      if (\ProcessWire\wire('modules')->isInstalled($module)) {
        $this->tools->writeInfo(" Module `{$module}` is already installed.");
        continue;
      }

      // install module with proper CLI options
      $options = array(
        'noPermissionCheck' => true,
        'noInit' => true
      );

      try {
        if (\ProcessWire\wire('modules')->getInstall($module, $options)) {
          $this->tools->writeSuccess(" Module `{$module}` installed successfully.");
        } else {
          $this->tools->writeError(" Module `{$module}` installation failed.");
        }
      } catch (\Exception $e) {
        $this->tools->writeError(" Module `{$module}` does not exist or installation failed: " . $e->getMessage());
      }
    }

    return static::SUCCESS;
  }

  private function passOnToModuleDownloadCommand($module, $output, $input) {
    $command = $this->getApplication()->find('module:download');

    $arguments = array(
      'command' => 'module:download',
      'modules' => $module,
      '--github' => $input->getOption('github'),
      '--branch' => $input->getOption('branch')
    );

    $passOnInput = new ArrayInput($arguments);
    $command->run($passOnInput, $output);
  }
}
