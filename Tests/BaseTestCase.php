<?php namespace Wirecli\Tests;

use GuzzleHttp\Client;
use \PHPUnit\Framework\TestCase as TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Filesystem\Filesystem;

abstract class BaseTestCase extends TestCase {

    const INSTALLATION_FOLDER = 'ProcessWire';
    const INSTALLATION_ARCHIVE = 'Tests/processwire.zip';

    /**
     * @var Filesystem
     */
    public $fs;

    /**
     * @var Application
     */
    public $app = null;

    public $tester = null;
    public $command = null;

    public function __construct($name = null, array $data = [], $dataName = '') {
      parent::__construct($name, $data, $dataName);
      $this->fs = new Filesystem();
      $this->app = new Application();
      $this->app->setAutoExit(false);
    }

    public function checkInstallation() {
        if ($this->fs->exists(self::INSTALLATION_FOLDER)) $this->fs->remove(self::INSTALLATION_FOLDER);

        // if installation exists and zip file is older than 24h remove it
        if ($this->fs->exists(self::INSTALLATION_ARCHIVE) && (time() - filemtime(self::INSTALLATION_ARCHIVE)) > 86400) {
            $this->fs->remove(self::INSTALLATION_ARCHIVE);
        }

        if (!$this->fs->exists(self::INSTALLATION_ARCHIVE)) $this->downloadArchive();
    }

    public function downloadArchive() {
        $client = new Client();
        $client->request('GET', 'https://github.com/processwire/processwire/archive/master.zip', ['sink' => 'Tests/processwire.zip']);
    }

}
