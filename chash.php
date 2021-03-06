<?php

/**
 * Command-line tool to do things more swiftly in Chamilo.
 * To add support for a new command see the Console Component read:.
 *
 * https://speakerdeck.com/hhamon/symfony-extending-the-console-component
 * http://symfony.com/doc/2.0/components/console/introduction.html
 *
 * @author Julio Montoya <gugli100@gmail.com>
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 * @license This script is provided under the terms of the GNU/GPLv3+ license
 */

/* Security check: do not allow any other calling method than command-line */
if (PHP_SAPI != 'cli') {
    die("Chash cannot be called by any other method than the command line.");
}

require __DIR__.'/vendor/autoload.php';

use Doctrine\DBAL\Tools\Console\Command\RunSqlCommand;
use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\Migrations\Tools\Console\Command\ExecuteCommand;
use Doctrine\Migrations\Tools\Console\Command\GenerateCommand;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\Migrations\Tools\Console\Command\StatusCommand;
use Doctrine\Migrations\Tools\Console\Command\VersionCommand;
use Symfony\Component\Console\Application;

$helpers = [
    'configuration' => new Chash\Helpers\ConfigurationHelper(),
];

$application = new Application('Chamilo Command Line Interface', '1.0');
$helpers = [
    'configuration' => new Chash\Helpers\ConfigurationHelper(),
];
$helperSet = $application->getHelperSet();
foreach ($helpers as $name => $helper) {
    $helperSet->set($helper, $name);
}

$application->addCommands(
    [
        // DBAL Commands.
        new RunSqlCommand(),
        //new \Doctrine\DBAL\Tools\Console\Command\ImportCommand(),

        // Migrations Commands.
        new DiffCommand(),
        new ExecuteCommand(),
        new GenerateCommand(),
        new MigrateCommand(),
        new StatusCommand(),
        new VersionCommand(),

        // Chash commands
        new Chash\Command\Chash\SetupCommand(),
        new Chash\Command\Chash\SelfUpdateCommand(),

        new Chash\Command\Database\RunSQLCommand(),
        new Chash\Command\Database\ImportCommand(),
        new Chash\Command\Database\DumpCommand(),
        new Chash\Command\Database\RestoreCommand(),
        new Chash\Command\Database\SQLCountCommand(),
        new Chash\Command\Database\FullBackupCommand(),
        new Chash\Command\Database\DropDatabaseCommand(),
        new Chash\Command\Database\ShowConnInfoCommand(),

        new Chash\Command\Files\CleanConfigFilesCommand(),
        new Chash\Command\Files\CleanCoursesFilesCommand(),
        new Chash\Command\Files\CleanDeletedDocumentsCommand(),
        new Chash\Command\Files\CleanTempFolderCommand(),
        new Chash\Command\Files\ConvertVideosCommand(),
        new Chash\Command\Files\DeleteCoursesCommand(),
        new Chash\Command\Files\DeleteMultiUrlCommand(),
        new Chash\Command\Files\GenerateTempFileStructureCommand(),
        new Chash\Command\Files\MailConfCommand(),
        new Chash\Command\Files\SetPermissionsAfterInstallCommand(),
        new Chash\Command\Files\ShowDiskUsageCommand(),
        new Chash\Command\Files\UpdateDirectoryMaxSizeCommand(),
        new Chash\Command\Files\ReplaceURLCommand(),

        new Chash\Command\Info\WhichCommand(),
        new Chash\Command\Info\GetInstancesCommand(),

        new Chash\Command\Installation\InstallCommand(),
        new Chash\Command\Installation\WipeCommand(),
        new Chash\Command\Installation\StatusCommand(),
        new Chash\Command\Installation\UpgradeCommand(),

        new Chash\Command\Translation\AddSubLanguageCommand(),
        new Chash\Command\Translation\DisableLanguageCommand(),
        new Chash\Command\Translation\EnableLanguageCommand(),
        new Chash\Command\Translation\ExportLanguageCommand(),
        new Chash\Command\Translation\ImportLanguageCommand(),
        new Chash\Command\Translation\ListLanguagesCommand(),
        new Chash\Command\Translation\PlatformLanguageCommand(),
        new Chash\Command\Translation\TermsPackageCommand(),

        new Chash\Command\User\ChangePassCommand(),
        new Chash\Command\User\DisableAdminsCommand(),
        new Chash\Command\User\MakeAdminCommand(),
        new Chash\Command\User\AddUserCommand(),
        new Chash\Command\User\ResetLoginCommand(),
        new Chash\Command\User\SetLanguageCommand(),
        new Chash\Command\User\UsersPerUrlAccessCommand(),
        new Chash\Command\Email\SendEmailCommand(),
    ]
);
$application->run();
