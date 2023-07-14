<?php namespace Wirecli\Helpers;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Wirecli\Helpers\WsTools as Tools;

/**
 * Class WsTables
 *
 * Contains table methods that could be used in every command
 *
 * @package Wirecli
 * @author Tabea David
 */
class WsTables {

  protected $output;
  protected $tools;

  /**
   * Construct WsTables
   *
   * @param OutputInterface $output
   */
  public function __construct(OutputInterface $output) {
    $this->output = $output;
    $this->tools = new Tools($output);
  }

  /**
   * Build borderless table
   *
   * @param array $content
   * @param array $headers
   */
  public function buildTable($content, $headers) {
    if (!is_array($headers)) $headers = array($headers);
    foreach ($headers as $k => $header) {
      $headers[$k] = $this->tools->writeHeader($header, false);
    }

    $tablePW = new Table($this->output);
    $tablePW
      ->setStyle('borderless')
      ->setHeaders($headers)
      ->setRows($content);

    return $tablePW;
  }

  /**
   * Render Tables
   *
   * @param array $tables
   * @param boolean $nlBefore, default true
   */
  public function renderTables($tables) {
    foreach ($tables as $table) {
      $table->render();
      $this->output->writeln('');
    }
  }

  /**
   * Write Table
   *
   * @param string $text
   */
  public function writeTable($text) {
    $table = new Table($this->output);
    $table->setHeaders(array($text));
    $table->render();
  }
}
