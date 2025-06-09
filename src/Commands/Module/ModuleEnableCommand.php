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

    // Force module discovery to detect manually copied modules
    // This ensures modules are found even before first admin login
    \ProcessWire\wire('modules')->refresh();

    $modules = $this->tools->ask($input->getArgument('modules'), 'Modules', null, false, null, 'required');
    if (!is_array($modules)) $modules = explode(',', $modules);

    foreach ($modules as $module) {
      $module = trim($module);
      
      // Validate that module exists after refresh
      if (!\ProcessWire\wire('modules')->isInstalled($module) && !\ProcessWire\wire('modules')->get($module)) {
        $this->tools->writeError("Module '{$module}' not found. Make sure it's installed in /site/modules/ and refresh was called.");
        continue;
      }
      
      $this->tools->nl()
        ->writeInfo("Attempting to enable '{$module}' module.")
        ->nl();

      try {
        if (!\ProcessWire\wire('modules')->isInstalled($module)) {
          \ProcessWire\wire('modules')->install($module);
          $this->tools->writeSuccess("Module '{$module}' installed successfully!");
        } else {
          $this->tools->writeComment("Module '{$module}' is already installed.");
        }
      } catch (\Exception $e) {
        $this->tools->writeError("Could not install module '{$module}': " . $e->getMessage());
      }
    }

    // Final refresh to ensure module registry is up to date
    \ProcessWire\wire('modules')->refresh();

    return 0;
  }

  private function checkIfModuleExistsLocally($module, $output, $input) {
    if (!$this->checkIfModuleExists($module)) {
      $output->writeln("<comment>Cannot find '{$module}' locally, trying to download...</comment>");
      $this->passOnToModuleDownloadCommand($module, $output, $input);
    }

  }

  private function passOnToModuleDownloadCommand($module, $output, $input) {
    $command = $this->getApplication()->find('mod:download');

    $arguments = array(
      'command' => 'mod:download',
      'modules' => $module,
      '--github' => $input->getOption('github'),
      '--branch' => $input->getOption('branch')
    );

    $passOnInput = new ArrayInput($arguments);
    $command->run($passOnInput, $output);
  }
}
