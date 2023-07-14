<?php namespace Wirecli\Helpers;

use Symfony\Component\Console\Helper\FormatterHelper as Formatter;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class WsTools
 *
 * Contains common methods that could be used in every command
 *
 * @package Wirecli
 * @author Camilo Castro
 * @author Tabea David
 */
Class WsTools {

  protected $output;
  protected $formatter;
  protected $helper;
  protected $input;

  protected static $types = array('error', 'success', 'info', 'comment', 'link', 'header', 'mark');

  /**
   * Construct WsTools
   *
   * @param OutputInterface $output
   */
  public function __construct(OutputInterface $output) {
    $this->output = $output;
    $this->formatter = new Formatter();

    $style = new OutputFormatterStyle('cyan', null, array('bold', 'underscore'));
    $output->getFormatter()->setStyle('success', $style);

    $style = new OutputFormatterStyle('magenta');
    $output->getFormatter()->setStyle('info', $style);

    $style = new OutputFormatterStyle('blue');
    $output->getFormatter()->setStyle('comment', $style);

    $style = new OutputFormatterStyle('magenta', null, array('underscore'));
    $output->getFormatter()->setStyle('link', $style);

    $style = new OutputFormatterStyle('cyan', null, array('reverse'));
    $output->getFormatter()->setStyle('header', $style);

    $style = new OutputFormatterStyle('blue', 'white', array('reverse'));
    $output->getFormatter()->setStyle('mark', $style);
  }

  /**
   * Setter for helper
   *
   * @param $helper
   */
  public function setHelper($helper) {
    $this->helper = $helper;
    return $this;
  }

  /**
   * Setter for input
   *
   * @param $input
   */
  public function setInput($input) {
    $this->input = $input;
    return $this;
  }

  /**
   * Simple method for coloring output
   * Possible Types: error, info, comment, success, link
   *
   * @param string $string
   * @param string $type
   * @param boolean $write
   * @return tinted string
   */
  public function write($string, $type = 'info', $write = true) {
    if (in_array($type, self::$types)) $string = "<{$type}>{$string}</{$type}>";
    if ($write) $this->output->writeln($string);

    return $string;
  }

  /**
   * Simple method for coloring link output
   *
   * @param string $string
   * @param boolean $write
   * @return tinted string
   */
  public function writeLink($string, $write = true) {
    return $this->write($string, 'link', $write);
  }

  /**
   * Simple method for coloring mark output
   *
   * @param string $string
   * @param boolean $write
   * @return tinted string
   */
  public function writeMark($string, $write = true) {
    return $this->write($string, 'mark', $write);
  }

  /**
   * Simple method for coloring header output
   *
   * @param string $string
   * @param boolean $write
   * @return tinted string
   */
  public function writeHeader($string, $write = true) {
    return $this->write(' ' . ucfirst($string) . ' ', 'header', $write);
  }

  /**
   * Simple method for coloring success output
   *
   * @param string $string
   * @param boolean $write
   * @return tinted string
   */
  public function writeSuccess($string, $write = true) {
    return $this->write($string, 'success', $write);
  }

  /**
   * Simple method for coloring error output
   *
   * @param string $string
   * @param boolean $write
   * @return tinted string
   */
  public function writeError($string, $write = true) {
    return $this->write(" $string", 'error', $write);
  }

  /**
   * Simple method for coloring error output
   * and exit
   *
   * @param string $string
   * @param boolean $write
   * @return tinted string
   */
  public function writeErrorAndExit($string) {
    $this->writeError($string);
    exit(1);
  }

  /**
   * Simple method for coloring comment output
   *
   * @param string $string
   * @param boolean $write
   * @return tinted string
   */
  public function writeComment($string, $write = true) {
    return $this->write($string, 'comment', $write);
  }

  /**
   * Simple method for coloring info output
   *
   * @param string $string
   * @param boolean $write
   * @return tinted string
   */
  public function writeInfo($string, $write = true) {
    return $this->write($string, 'info', $write);
  }

  /**
   * Get question green text, white brackets/semicolon, yellow default
   *
   * @param string $question
   * @param string $default
   * @param string $sep
   * @return string
   */
  public function getQuestion($question, $default = null, $sep = ':') {
    $que = $this->writeInfo($question, false);
    $def = ' [' . $this->writeComment($default, false) . ']';

    return $default ? "{$que}{$def}{$sep} " : "{$que}{$sep} ";
  }

  /**
   * Write header section
   *
   * @param string $text
   * @param boolean $write
   */
  public function writeBlock($text, $write = true) {
    $out = $this->formatter->formatBlock($text, 'bg=blue;fg=white', true);
    if ($write) $this->output->writeln(array($out, ''));
    return $out;
  }

  /**
   * Write error section
   *
   * @param string $text
   * @param boolean $write
   */
  public function writeBlockError($text, $write = true) {
    $out = $this->formatter->formatBlock($text, 'bg=red;fg=white', true);
    if ($write) $this->output->writeln(array($out, ''));
    return $out;
  }

  /**
   * Write block
   *
   * @param string|array $text
   * @param boolean $write
   */
  public function writeBlockBasic($text, $write = true) {
    $out = array('');
    $this->getOutput($text, $out);
    $out[] = '';

    if ($write) $this->output->writeln($out);
    return $out;
  }

  /**
   * Write header section for comment
   *
   * @param string $text
   * @param boolean $write
   */
  public function writeBlockCommand($text, $write = true) {
    return $this->writeBlock(ucfirst($text), $write);
  }

  /**
   * Write header section
   *
   * @param string $section
   * @param string $text
   * @param boolean $write
   */
  public function writeSection($section, $text, $write = true) {
    $out = $this->formatter->formatSection($section, $text);
    if ($write) $this->output->writeln($out);
    return $out;
  }

  /**
   * Write definition list item
   *
   * @param string $section
   * @param string $text
   * @param boolean $write
   */
  public function writeDfList($section, $text, $write = true) {
    $out = ' - ' . $this->writeSection($section, $text, false);
    if ($write) $this->output->writeln($out);
    return $out;
  }

  /**
   * Output new line / break
   *
   * @param integer $count
   */
  public function nl($count = 1) {
    for ($i = 0; $i < $count; $i++) $this->output->writeln('');
  }

  /**
   * Get Output
   *
   * @param string|array $text
   * @param array $out
   */
  private function getOutput($text, &$out) {
    if (is_array($text)) {
      foreach ($text as $t) {
        $out[] = $this->writeInfo($t, false);
      }
    } else {
      $out[] = $this->writeInfo($text, false);
    }
  }

  /**
   * Returns: `x in set, total: y`
   *
   * @param int $count
   * @param int $total
   * @param boolean $write
   * @return tinted string
   */
  public function writeCount($count, $total = null, $write = true) {
    if (!$total) $total = $count;

    $this->writeInfo("($count in set, total: $total)", $write);
  }

  /**
   * Helper for symfony question helper
   *
   * @param string $item
   * @param string $question
   * @param string $default
   * @param boolean $hidden
   * @param array $autocomplete,
   * @param string $validator
   * @param boolean $doAsk whether to ask if params were provided
   * @return string
   */
  public function ask($item,  $question, $default = null, $hidden = false, $autocomplete = null, $validator = null, $doAsk = false) {
    if (!$item || $doAsk) {
      $question = new Question(
        $this->getQuestion($question, $default),
        $default
      );

      if ($hidden) {
        $question->setHidden(true);
        $question->setHiddenFallback(false);
      }

      if ($autocomplete) {
        $question->setAutocompleterValues($autocomplete);
      }

      if ($validator) {
        switch ($validator) {
        case 'email':
          $question->setValidator(function ($answer) {
            if (!filter_var($answer, FILTER_VALIDATE_EMAIL)) {
              throw new \RuntimeException('Please enter a valid email address.');
            }
            return $answer;
          });
          break;

        case 'required':
          $question->setValidator(function ($answer) {
            if (!$answer) {
              throw new \RuntimeException('Please provide an answer.');
            }
            return $answer;
          });
          break;
        }

        $question->setMaxAttempts(3);
      }

      $item = $this->helper->ask($this->input, $this->output, $question);
      $this->nl();
    }

    if ($item && $validator === 'email' && !filter_var($item, FILTER_VALIDATE_EMAIL)) {
      return $this->ask($item, $question, $default, $hidden, $autocomplete, $validator, true);
    }

    return $item;
  }

  /**
   * Ask choice
   *
   * @param $item
   * @param string $question
   * @param array $options
   * @param string $default
   * @param boolean $isMulti
   */
  public function askChoice($item, $question, $options, $default = '0', $isMulti = false) {
    if (!$item) {
      $def = array();
      foreach (explode(',', $default) as $key) $def[] = $options[$key];

      $question = new ChoiceQuestion(
        $this->getQuestion($question, implode(', ', $def)),
        $options,
        $default
      );

      if ($isMulti) $question->setMultiselect(true);

      return $this->helper->ask($this->input, $this->output, $question);
    }

    return $item;
  }

  /**
   * Ask confirmation question
   *
   * @param $item
   * @param string $question
   * @param string $default
   */
  public function askConfirmation($item, $question, $default = 'n') {
    if (!$item) {
      $question = new ConfirmationQuestion(
        $this->getQuestion($question, $default),
        false // value to return if the user doesn't enter any valid input
      );

      return $this->helper->ask($this->input, $this->output, $question);
    }

    return $item;
  }

  /**
   * Generate random password with given length
   *
   * @param integer $length
   * @return string
   */
  public function generatePassword($length = 12) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
    return substr(str_shuffle($chars), 0, $length);
  }
}
