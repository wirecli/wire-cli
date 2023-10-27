<?php namespace Wirecli\Helpers;

use Symfony\Component\Filesystem\Filesystem;
use Psr\Log\LoggerInterface;
use PDO;
use Exception;
use \ProcessWire\WireDatabaseBackup;
use DirectoryIterator;

/**
 * ProcessWire Installer
 *
 * Because this installer runs before PW3 is installed, it is largely self contained.
 * It's a quick-n-simple single purpose script that's designed to run once, and it should be deleted after installation.
 * This file self-executes using code found at the bottom of the file, under the Installer class.
 *
 * Note that it creates this file once installation is completed: /site/assets/installed.php
 * If that file exists, the installer will not run. So if you need to re-run this installer for any
 * reason, then you'll want to delete that file. This was implemented just in case someone doesn't delete the installer.
 *
 * ProcessWire 3.x
 * Copyright (C) 2014 by Ryan Cramer
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * https://processwire.com
 *
 * Class Installer
 *
 * @package Wirecli
 * @author Tabea David <info@justonestep.de>
 */
class Installer {

  protected $fs = null;

  /**
   * Whether or not we force installed files to be copied.
   *
   * If false, we attempt a faster rename of directories instead.
   *
   */
  const FORCE_COPY = true;

  /**
   * Replace existing database tables if already present?
   *
   */
  const REPLACE_DB = true;

  /**
   * Minimum required PHP version to install ProcessWire
   *
   */
  const MIN_REQUIRED_PHP_VERSION = '5.3.8';

  /**
   * Test mode for installer development, non destructive
   *
   */
  const TEST_MODE = false;

  /**
   * Default site profile
   *
   */
  const PROFILE_DEFAULT = 'site-blank';

  /**
   * File permissions, determined in the dbConfig function
   *
   * Below are last resort defaults
   *
   */
  protected $chmodDir = "0777";
  protected $chmodFile = "0666";

  /**
   * Number of errors that occurred during the request
   *
   */
  protected $numErrors = 0;

  /**
   * @param LoggerInterface $log
   */
  protected $log;

  protected $projectDir;

  /**
   * Construct Installer
   *
   * @param LoggerInterface $log
   * @param string $projectDir
   * @param boolean $v
   */
  public function __construct(LoggerInterface $log, $projectDir, $v = true) {
    $this->log = $log;
    $this->projectDir = $projectDir;
    $this->v = $v;
  }

  /**
   * Check if the given function $name exists and report OK or fail with $label
   *
   * @param string $name
   * @param string $label
   */
  protected function checkFunction($name, $label) {
    if (function_exists($name)) {
      if ($this->v) $this->log->info("$label");
    } else {
      $this->error("Fail: $label");
    }
  }

  /**
   * Step 0: Get `site` folder
   *
   * @param string $profile
   */
  public function getSiteFolder($profile) {
    $projectDir = $this->projectDir;
    $site = self::PROFILE_DEFAULT;
    $availableProfiles = array('beginner', 'blank', 'classic', 'default', 'languages');

    if ($profile) {
      $site = in_array($profile, $availableProfiles) ? 'site-' . $profile : $profile;
      if (!is_dir("$projectDir/$site")) $site = self::PROFILE_DEFAULT;
    }

    if (is_file("$projectDir/site/install/install.sql")) {
      if ($this->v) $this->log->info('Found installation profile in `/site/install/`');
    } else if (is_dir("$projectDir/site/")) {
      if ($this->v) $this->log->info('Found `/site/` -- already installed?');
    } else if (@rename("$projectDir/$site", "$projectDir/site")) {
      if ($this->v) $this->log->info("Renamed /$site => /site");
    } else {
      $this->error('Before continuing, rename `/site-default` to `/site` (this is the default installation profile).');
      if ($this->v) {
        $message = 'If you prefer, you may download an alternate installation profile ';
        $message .= 'at processwire.com/download, which you should unzip to `/site`.';
        $this->log->info($message);
      }
      return;
    }

    return $this;
  }

