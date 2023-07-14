<?php namespace Wirecli\Commands\Template;

use ProcessWire\Template;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wirecli\Helpers\PwConnector;
use Wirecli\Helpers\WsTools as Tools;

/**
 * Class TemplateDeleteCommand
 *
 * Deletes ProcessWire templates
 *
 * @package Wirecli
 * @author Tabea David
 */
class TemplateDeleteCommand extends PwConnector {

  /**
   * Configures the current command.
   */
  public function configure() {
    $this
      ->setName('template:delete')
      ->setDescription('Deletes ProcessWire template(s)')
      ->addArgument('name', InputArgument::OPTIONAL, 'Name of template(s)')
      ->addOption('nofile', null, InputOption::VALUE_NONE, 'Prevents template file deletion');
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
      ->setHelper($this->getHelper('question'))
      ->setInput($input)
      ->writeBlockCommand($this->getName());

    // ask which template should be deleted
    $availableTemplates = $this->getAvailableTemplates();
    $tmplsString = $input->getArgument('name');
    $names = $tmplsString ? explode(',', $tmplsString) : null;
    $names = $tools->askChoice($names, 'Select all templates which should be deleted', $availableTemplates, 0, true);
    $tools->nl();

    $templates = \ProcessWire\wire('templates');
    $fieldgroups = \ProcessWire\wire('fieldgroups');

    foreach ($names as $name) {
      $template = $templates->get($name);
      if ($template->id) {
        // try to delete depending file?
        if (!$input->getOption('nofile') && file_exists($template->filename)) {
          unlink($template->filename);
        }

        $template->flags = Template::flagSystemOverride;
        $template->flags = 0; // all flags now removed, can be deleted
        $templates->delete($template);

        // delete depending fieldgroups
        $fg = $fieldgroups->get($name);
        if ($fg->id) $fieldgroups->delete($fg);
        $tools->writeSuccess("Template '{$name}' deleted successfully.");
      } else {
        $tools->writeError("Template '{$name}' doesn't exist.");
      }
    }

    return static::SUCCESS;
  }

}
