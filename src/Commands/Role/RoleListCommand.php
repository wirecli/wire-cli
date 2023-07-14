<?php namespace Wirecli\Commands\Role;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wirecli\Helpers\PwUserTools;
use Wirecli\Helpers\WsTools as Tools;

/**
 * Class RoleListCommand
 *
 * List ProcessWire user roles
 *
 * @package Wirecli
 * @author Tabea David
 */
class RoleListCommand extends PwUserTools {

  /**
   * Configures the current command.
   */
  public function configure() {
    $this
      ->setName('role:list')
      ->setDescription('Lists ProcessWire role(s)');
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    $this->init($input, $output);
    $tools = new Tools($output);
    $tools->writeBlockCommand($this->getName());

    foreach (\ProcessWire\wire('roles') as $role) {
      $tools->writeInfo("  - {$role->name}");
    }

    return static::SUCCESS;
  }
}