  /**
   * Step 1: Check for ProcessWire compatibility
   *
   */
  public function compatibilityCheck() {
    $projectDir = $this->projectDir;

    if (version_compare(PHP_VERSION, self::MIN_REQUIRED_PHP_VERSION) >= 0) {
      if ($this->v) $this->log->info('PHP version ' . PHP_VERSION);
    } else {
      $message = 'ProcessWire requires PHP version ' . self::MIN_REQUIRED_PHP_VERSION;
      $message .= ' or newer. You are running PHP ' . PHP_VERSION;
      $this->error($message);
    }

    if (extension_loaded('pdo_mysql')) {
      if ($this->v) $this->log->info('PDO (mysql) database');
    } else {
      $this->error('PDO (pdo_mysql) is required (for MySQL database)');
    }

    if (self::TEST_MODE) $this->error('Example error message for test mode');

    $this->checkFunction('filter_var', 'Filter functions (filter_var)');
    $this->checkFunction('mysqli_connect', 'MySQLi (not required by core, but may be required by some 3rd party modules)');
    $this->checkFunction('imagecreatetruecolor', 'GD 2.0 or newer');
    $this->checkFunction('json_encode', 'JSON support');
    $this->checkFunction('preg_match', 'PCRE support');
    $this->checkFunction('ctype_digit', 'CTYPE support');
    $this->checkFunction('iconv', 'ICONV support');
    $this->checkFunction('session_save_path', 'SESSION support');
    $this->checkFunction('hash', 'HASH support');
    $this->checkFunction('spl_autoload_register', 'SPL support');

    if (function_exists('apache_get_modules')) {
      if (in_array('mod_rewrite', apache_get_modules())) {
        if ($this->v) $this->log->info('Found Apache module: mod_rewrite');
      } else {
        $this->info('Apache mod_rewrite does not appear to be installed and is required by ProcessWire.');
      }
    } else {
      // apache_get_modules doesn't work on a cgi installation.
      // check for environment var set in htaccess file, as submitted by jmarjie.
      $mod_rewrite = getenv('HTTP_MOD_REWRITE') == 'On' || getenv('REDIRECT_HTTP_MOD_REWRITE') == 'On' ? true : false;
      if ($mod_rewrite && $this->v) $this->log->info("Found Apache module (cgi): mod_rewrite");
    }

    if (is_writable("$projectDir/site/assets/")) {
      if ($this->v) $this->log->info("$projectDir/site/assets/ is writable");
    } else {
      $this->error("Error: Directory {$projectDir}/site/assets/ must be writable. Please adjust the permissions before continuing.");
    }

    if (is_writable("$projectDir/site/config.php")) {
      if ($this->v) $this->log->info("$projectDir/site/config.php is writable");
    } else {
      $this->error("Error: File `{$projectDir}/site/config.php` must be writable. Please adjust the permissions before continuing.");
    }

    if (!is_file("$projectDir/.htaccess") || !is_readable("$projectDir/.htaccess")) {
      if (@rename("$projectDir/htaccess.txt", "$projectDir/.htaccess")) {
        if ($this->v) $this->log->info('Installed `.htaccess`');
      } else {
        $message = '`/.htaccess` doesn\'t exist. Before continuing, you should rename the included `htaccess.txt` file to be `.htaccess`';
        $message .= '(with the period in front of it, and no `.txt` at the end).';
        $this->error($message);
      }
    } else if (!strpos(file_get_contents("$projectDir/.htaccess"), 'PROCESSWIRE')) {
      $message = '`/.htaccess` file exists, but is not for ProcessWire. Please overwrite or combine it with the provided `/htaccess.txt` file ';
      $message .= '(i.e. rename `/htaccess.txt` to /.htaccess`, with the period in front).';
      $this->error($message);
    } else {
      if ($this->v) $this->log->info('`.htaccess` looks good');
    }

    if ($this->numErrors) {
      $message = 'One or more errors were found above. We recommend you correct these issues before proceeding or contact ProcessWire support ';
      $message .= 'if you have questions or think the error is incorrect. But if you want to proceed anyway, click Continue below.';
      $this->log->error($message);
    }
  }

