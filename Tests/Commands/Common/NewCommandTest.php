<?php namespace Wirecli\Tests\Commands\Common;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Ofbeaton\Console\Tester\UnhandledQuestionException;
use Wirecli\Tests\BaseTestCase as Base;
use Wirecli\Commands\Common\NewCommand;

class NewCommandTest extends Base {
    /**
     * @field array default config values
     */
    protected $defaults = array(
        '--timezone' => 'Europe\Berlin',
        '--httpHosts' => 'wirecli.sek',
        '--username' => 'admin',
        '--userpass' => 'password',
        '--useremail' => 'test@wirecli.pw'
    );

    /**
     * @before
     */
    public function setupCommand() {
        $this->app->add(new NewCommand());
        $this->command = $this->app->find('new');
        $this->tester = new CommandTester($this->command);

        $credentials = array(
            '--dbUser' => $GLOBALS['DB_USER'], 
            '--dbPass' => $GLOBALS['DB_PASSWD'],
            '--dbName' => $GLOBALS['DB_DBNAME']
        );

        $this->extends = array(
            'command'  => $this->command->getName(),
            'directory' => Base::INSTALLATION_FOLDER,
        );

        $this->defaults = array_merge($this->defaults, $credentials, $this->extends);
    }

    public function testDownload() {
        $this->checkInstallation();
        $options = array('--no-install' => true, '--src' => Base::INSTALLATION_ARCHIVE);
        $this->tester->execute(array_merge($this->defaults, $options));

        $this->assertDirectoryExists(Base::INSTALLATION_FOLDER);
        $this->assertDirectoryExists(Base::INSTALLATION_FOLDER . '/wire');
        $this->assertDirectoryNotExists(Base::INSTALLATION_FOLDER . '/site');
    }

    public function testDownloadWithRelativeSrc() {
        $this->checkInstallation();
        $relativePath = basename(Base::INSTALLATION_ARCHIVE); // e.g., 'archive.zip'
        if (!file_exists($relativePath)) {
            copy(Base::INSTALLATION_ARCHIVE, $relativePath);
        }
        // Ensure the ProcessWire directory exists for the test
        if (!is_dir(Base::INSTALLATION_FOLDER)) {
            mkdir(Base::INSTALLATION_FOLDER);
        }
        $options = array('--no-install' => true, '--src' => $relativePath);
        $this->tester->execute(array_merge($this->defaults, $options));
        $this->assertDirectoryExists(Base::INSTALLATION_FOLDER);
        if (file_exists($relativePath)) {
            unlink($relativePath);
        }
    }

    public function testDownloadWithAbsoluteSrc() {
        $this->checkInstallation();
        $absolutePath = realpath(Base::INSTALLATION_ARCHIVE);
        // Ensure the ProcessWire directory exists for the test
        if (!is_dir(Base::INSTALLATION_FOLDER)) {
            mkdir(Base::INSTALLATION_FOLDER);
        }
        $options = array('--no-install' => true, '--src' => $absolutePath);
        $this->tester->execute(array_merge($this->defaults, $options));
        $this->assertDirectoryExists(Base::INSTALLATION_FOLDER);
    }

    public function testDownloadWithNonExistentSrc() {
        $this->checkInstallation();
        $options = array('--no-install' => true, '--src' => 'nonexistent.zip');
        $this->expectException(\RuntimeException::class);
        $this->tester->execute(array_merge($this->defaults, $options));
    }

    public function testDownloadWithMixedSlashesAndSpacesSrc() {
        $this->checkInstallation();
        $absolutePath = realpath(Base::INSTALLATION_ARCHIVE);
        // Simulate a path with mixed slashes and spaces
        $mixedPath = str_replace('/', '\\', $absolutePath);
        $mixedPath = ' ' . $mixedPath . ' ';
        // Ensure the ProcessWire directory exists for the test
        if (!is_dir(Base::INSTALLATION_FOLDER)) {
            mkdir(Base::INSTALLATION_FOLDER);
        }
        $options = array('--no-install' => true, '--src' => $mixedPath);
        $this->tester->execute(array_merge($this->defaults, $options));
        $this->assertDirectoryExists(Base::INSTALLATION_FOLDER);
    }

    /**
      * @depends testDownload
      * @expectedException RuntimeException
      * @expectedExceptionMessageRegExp /(Database connection information did not work)./
      */
    public function testInstallWrongPassword() {
        // check ProcessWire has not been installed yet
        if ($this->fs->exists(Base::INSTALLATION_FOLDER . '/site/config.php')) return;

        // return the input you want to answer the question with
        $this->mockQuestionHelper($this->command, function($text, $order, Question $question) {
            if (strpos($text, 'database user name') !== false) return 'whatever';
            if (strpos($text, 'database password') !== false) return 'wrong';

            throw new UnhandledQuestionException();
        });

        $options = array(
            '--src' => Base::INSTALLATION_ARCHIVE,
            '--dbPass' => 'wrong'
        );

        $this->tester->execute(array_merge($this->defaults, $options));
    }

    /**
      * @depends testDownload
      * @expectedExceptionMessageRegExp /(enter a valid email address)/
      */
    public function testInstallInvalidEmailAddress() {
        // check ProcessWire has not been installed yet
        if ($this->fs->exists(Base::INSTALLATION_FOLDER . '/site/config.php')) return;

        // return the input you want to answer the question with
        $this->mockQuestionHelper($this->command, function($text, $order, Question $question) {
            if (strpos($text, 'admin email address') !== false) return 'whatever';

            throw new UnhandledQuestionException();
        });

        $options = array(
            '--src' => Base::INSTALLATION_ARCHIVE,
            '--useremail' => 'invalid'
        );

        $this->tester->execute(array_merge($this->defaults, $options));
    }

    /**
     * @depends testDownload
     */
    public function testInstall() {
        // check ProcessWire has not been installed yet
        if ($this->fs->exists(Base::INSTALLATION_FOLDER . '/site/config.php')) return;

        $this->tester->execute($this->defaults);
        $output = $this->tester->getDisplay();

        $this->assertDirectoryExists(Base::INSTALLATION_FOLDER . '/site');
        $this->assertFileExists(Base::INSTALLATION_FOLDER . '/site/config.php');
        $this->assertContains('Congratulations, ProcessWire has been successfully installed.', $output);
    }

    /**
     * @depends testInstall
     * @expectedExceptionMessageRegExp /(There is already a \')(.*)(\' project)/
     */
    public function testIsInstalled() {
        $this->tester->execute($this->defaults);
    }
}
