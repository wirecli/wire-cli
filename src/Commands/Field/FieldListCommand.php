<?php namespace Wirecli\Commands\Field;

use ProcessWire\Field;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wirecli\Helpers\PwConnector;
use Wirecli\Helpers\PwTools;
use Wirecli\Helpers\WsTables as Tables;
use Wirecli\Helpers\WsTools as Tools;

/**
 * Class FieldListCommand
 *
 * Lists all available fields
 *
 * @package Wirecli
 * @author Tabea David
 */
class FieldListCommand extends PwConnector {

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this
      ->setName('field:list')
      ->setDescription('Lists all available fields.')
      ->addOption('all', null, InputOption::VALUE_NONE, 'Show built-in fields. By default, system/permanent fields are not shown.')
      ->addOption('template', null, InputOption::VALUE_REQUIRED, 'Filter by template. When selected, only the fields from a specific template will be shown.')
      ->addOption('tag', null, InputOption::VALUE_REQUIRED, 'Filter by tag. When selected, only the fields with a specific tag will be shown.')
      ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Filter by field type. When specified, only fields of the selected type will be shown.')
      ->addOption('unused', null, InputOption::VALUE_NONE, 'Only list unused fields.');
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->init($input, $output);
    $tools = new Tools($output);
    $tools->writeBlockCommand($this->getName());

    // get available fields
    $fieldtypes = array();
    foreach (\ProcessWire\wire('modules') as $module) {
      if (preg_match('/^Fieldtype/', $module->name)) {
        $fieldtypes[] = $module->name;
      }
    }

    $headers = array('Name', 'Label', 'Type', 'Templates');
    $data = $this->getData($this->getFilter($input));

    if (count($data->count) > 0) {
      foreach ($data->content as $tag => $c) {
        $tools->writeInfo('--- ' . strtoupper($tag) . ' ---');
        $tables = new Tables($output);
        $fieldTables = array($tables->buildTable($c, $headers));
        $tables->renderTables($fieldTables, false);
      }
    }

    $tools->writeCount($data->count);

    return static::SUCCESS;
  }

  /**
   * @param InputInterface $input
   * @return array
   */
  private function getFilter($input) {
    $filter = array();
    $filter['all'] = $input->getOption('all') ? true : false;
    $filter['unused'] = $input->getOption('unused') ? true : false;
    $filter['template'] = $input->getOption('template') ? $input->getOption('template') : '';
    $filter['tag'] = $input->getOption('tag') ? $input->getOption('tag') : '';
    $filter['type'] = $input->getOption('type') ? PwTools::getProperFieldtypeName($input->getOption('type')) : '';

    return (object)$filter;
  }

  /**
   * get templates data
   *
   * @param object $filter
   * @return array
   */
  private function getData($filter) {
    $content = array();
    $count = 0;

    foreach (\ProcessWire\wire('fields') as $field) {
      // no filter, exclude built-in fields except title
      if ($filter->all === false && ($field->flags & Field::flagSystem || $field->flags & Field::flagPermanent)) {
        if ($field->name != 'title') continue;
      }

      // filter unused fields
      if ($filter->unused && $field->getTemplates()->count()) continue;

      // filter by template
      if ($filter->template && !$field->getTemplates()->has($filter->template)) continue;

      // filter by tag
      if ($filter->tag && !$this->fieldHasTag($field->tags, $filter->tag)) continue;

      // filter by field type
      if ($filter->type && $field->type != $filter->type) continue;

      // get row content
      $fieldContent = array(
        $field->name,
        $field->label,
        str_replace('Fieldtype', '', $field->type),
        $field->getTemplates()->count
      );

      // add field by tag
      if (!$field->tags) {
        $tag = 'untagged';

        if (!isset($content[$tag])) $content[$tag] = array();
        $content[$tag][$field->name] = $fieldContent;
      } else {
        $tags = explode(' ', $field->tags);

        foreach ($tags as $tag) {
          if (!$tag) continue;
          $tag = strtolower(ltrim($tag, '-'));
          if (substr($tag, 0, 1) === '-') ltrim($tag, '-');

          if (!isset($content[$tag])) $content[$tag] = array();
          $content[$tag][$field->name] = $fieldContent;
        }
      }

      $count++;
    }

    ksort($content);
    return (object) array('count' => $count, 'content' => $content);
  }

  /**
   * check whether field has specific tag
   *
   * @param string $fieldTags
   * @param string $tagName
   * @return boolean
   */
  protected function fieldHasTag($fieldTags, $tagName) {
    $tags = explode(',', $fieldTags);

    if ($tags && in_array($tagName, $tags)) {
      $hasTag = true;
    }

    return isset($hasTag) ? $hasTag : false;
  }

}