  /**
   * Step 3: Save database configuration, then begin profile import
   *
   * @param array $post
   */
  public function dbSaveConfig($post) {
    $fields = array('dbUser', 'dbName', 'dbPass', 'dbHost', 'dbPort');
    $values = array();

    foreach ($fields as $field) {
      $value = $post[$field];
      $value = substr($value, 0, 128);
      $values[$field] = $value;
    }

    if (!$values['dbUser'] || !$values['dbName'] || !$values['dbPort']) {
      $this->error('Missing database configuration fields');
      return ;
    }

    error_reporting(1);

    $database = $this->checkDatabaseConnection($values);
    if (is_null($database)) return;

    // file permissions
    $fields = array('chmodDir', 'chmodFile');
    foreach ($fields as $field) {
      $value = (int) $post[$field];
      if (strlen("$value") !== 3) $this->error("Value for '$field' is invalid");
      else $this->$field = "0$value";
      $values[$field] = $value;
    }

    $timezones = $this->timezones();
    if (in_array($post['timezone'], timezone_identifiers_list())) {
      $values['timezone'] = $post['timezone'];
    } else if ($timezones[(int)$post['timezone']]) {
      $value = $timezones[$timezone];
      if (strpos($value, '|')) list($label, $value) = explode('|', $value);
      $values['timezone'] = $value;
    } else {
      $values['timezone'] = 'America/New_York';
    }

    $values['httpHosts'] = array();
    $httpHosts = trim($post['httpHosts']);
    if (strlen($httpHosts)) {
      $httpHosts = str_replace(array("'", '"'), '', $httpHosts);
      $httpHosts = explode("\n", $httpHosts);
      foreach ($httpHosts as $key => $host) {
        $httpHosts[$key] = strtolower(trim(filter_var($host, FILTER_SANITIZE_URL)));
      }
      $values['httpHosts'] = $httpHosts;
    }

    if ($this->numErrors) {
      $this->error('Please fix the error to further proceed');
      return;
    }

    if ($this->v) $this->log->info('Database connection successful to ' . htmlspecialchars($values['dbName']));

    if ($this->dbSaveConfigFile($values)) {
      $this->profileImport($database);
      $this->adminAccountSave($post);
    } else {
      $this->error('Error saving config file.');
    }
  }

  /**
   * Check database connection
   *
   * @param array $values
   * @param boolean $out
   */
  public function checkDatabaseConnection($values, $out = true) {
    $pdo = null;
    $dbname = "`" . str_replace("`", "``", $values['dbName']) . "`";
    $dsnArr = array(
      'host' => $values['dbHost'],
      'port' => $values['dbPort']
    );

    $dsn = array_reduce(array_keys($dsnArr), function($carry, $key) use ($dsnArr) { 
      $carry .= "{$key}={$dsnArr[$key]};"; 
      return $carry; 
    });

    $driverOptions = array(
      PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8MB4'",
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    );

    try {
      $pdo = new PDO("mysql:$dsn", $values['dbUser'], $values['dbPass'], $driverOptions);
      $pdo->query("CREATE DATABASE IF NOT EXISTS $dbname");
      $pdo->query("use $dbname");
      $success = true;
    } catch(Exception $e) {
      if ($out) {
        $this->error("Database connection information did not work.");
        $this->error($e->getMessage());
      }
    }

    return $pdo;
  }

