<?php namespace Wirecli\Commands\Role;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wirecli\Helpers\PwUserTools;
use Wirecli\Helpers\WsTools as Tools;

/**
 * Class RoleCreateCommand
 *
 * Creating ProcessWire user roles
 *
 * @package Wirecli
 * @author Marcus Herrmann
 */
class RoleCreateCommand extends PwUserTools {

  /**
   * Configures the current command.
   */
  public function configure() {
    $this
      ->setName('role:create')
      ->setDescription('Creates a ProcessWire role')
      ->addArgument('name', InputArgument::OPTIONAL, 'comma-separated list');
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    $this->init($input, $output);
    $tools = new Tools($output);
    $tools
      ->setInput($input)
      ->setHelper($this->getHelper('question'));
    $tools->writeBlockCommand($this->getName());

    $names = $tools->ask($input->getArgument('name'), 'Enter roles to add, comma-separated');
    foreach (explode(',', preg_replace('/\s+/', '', $names)) as $name) {
      if (!\ProcessWire\wire('roles')->get($name) instanceof \ProcessWire\NullPage) {
        $tools->writeError("Role '{$name}' already exists.");
      } else {
        \ProcessWire\wire('roles')->add($name);
        $tools->writeSuccess("Role '{$name}' created successfully.");
      }
    }

    return static::SUCCESS;
  }

}
