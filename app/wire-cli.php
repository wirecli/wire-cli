<?php

use AutoShell\Console;
use Symfony\Component\Console\Application;

use Wirecli\Commands\User\UserCreateCommand;
use Wirecli\Commands\User\UserUpdateCommand;
use Wirecli\Commands\User\UserDeleteCommand;
use Wirecli\Commands\User\UserListCommand;
use Wirecli\Commands\Role\RoleCreateCommand;
use Wirecli\Commands\Role\RoleDeleteCommand;
use Wirecli\Commands\Role\RoleListCommand;
use Wirecli\Commands\Template\TemplateCreateCommand;
use Wirecli\Commands\Template\TemplateFieldsCommand;
use Wirecli\Commands\Template\TemplateTagCommand;
use Wirecli\Commands\Template\TemplateInfoCommand;
use Wirecli\Commands\Template\TemplateDeleteCommand;
use Wirecli\Commands\Template\TemplateListCommand;
use Wirecli\Commands\Field\FieldCreateCommand;
use Wirecli\Commands\Field\FieldCloneCommand;
use Wirecli\Commands\Field\FieldDeleteCommand;
use Wirecli\Commands\Field\FieldTagCommand;
use Wirecli\Commands\Field\FieldTypesCommand;
use Wirecli\Commands\Field\FieldListCommand;
use Wirecli\Commands\Field\FieldEditCommand;
use Wirecli\Commands\Field\FieldRenameCommand;
use Wirecli\Commands\Module\ModuleDownloadCommand;
use Wirecli\Commands\Module\ModuleEnableCommand;
use Wirecli\Commands\Module\ModuleDisableCommand;
use Wirecli\Commands\Module\ModuleGenerateCommand;
use Wirecli\Commands\Module\ModuleUpgradeCommand;
use Wirecli\Commands\Common\NewCommand;
use Wirecli\Commands\Common\ViteCommand;
use Wirecli\Commands\Common\UpgradeCommand;
use Wirecli\Commands\Common\StatusCommand;
use Wirecli\Commands\Common\ServeCommand;
use Wirecli\Commands\Common\CheatCommand;
use Wirecli\Commands\Common\DebugCommand;
use Wirecli\Commands\Backup\BackupDatabaseCommand;
use Wirecli\Commands\Backup\RestoreDatabaseCommand;
use Wirecli\Commands\Backup\BackupDuplicatorCommand;
use Wirecli\Commands\Backup\BackupImagesCommand;
use Wirecli\Commands\Page\PageCreateCommand;
use Wirecli\Commands\Page\PageListCommand;
use Wirecli\Commands\Page\PageDeleteCommand;
use Wirecli\Commands\Page\PageEmptyTrashCommand;
use Wirecli\Commands\Logs\LogTailCommand;
use Wirecli\Commands\Logs\LogListCommand;

if (file_exists(__DIR__.'/../../../autoload.php')) {
    require __DIR__.'/../../../autoload.php';
} else {
    require __DIR__.'/../vendor/autoload.php';
}

// get version from composer.json
$json = json_decode(file_get_contents(__DIR__.'/version.json'));
$version = join(["v", $json->version]);
$app = new Application('wire-cli - An extendable ProcessWire CLI', $version);

$app->add(new UserCreateCommand());
$app->add(new UserUpdateCommand());
$app->add(new UserDeleteCommand());
$app->add(new UserListCommand());
$app->add(new RoleCreateCommand());
$app->add(new RoleDeleteCommand());
$app->add(new RoleListCommand());
$app->add(new TemplateCreateCommand());
$app->add(new TemplateFieldsCommand());
$app->add(new TemplateTagCommand());
$app->add(new TemplateInfoCommand());
$app->add(new TemplateDeleteCommand());
$app->add(new TemplateListCommand());
$app->add(new FieldCreateCommand());
$app->add(new FieldCloneCommand());
$app->add(new FieldDeleteCommand());
$app->add(new FieldTagCommand());
$app->add(new FieldTypesCommand());
$app->add(new FieldListCommand());
$app->add(new FieldEditCommand());
$app->add(new FieldRenameCommand());
$app->add(new ModuleDownloadCommand());
$app->add(new ModuleEnableCommand());
$app->add(new ModuleDisableCommand());
$app->add(new ModuleGenerateCommand(new GuzzleHttp\Client()));
$app->add(new ModuleUpgradeCommand());
$app->add(new NewCommand());
$app->add(new ViteCommand());
$app->add(new UpgradeCommand(new \Symfony\Component\Filesystem\Filesystem()));
$app->add(new StatusCommand());
$app->add(new ServeCommand());
$app->add(new CheatCommand());
$app->add(new DebugCommand());
$app->add(new BackupDatabaseCommand());
$app->add(new BackupImagesCommand());
$app->add(new RestoreDatabaseCommand());
$app->add(new BackupDuplicatorCommand());
$app->add(new PageCreateCommand());
$app->add(new PageListCommand());
$app->add(new PageDeleteCommand());
$app->add(new PageEmptyTrashCommand());
$app->add(new LogTailCommand());
$app->add(new LogListCommand());

$app->run();