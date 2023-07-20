<?php namespace Wirecli\Commands\Common;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;

use Wirecli\Helpers\PwConnector;
use Wirecli\Helpers\WsTools as Tools;

/**
 * Class ServeCommand
 *
 * Example command for passthru()
 *
 * @package Wirecli
 * @link http://php.net/manual/en/function.passthru.php
 * @author Marcus Herrmann
 */
class ServeCommand extends PwConnector {

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this
      ->setName('serve')
      ->setDescription('Serve ProcessWire via built in PHP webserver')
      ->addOption('scheme', null, InputOption::VALUE_OPTIONAL, 'Scheme to serve on', 'http')
      ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Host to serve on', 'localhost')
      ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'Port to serve on', '8080');
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->checkForProcessWire($output);
    
    $scheme = $input->getOption('scheme');
    $host = $input->getOption('host');
    $port = $input->getOption('port');
    $url = "$scheme://$host:$port";
    $this->tools->writeComment("Starting PHP server at $url ...");
    $this->helper = $this->getHelper('question');

    $port = (int)$port;
    $pf = @fsockopen($host, $port); // Try to open a socket
    if (is_resource($pf)) {
      fclose($pf); // Close the previous socket
      do {
        $this->tools->writeError("Port $port is already in use.");
        $this->tools->nl();

        $port++; // Increment the port number and try again
                
        $question = new Question($this->tools->getQuestion("Trying another one", (int)$port), $port);
        $question->setValidator(function ($answer) {
          if ($answer && !filter_var($answer, FILTER_VALIDATE_INT)) {
            throw new \RuntimeException('Please enter a valid port number.');
          }
          return $answer;
        });

        $newPort = $this->helper->ask($input, $output, $question); // Ask for a new port number
        if (is_resource($pf)) { // If the previous socket is still open, close it
          fclose($pf);
        }
        $pf = @fsockopen($host, (int)$newPort);
      }
      while($pf !== false);
      
      if (is_resource($pf)) { // If the previous socket is still open, close it
        fclose($pf);
      }
      $port = $newPort;
    }

    $url = "$scheme://$host:$port";
    $this->tools->nl(); 
    $this->tools->writeSuccess("PHP server started, serving at $url. Press CTRL+C to stop the server.");
    $this->tools->nl();

    if (passthru("php -S $host:$port") !== null) {
      $this->tools->writeError("Failed to start PHP server.");
      return static::FAILURE;
    }

    return static::SUCCESS;
  }
}