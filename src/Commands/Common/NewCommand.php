<?php namespace Wirecli\Commands\Common;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Wirecli\Helpers\Downloader;
use Wirecli\Helpers\Installer;
use Wirecli\Helpers\PwConnector;
use Wirecli\Helpers\WsTools as Tools;

/**
 * Class NewCommand
 *
 * Downloads ProcessWire in current or in specified folder
 * Methods and approach based on T. Otwell's Laravel installer script: https://github.com/laravel/installer
 * Methods based on P. Urlich's ProcessWire online Installer script: https://github.com/somatonic/PWOnlineInstaller
 *
 * @package Wirecli
 * @author Taylor Otwell
 * @author Fabien Potencier
 * @author Philipp Urlich
 * @author Marcus Herrmann
 * @author Hari KT
 * @author Tabea David
 *
 */
class NewCommand extends PWConnector {

  /**
   * @var Filesystem
   */
  private $fs;
  private $projectName;
  private $projectDir;
  private $version;
  private $helper;
  private $compressedFilePath;
  private $requirementsErrors = array();
  private $src;
  private $installer;
  private $verbose;
  protected $downloader;
  protected $tools;

  /**
   * @field array default config values
   */
  protected $defaults = array(
    'dbName' => '',
    'dbUser' => '',
    'dbPass' => '',
    'dbHost' => 'localhost',
    'dbPort' => '3306',
    'dbEngine' => 'MyISAM',
    'dbCharset' => 'utf8',
    'timezone' => '',
    'chmodDir' => '755',
    'chmodFile' => '644',
    'httpHosts' => '',
    'adminUrl' => 'processwire',
    'username' => '',
    'userpass' => '',
    'userpass_confirm' => '',
    'useremail' => '',
    'color' => 'classic'
  );

  /**
   * @var OutputInterface
   */
  protected $output;

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this
      ->setName('new')
      ->setDescription('Creates a new ProcessWire project')
      ->addArgument('directory', InputArgument::OPTIONAL, 'Directory where the new project will be created')
      ->addOption('force', null, InputOption::VALUE_NONE, 'Force installation in an non empty directory')
      ->addOption('dbUser', null, InputOption::VALUE_REQUIRED, 'Database user')
      ->addOption('dbPass', null, InputOption::VALUE_OPTIONAL, 'Database password')
      ->addOption('dbName', null, InputOption::VALUE_REQUIRED, 'Database name')
      ->addOption('dbHost', null, InputOption::VALUE_REQUIRED, 'Database host, default: `localhost`')
      ->addOption('dbPort', null, InputOption::VALUE_REQUIRED, 'Database port, default: `3306`')
      ->addOption('dbEngine', null, InputOption::VALUE_REQUIRED, 'Database engine, default: `MyISAM`')
      ->addOption('dbCharset', null, InputOption::VALUE_REQUIRED, 'Database characterset, default: `utf8`')
      ->addOption('timezone', null, InputOption::VALUE_REQUIRED, 'Timezone')
      ->addOption('chmodDir', null, InputOption::VALUE_REQUIRED, 'Directory mode, default `755`')
      ->addOption('chmodFile', null, InputOption::VALUE_REQUIRED, 'File mode, defaults `644`')
      ->addOption('httpHosts', null, InputOption::VALUE_REQUIRED, 'Hostname without `www` part')
      ->addOption('adminUrl', null, InputOption::VALUE_REQUIRED, 'Admin url')
      ->addOption('username', null, InputOption::VALUE_REQUIRED, 'Admin username')
      ->addOption('userpass', null, InputOption::VALUE_REQUIRED, 'Admin password')
      ->addOption('useremail', null, InputOption::VALUE_OPTIONAL, 'Admin email address')
      ->addOption('profile', null, InputOption::VALUE_REQUIRED, 'Default site profile: `path/to/profile.zip` OR one of `beginner, blank, classic, default, languages`')
      ->addOption('src', null, InputOption::VALUE_REQUIRED, 'Path to pre-downloaded folder, zip or tgz: `path/to/src`')
      ->addOption('sha', null, InputOption::VALUE_REQUIRED, 'Download specific commit')
      ->addOption('dev', null, InputOption::VALUE_NONE, 'Download latest develeopment version')
      ->addOption('no-install', null, InputOption::VALUE_NONE, 'Disable installation')
      ->addOption('v', null, InputOption::VALUE_NONE, 'verbose');
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->init($input, $output, false);
    $this->fs = new Filesystem();
    $this->projectDir = $this->getDirectory();
    $this->projectName = basename($this->projectDir);
    $this->src = $input->getOption('src') ? $this->getAbsolutePath($input->getOption('src')) : null;

