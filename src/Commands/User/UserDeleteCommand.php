<?php namespace Wirecli\Commands\User;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wirecli\Helpers\PwUserTools;
use Wirecli\Helpers\WsTools as Tools;

/**
 * Class UserDeleteCommand
 *
 * Creating ProcessWire users
 *
 * @package Wirecli
 * @author Marcus Herrmann
 * @author Tabea David <info@justonestep.de>
 */

class UserDeleteCommand extends PwUserTools {

  /**
   * Configures the current command.
   */
  public function configure() {
    $this
      ->setName('user:delete')
      ->setDescription('Deletes ProcessWire users')
      ->addArgument('name', InputArgument::OPTIONAL, 'Name of user')
      ->addOption('role', null, InputOption::VALUE_REQUIRED, 'Delete user(s) by role');
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

    // if argument role is provided, delete all users with specific role
    if ($role = $input->getOption('role')) {
      $users = \ProcessWire\wire('users')->find("roles=$role");

      foreach ($users as $user) {
        \ProcessWire\wire('users')->delete($user);
      }

      $output->writeln("<info>Deleted {$users->count()} users successfully!</info>");
    } else {
      // check name
      $availableUsers = parent::getAvailableUsers(true);
      $urs = $input->getArgument('name');
      $users = $urs ? explode(',', $urs) : null;

      $users = $tools->askChoice($users, 'Select all users which should be deleted', $availableUsers, 0, true);

      $tools->nl();
      foreach ($users as $name) {
        if (\ProcessWire\wire('users')->get($name) instanceof \ProcessWire\NullPage) {
          $tools->writeError("User '{$name}' doesn't exists.");
        } else {
          $user = \ProcessWire\wire('users')->get($name);
          \ProcessWire\wire('users')->delete($user);
          $tools->writeSuccess("User '{$name}' deleted successfully.");
        }
      }
    }

    return static::SUCCESS;
  }
}