  /**
   * Save configuration to /site/config.php
   *
   * @param array $values
   */
  protected function dbSaveConfigFile(array $values) {
    $salt = md5(mt_rand() . microtime(true));

    $cfg =  "\n/**" .
      "\n * Installer: Database Configuration" .
      "\n * " .
      "\n */" .
      "\n\$config->dbHost = '$values[dbHost]';" .
      "\n\$config->dbName = '$values[dbName]';" .
      "\n\$config->dbUser = '$values[dbUser]';" .
      "\n\$config->dbPass = '$values[dbPass]';" .
      "\n\$config->dbPort = '$values[dbPort]';" .
      "\n" .
      "\n/**" .
      "\n * Installer: User Authentication Salt " .
      "\n * " .
      "\n * Must be retained if you migrate your site from one server to another" .
      "\n * " .
      "\n */" .
      "\n\$config->userAuthSalt = '$salt'; " .
      "\n" .
      "\n/**" .
      "\n * Installer: File Permission Configuration" .
      "\n * " .
      "\n */" .
      "\n\$config->chmodDir = '0$values[chmodDir]'; // permission for directories created by ProcessWire" .
      "\n\$config->chmodFile = '0$values[chmodFile]'; // permission for files created by ProcessWire " .
      "\n" .
      "\n/**" .
      "\n * Installer: Time zone setting" .
      "\n * " .
      "\n */" .
      "\n\$config->timezone = '$values[timezone]';" .
      "\n" .
      "\n/**" .
      "\n * * Installer: Admin theme" .
      "\n *" . 
      "\n */".
      "\n\$config->defaultAdminTheme = 'AdminThemeUikit';" .
      "\n\n";

    if (!empty($values['httpHosts'])) {
      $cfg .= "" .
        "\n/**" .
        "\n * Installer: HTTP Hosts Whitelist" .
        "\n * " .
        "\n */" .
        "\n\$config->httpHosts = array(";
      foreach ($values['httpHosts'] as $host) $cfg .= "'$host', ";
      $cfg = rtrim($cfg, ", ") . ");\n\n";
    }

    $fp = fopen($this->projectDir . "/site/config.php", "a");
    if (!$fp) {
      $this->error("Error saving configuration to {$this->projectDir}/site/config.php. Please make sure it is writable.");
      return false;
    } else {
      fwrite($fp, $cfg);
      fclose($fp);
      if ($this->v) $this->log->info("Saved configuration to ./site/config.php");
      return true;
    }
  }

  /**
   * Step 3b: Import profile
   *
   * @param Database $database
   */
  protected function profileImport($database) {
    $profile = $this->projectDir . '/site/install/';
    if (!is_file("{$profile}install.sql")) die("No installation profile found in {$profile}");

    // checks to see if the database exists using an arbitrary query (could just as easily be something else)
    try {
      $query = $database->prepare('SHOW COLUMNS FROM pages');
      $result = $query->execute();
    } catch(Exception $e) {
      $result = false;
    }

    if (self::REPLACE_DB || !$result || $query->rowCount() == 0) {
      $this->profileImportSQL($database, $this->projectDir . '/wire/core/install.sql', $profile . 'install.sql');

      if (is_dir($profile . 'files')) {
        $this->profileImportFiles($profile);
      } else {
        $this->mkdir($this->projectDir . '/site/assets/files/');
      }

      $this->mkdir($this->projectDir . '/site/assets/cache/');
      $this->mkdir($this->projectDir . '/site/assets/logs/');
      $this->mkdir($this->projectDir . '/site/assets/sessions/');
    } else {
      if ($this->v) $this->log->info('A profile is already imported, skipping...');
    }

    // copy default site modules /site-default/modules/ to /site/modules/
    $dir = $this->projectDir . '/site/modules/';
    $defaultDir = $this->projectDir . '/site-default/modules/';
    if (!is_dir($dir)) $this->mkdir($dir);
    if (is_dir($defaultDir)) {
      if (is_writable($dir)) {
        $result = $this->copyRecursive($defaultDir, $dir, false);
        if ($result) {
          if ($this->v) $this->log->info("Imported: $defaultDir => $dir");
        } else {
          $this->log->warning("Error Importing: $defaultDir => $dir");
        }
      } else {
        $this->log->warning("$dir is not writable, unable to install default site modules (recommended, but not required)");
      }
    }
  }