    $srcStatus = $this->checkExtractedSrc();
    $this->verbose = $input->getOption('v') ? true : false;

    $profile = $input->getOption('profile');
    $branch = $this->getZipURL();
    $logger = new Logger('name');
    $logger->pushHandler(new StreamHandler("php://output"));
    $this->installer = new Installer($logger, $this->projectDir, $this->verbose);
    $this->tools = new Tools($output);
    $this->version = $this->getVersion();
    $this->helper = $this->getHelper('question');

    try {
      $this->tools->writeBlock('Welcome to the wire-cli ProcessWire generator');

      if (!$this->checkAlreadyDownloaded() && $srcStatus !== 'extracted') {
        $this->downloader = new Downloader($this->output, $this->projectDir, $this->version);

        if (!$srcStatus) {
          $this->checkProjectName();
          $this->download($branch);
        }
        $this->extract();
      }

      $this->cleanUp();
    } catch (Exception $e) {
    }

    try {
      if (!$input->getOption('no-install')) {
        $profile = $this->extractProfile($profile);
        $this->installer->getSiteFolder($profile);
        $this->checkProcessWireRequirements();

        // use defaults it not set
        $doNotAsk = array('dbHost', 'dbPort', 'dbEngine', 'dbCharset', 'chmodDir', 'chmodFile', 'adminUrl');
        foreach ($doNotAsk as $item) if ($input->getOption($item)) $this->defaults[$item] = $input->getOption($item);

        // ask
        $this->defaults['dbName'] = $this->ask('dbName', 'Please enter the database name', 'pw');
        $this->askDbInformations();

        $index = 0;
        while (is_null($this->installer->checkDatabaseConnection($this->defaults, false))) {
          $this->tools->writeError("Database connection information did not work, please try again.");
          if ($index >= 3) throw new \RuntimeException('Database connection information did not work, please make sure that mysql is running.');
          $this->askDbInformations(true);
          $index++;
        }

        $this->defaults['timezone'] = $this->ask('timezone', 'Please enter the timezone', 'Europe/Berlin', false, timezone_identifiers_list());
        $httpHosts = $this->ask('httpHosts', 'Please enter the hostname without `www.`', 'pw.local');
        $this->defaults['httpHosts'] = $httpHosts . "\n" . "www." . $httpHosts;
        $this->defaults['username'] = $this->ask('username', 'Please enter admin user name', 'admin');
        $this->defaults['userpass'] = $this->ask('userpass', 'Please enter admin password', 'password', true);
        $this->defaults['userpass_confirm'] = $this->defaults['userpass'];
        $this->defaults['useremail'] = $this->ask('useremail', 'Please enter admin email address', null, false, null, 'email');

        // ... install!
        $this->installer->dbSaveConfig($this->defaults);
        $this->cleanUpInstallation();
        $this->tools->writeSuccess('ᕙ(✧‿✧)ᕗ Congratulations, ProcessWire has been successfully installed.');

      }
    } catch (\Exception $e) {
      $this->cleanUp();
      throw $e;
    }

