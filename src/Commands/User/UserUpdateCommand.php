<?php namespace Wirecli\Commands\User;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wirecli\Helpers\PwUserTools;
use Wirecli\Helpers\WsTools as Tools;

/**
 * Class UserCreateCommand
 *
 * Creating ProcessWire users
 *
 * @package Wirecli
 * @author Marcus Herrmann
 */

class UserUpdateCommand extends PwUserTools {

  const PASS_MASK = '*****';

  /**
   * Configures the current command.
   */
  public function configure() {
    $this
      ->setName('user:update')
      ->setDescription('Updates a ProcessWire user')
      ->addArgument('name', InputArgument::OPTIONAL, 'Name of user')
      ->addOption('newname', null, InputOption::VALUE_REQUIRED, 'Supply an user name')
      ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Supply an email address')
      ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Supply a password')
      ->addOption('roles', null, InputOption::VALUE_REQUIRED, 'Attach existing roles to user, comma separated');
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

    // ask for username
    $name = $tools->askChoice(
      $input->getArgument('name'),
      'Which user should be updated?',
      $this->getAvailableUsers(),
      0
    );

    $tools->nl();

    // check if user exists
    $user = \ProcessWire\wire('pages')->get("name={$name}");
    if ($user instanceof \ProcessWire\NullPage) {
      $tools->writeError("User '{$name}' does not exist.");
      exit(1);
    }

    // update roles, pass, name and email
    $roles = $input->getOption('roles') ? explode(",", $input->getOption('roles')) : null;
    $pass = $input->getOption('password');
    $newname = $input->getOption('newname');
    $email = $input->getOption('email');

    if (!$roles && !$pass && !$newname && !$email) {
      $newname = $tools->ask('', 'New name', $name);
      $email = $tools->ask('', 'E-Mail-Address', $user->email, false, null, 'email');

      $pass = $tools->ask($input->getOption('password'), 'Password', self::PASS_MASK, true);
      if ($pass === self::PASS_MASK) $pass = null;
    }

    $user = $this->updateUser($name, $pass, $email, $newname);
    $user->save();

    // ROLES
    // get available roles
    $availableRoles = $this->getAvailableRoles($input->getOption('roles'));

    // get current roles
    $currentRoles = array();
    foreach ($user->roles as $role) {
      $currentRoles[array_search($role->name, $availableRoles)] = $role->name;
    }

    // askQuestion
    $roles = $tools->askChoice(
      '',
      'Which roles should be attached',
      $availableRoles,
      implode(',', array_keys($currentRoles)),
      true
    );

    if ($roles) $this->attachRolesToUser($name, $roles, $output);

    $tools->nl();
    $tools->writeSuccess("User '{$name}' updated successfully.");

    return static::SUCCESS;
  }

}