  /**
   * Import files to profile
   *
   * @param string $fromPath
   */
  protected function profileImportFiles($fromPath) {
    if (self::TEST_MODE) {
      if ($this->v) $this->log->info("TEST MODE: Skipping file import - $fromPath");
      return;
    }

    $dir = new DirectoryIterator($fromPath);
    foreach ($dir as $file) {
      if ($file->isDot()) continue;
      if (!$file->isDir()) continue;

      $dirname = $file->getFilename();
      $pathname = $file->getPathname();

      if (is_writable($pathname) && self::FORCE_COPY == false) {
        // if it's writable, then we know all the files are likely writable too, so we can just rename it
        $result = $this->fs->rename($pathname, $this->projectDir . "/site/assets/$dirname/");
      } else {
        // if it's not writable, then we will make a copy instead, and that copy should be writable by the server
        $result = $this->copyRecursive($pathname, $this->projectDir . "/site/assets/$dirname/");
      }

      if ($result) {
        if ($this->v) $this->log->info("Imported: $pathname => ./site/assets/$dirname/");
      } else {
        $this->error("Error Importing: $pathname => ./site/assets/$dirname/");
      }
    }
  }

  /**
   * Import profile SQL dump
   *
   * @param Database $database
   * @param string $file1
   * @param string $file2
   * @param array $options
   */
  protected function profileImportSQL($database, $file1, $file2, array $options = array()) {
    $defaults = array(
      'dbEngine' => 'MyISAM',
      'dbCharset' => 'utf8mb4',
    );

    $options = array_merge($defaults, $options);
    if (self::TEST_MODE) return;
    $restoreOptions = array();
    $replace = array();

    if ($options['dbEngine'] != 'MyISAM') {
      $replace['ENGINE=MyISAM'] = "ENGINE=$options[dbEngine]";
      $this->log->warning("Engine changed to '$options[dbEngine]', please keep an eye out for issues.");
    }

    if ($options['dbCharset'] != 'utf8mb4') {
      $replace['CHARSET=utf8mb4'] = "CHARSET=$options[dbCharset]";
      $this->log->warning("Character set has been changed to '$options[dbCharset]', please keep an eye out for issues.");
    }

    if (count($replace)) $restoreOptions['findReplaceCreateTable'] = $replace;
    require($this->projectDir . "/wire/core/WireDatabaseBackup.php");
    $backup = new WireDatabaseBackup();
    $backup->setDatabase($database);

    if ($backup->restoreMerge($file1, $file2, $restoreOptions)) {
      if ($this->v) {
        $this->log->info("Imported database file: $file1");
        $this->log->info("Imported database file: $file2");
      }
    } else {
      foreach ($backup->errors() as $error) $this->error($error);
    }
  }