    return static::SUCCESS;
  }

  /**
   * Ask database informations
   *
   * @param boolean $doAsk whether to ask if params were provided
   */
  private function askDbInformations($doAsk = false) {
    $this->defaults['dbUser'] = $this->ask('dbUser', 'Please enter the database user name', 'root', null, null, null, $doAsk);

    // allow empty passwords `--dbPass=""`
    if (!$doAsk && $this->input->hasParameterOption('--dbPass') && !is_null($this->input->getParameterOption('--dbPass'))) {
      $this->defaults['dbPass'] = $this->input->getParameterOption('--dbPass');
    } else {
      $this->defaults['dbPass'] = $this->ask('dbPass', 'Please enter the database password', null, true, null, null, $doAsk);
    }
  }

  /**
   * Helper for symfony question helper
   *
   * @param string $name
   * @param string $question
   * @param string $default
   * @param boolean $hidden
   * @param array $autocomplete,
   * @param string $validator
   * @param boolean $doAsk whether to ask if params were provided
   * @return string
   */
  private function ask($name,  $question, $default = null, $hidden = false, $autocomplete = null, $validator = null, $doAsk = false) {
    $item = $this->input->getOption($name);
    if (!$item || $doAsk) {
      $question = new Question($this->tools->getQuestion($question, $default), $default);

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
            if ($answer && !filter_var($answer, FILTER_VALIDATE_EMAIL)) {
              throw new \RuntimeException('Please enter a valid email address.');
            }
            return $answer;
          });
          break;
        }

        $question->setMaxAttempts(3);
      }

      $item = $this->helper->ask($this->input, $this->output, $question);
      $this->tools->nl();
    }

    if ($item && $validator === 'email' && !filter_var($item, FILTER_VALIDATE_EMAIL)) {
      return $this->ask($name, $question, $default, $hidden, $autocomplete, $validator, true);
    }

    return $item;
  }

  /**
   * Get absolute path
   *
   * @param $path
   * @return string
   */
  private function getAbsolutePath($path) {
    return $this->fs->isAbsolutePath($path) ? $path : getcwd() . DIRECTORY_SEPARATOR . $path;
  }

  /**
   * Check extracted source
   *
   * @return string
   */
  private function checkExtractedSrc() {
    $status = null;

    if ($this->src && $this->fs->exists($this->src)) {
      switch(filetype($this->src)) {
      case 'dir':
        // copy extracted src files to projectDir
        $this->fs->mirror($this->src, $this->projectDir);
        $status = 'extracted';
        break;
      case 'file':
        // check for zip or tgz filetype
        if (in_array(pathinfo($this->src)['extension'], array('zip', 'tgz'))) {
          $status = 'compressed';
        }
        break;
      }
    }

    return $status;
  }

  /**
   * Get directory
   *
   * @return string
   */
  private function getDirectory() {
    $directory = getcwd();

    if ($d = $this->input->getArgument('directory')) {
      $directory = rtrim(trim($d), DIRECTORY_SEPARATOR);
    } else {
      if (!$directory) {
        $this->tools->writeError('No such file or directory, you may have to refresh the current directory by executing for example `cd \$PWD`.');
        return;
      }
      chdir(dirname($directory));
    }

    return $this->getAbsolutePath($directory);
  }

  /**
   * Get zip URL
   *
   * @return string
   */
  private function getZipURL() {
    $targetBranch = $this->determineBranch();
    $branch = str_replace('{branch}', $targetBranch, self::zipURL);
    $check = str_replace('{branch}', $targetBranch, self::versionURL);

    try {
      $ch = curl_init($check);
    } catch (Exception $e) {
      $messages = array(
        'Curl request failed.',
        'Please check whether the php curl extension is enabled, uncomment the following line in your php.ini:',
        '`;extension=php_curl.dll` and restart the server. Check your phpinfo() to see whether curl has been properly enabled or not.'
      );
      throw new \RuntimeException(implode("\n", $messages));
    }

    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $retcode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = (string)curl_error($ch);
    curl_close($ch);

    if ((int)$retcode !== 200) {
      throw new \RuntimeException(
        "Error loading sha `$targetBranch`, curl request failed (status code: $retcode, url: $check).\ncURL error: $curlError"
      );
    }

    return $branch;
  }

  /**
   * Checks whether it's safe to create a new project for the given name in the
   * given directory.
   *
   * @return NewCommand
   *
   * @throws \RuntimeException if a project with the same does already exist
   */
  private function checkProjectName() {
    if (
      is_dir($this->projectDir) && 
      !$this->isEmptyDirectory($this->projectDir) && 
      $this->fs->exists($this->projectDir . '/site/config.php')) {
      throw new \RuntimeException(sprintf(
        "There is already a '%s' project in this directory (%s).\n" .
        "Change your project name or create it in another directory.",
        $this->projectName, $this->projectDir
      ));
    }

    return $this;
  }

  /**
   * Check already downloaded
   *
   * @return boolean
   */
  private function checkAlreadyDownloaded() {
    return file_exists($this->projectDir . '/site/install') ? true : false;
  }

  /**
   * Chooses the best compressed file format to download (ZIP or TGZ) depending upon the
   * available operating system uncompressing commands and the enabled PHP extensions
   * and it downloads the file.
   *
   * @return NewCommand
   *
   * @throws \RuntimeException if the ProcessWire archive could not be downloaded
   */
  private function download($branch) {
    $this->tools->writeInfo("\nDownloading ProcessWire...");
    $this->compressedFilePath = $this->downloader->download($branch);
    $this->tools->nl();

    return $this;
  }

  /**
   * Extracts the compressed Symfony file (ZIP or TGZ) using the
   * native operating system commands if available or PHP code otherwise.
   *
   * @return NewCommand
   *
   * @throws \RuntimeException if the downloaded archive could not be extracted
   */
  private function extract() {
    $forceInstall = $this->input->getOption('force');
    $this->tools->writeBlockBasic('Preparing project...');
    $cfp = $this->src ? $this->src : $this->compressedFilePath;
    $this->downloader->extract($cfp, $this->projectDir, $this->getName(), $forceInstall);

    return $this;
  }

  /**
   * Removes all the temporary files and directories created to
   * download the project and removes ProcessWire-related files that don't make
   * sense in a proprietary project.
   *
   * @return NewCommand
   */
  private function cleanUp() {
    if ($this->compressedFilePath)
      $this->fs->remove(dirname($this->compressedFilePath));

    try {
      $licenseFile = array($this->projectDir . '/LICENSE');
      $upgradeFiles = glob($this->projectDir . '/UPGRADE*.md');
      $changelogFiles = glob($this->projectDir . '/CHANGELOG*.md');

      $filesToRemove = array_merge($licenseFile, $upgradeFiles, $changelogFiles);
      $this->fs->remove($filesToRemove);

      $readmeContents = sprintf("%s\n%s\n\nA ProcessWire project created on %s.\n", $this->projectName,
        str_repeat('=', strlen($this->projectName)), date('F j, Y, g:i a'));
      $this->fs->dumpFile($this->projectDir . '/README.md', $readmeContents);
    } catch (\Exception $e) {
      // don't throw an exception in case any of the ProcessWire-related files cannot
      // be removed, because this is just an enhancement, not something mandatory
      // for the project
    }

    return $this;
  }

  /**
   * Removes all the temporary files and directories created to
   * install the project and removes ProcessWire-related files that don't make
   * sense in a running project.
   *
   * @return NewCommand
   */
  private function cleanUpInstallation() {
    $this->fs->remove(dirname($this->compressedFilePath));

    try {
      $siteDirs = glob($this->projectDir . '/site-*');
      $installDir = array($this->projectDir . '/site/install');
      $installFile = array($this->projectDir . '/install.php');

      $this->fs->remove(array_merge($siteDirs, $installDir, $installFile));
      if ($this->verbose) $this->tools->writeInfo('Remove ProcessWire-related files that don\'t make sense in a running project.');
    } catch (\Exception $e) {
      // don't throw an exception in case any of the ProcessWire-related files cannot
      // be removed, because this is just an enhancement, not something mandatory
      // for the project
    }

    return $this;
  }

  /**
   * Checks if environment meets ProcessWire requirements
   *
   * @return OneclickCommand
   */
  private function checkProcessWireRequirements() {
    $this->installer->compatibilityCheck();

    return $this;
  }

  /**
   * Utility method to show the number of bytes in a readable format.
   *
   * @param int $bytes The number of bytes to format
   *
   * @return string The human readable string of bytes (e.g. 4.32MB)
   */
  private function formatSize($bytes) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = $bytes ? floor(log($bytes, 1024)) : 0;
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);

    return number_format($bytes, 2) . ' ' . $units[$pow];
  }

  /**
   * Formats the error message contained in the given Requirement item
   * using the optional line length provided.
   *
   * @param \Requirement $requirement The ProcessWire requirements
   * @param int $lineSize The maximum line length
   *
   * @return string
   */
  private function getErrorMessage(\Requirement $requirement, $lineSize = 70) {
    if ($requirement->isFulfilled()) return;
    $errorMessage = wordwrap($requirement->getTestMessage(), $lineSize - 3, PHP_EOL . '   ') . PHP_EOL;
    $errorMessage .= '   > ' . wordwrap($requirement->getHelpText(), $lineSize - 5, PHP_EOL . '   > ') . PHP_EOL;

    return $errorMessage;
  }

  /**
   * Returns the executed command.
   *
   * @return string
   */
  private function getExecutedCommand() {
    $version = '';
    if ('latest' !== $this->version) $version = $this->version;

    $pathDirs = explode(PATH_SEPARATOR, $_SERVER['PATH']);
    $executedCommand = $_SERVER['PHP_SELF'];
    $executedCommandDir = dirname($executedCommand);

    if (in_array($executedCommandDir, $pathDirs)) $executedCommand = basename($executedCommand);

    return sprintf('%s new %s %s', $executedCommand, $this->projectName, $version);
  }

  /**
   * Checks whether the given directory is empty or not.
   *
   * @param  string $dir the path of the directory to check
   * @return bool
   */
  private function isEmptyDirectory($dir) {
    // glob() cannot be used because it doesn't take into account hidden files
    // scandir() returns '.'  and '..'  for an empty dir
    return 2 === count(scandir($dir . '/'));
  }

  /**
   * Extract profile
   *
   * @param  string $profile
   * @return string
   */
  private function extractProfile($profile) {
    if (!$profile || !preg_match('/^.*\.zip$/', $profile)) return $profile;

    $this->tools->writeInfo('Extracting profile...');

    try {
      $extractPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid(time()) . DIRECTORY_SEPARATOR;
      // TODO: use Symfony ZipArchive class
      //$extractionSucceeded = $distill->extractWithoutRootDirectory($profile, $extractPath);

      foreach (new \DirectoryIterator($extractPath) as $fileInfo) {
        if ($fileInfo->isDir() && !$fileInfo->isDot()) {
          $dir = $fileInfo->getFilename();
          break;
        }
      }

      if ($extractionSucceeded) {
        try {
          $this->fs->mirror($extractPath, $this->projectDir . '/');
        } catch (\Exception $e) {
        }
        // cleanup
        $this->fs->remove($extractPath);

        try {
          $process = new Process("cd $this->projectDir;");
          $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
              echo ' ' . $buffer;
            } else {
              echo ' ' . $buffer;
            }
          });
        } catch (\Exception $e) {
        }
      }
    } catch (FileCorruptedException $e) {
      throw new \RuntimeException(
        "The profile can't be installed because the downloaded package is corrupted.\n"
      );
    } catch (FileEmptyException $e) {
      throw new \RuntimeException(
        "The profile can't be installed because the downloaded package is empty.\n"
      );
    } catch (TargetDirectoryNotWritableException $e) {
      throw new \RuntimeException(
        "The profile can't be installed because the installer doesn't have enough\n" .
        "permissions to uncompress and rename the package contents.\n"
      );
    } catch (\Exception $e) {
      throw new \RuntimeException(
        "The profile can't be installed because the downloaded package is corrupted\n" .
        "or because the installer doesn't have enough permissions to uncompress and\n" .
        "rename the package contents.\n" .
        $e->getMessage()
      );
    }

    if (!$extractionSucceeded) {
      throw new \RuntimeException(
        "The profile can't be installed because the downloaded package is corrupted\n" .
        "or because the uncompress commands of your operating system didn't work."
      );
    }

    return $dir;
  }
}