  /**
   * Save submitted admin account form
   *
   * @param array $accountInfo
   */
  protected function adminAccountSave($accountInfo) {
    if (file_exists($this->projectDir . '/vendor/autoload.php')) {
      require $this->projectDir . '/vendor/autoload.php';
    }
    require $this->projectDir . '/index.php';

    $input = $wire->input;
    $sanitizer = $wire->sanitizer;

    if (!$accountInfo['username'] || !$accountInfo['userpass']) $this->error('Missing account information');
    if ($accountInfo['userpass'] !== $accountInfo['userpass_confirm']) $this->error('Passwords do not match');
    if (strlen($accountInfo['userpass']) < 6) $this->error('Password must be at least 6 characters long');

    $username = $sanitizer->pageName($accountInfo['username']);
    if ($username != $accountInfo['username']) $this->error('Username must be only a-z 0-9');
    if (strlen($username) < 2) $this->error('Username must be at least 2 characters long');

    $adminName = $sanitizer->pageName($accountInfo['adminUrl']);
    if ($adminName !== $accountInfo['adminUrl']) $this->error('Admin login URL must be only a-z 0-9');
    if (strlen($adminName) < 2) $this->error('Admin login URL must be at least 2 characters long');

    $email = strtolower($sanitizer->email($accountInfo['useremail']));
    if ($email != strtolower($accountInfo['useremail'])) $this->error('Email address did not validate');
    if ($this->numErrors) return;

    $superuserRole = $wire->roles->get("name=superuser");
    $user = $wire->users->get($wire->config->superUserPageID);

    if (!$user->id) {
      $user = new User();
      $user->id = $wire->config->superUserPageID;
    }

    $user->name = $username;
    $user->pass = $accountInfo['userpass'];
    $user->email = $email;

    if (!$user->roles->has("superuser")) $user->roles->add($superuserRole);

    $admin = $wire->pages->get($wire->config->adminRootPageID);
    $admin->of(false);
    $admin->name = $adminName;

    try {
      if (self::TEST_MODE) {
        if ($this->v) $this->log->info('TEST MODE: skipped user creation');
      } else {
        $wire->users->save($user);
        $wire->pages->save($admin);
      }
    } catch(Exception $e) {
      $this->error($e->getMessage());
      return;
    }

    $adminName = htmlentities($adminName, ENT_QUOTES, "UTF-8");

    if ($this->v) $this->log->info("User account saved: <b>{$user->name}</b>");
    $theme = $wire->modules->getInstall('AdminThemeUikit');
    $configData = $wire->modules->getModuleConfigData('AdminThemeUikit');
    $wire->modules->saveModuleConfigData('AdminThemeUikit', $configData);

    if ($this->v) $this->log->info("It is recommended that you make <b>/site/config.php</b> non-writable, for security.");

    if (!self::TEST_MODE) {
      if (@unlink($this->projectDir . "/install.php")) {
        if ($this->v) $this->log->info('Deleted this installer (./install.php) for security.');
      } else {
        if ($this->v) $this->log->info('Please delete this installer! The file is located in your web root at: ./install.php');
      }
    }

    if ($this->v) {
      $this->log->info('There are additional configuration options available in <b>/site/config.php</b> that you may want to review.');
      $this->log->info('To save space, you may optionally delete <b>/site/install/</b> - it\'s no longer needed.');
      $this->log->info('Note that future runtime errors are logged to <b>/site/assets/logs/errors.txt</b> (not web accessible).');
      $this->log->info("Your admin URL is <a href='./$adminName/'>/$adminName/</a>");
    }

    // set a define that indicates installation is completed so that this script no longer runs
    if (!self::TEST_MODE) {
      file_put_contents($this->projectDir . "/site/assets/installed.php", "<?php // The existence of this file prevents the installer from running. Don't delete it unless you want to re-run the install or you have deleted ./install.php.");
    }
  }

  /**
   * Report and log an error
   *
   * @param string $str
   */
  protected function error($str) {
    $this->numErrors++;
    $this->log->error($str);
    return false;
  }

  /**
   * Create a directory and assign permission
   *
   * @param string $path
   * @param boolean $showNote
   */
  protected function mkdir($path, $showNote = true) {
    if (self::TEST_MODE) return;
    if (mkdir($path)) {
      chmod($path, octdec($this->chmodDir));
      if ($showNote && $this->v) $this->log->info("Created directory: $path");
      return true;
    } else {
      if ($showNote) $this->error("Error creating directory: $path");
      return false;
    }
  }

  /**
   * Copy directories recursively
   *
   * @param string $src
   * @param string $dst
   */
  protected function copyRecursive($src, $dst) {
    if (self::TEST_MODE) return;

    if (substr($src, -1) != '/') $src .= '/';
    if (substr($dst, -1) != '/') $dst .= '/';

    $dir = opendir($src);
    $this->mkdir($dst, false);

    while(false !== ($file = readdir($dir))) {
      if ($file == '.' || $file == '..') continue;
      if (is_dir($src . $file)) {
        $this->copyRecursive($src . $file, $dst . $file);
      } else {
        copy($src . $file, $dst . $file);
        chmod($dst . $file, octdec($this->chmodFile));
      }
    }

    closedir($dir);
    return true;
  }

  /**
   * Timezones
   */
  protected function timezones() {
    $timezones = timezone_identifiers_list();
    $extras = array(
      'US Eastern|America/New_York',
      'US Central|America/Chicago',
      'US Mountain|America/Denver',
      'US Mountain (no DST)|America/Phoenix',
      'US Pacific|America/Los_Angeles',
      'US Alaska|America/Anchorage',
      'US Hawaii|America/Adak',
      'US Hawaii (no DST)|Pacific/Honolulu',
    );

    foreach ($extras as $t) $timezones[] = $t;

    return $timezones;
  }
}
