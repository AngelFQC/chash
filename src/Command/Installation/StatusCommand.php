<?php

namespace Chash\Command\Installation;

use Chash\Command\Common\DatabaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class StatusCommand.
 */
class StatusCommand extends DatabaseCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName('chash:chamilo_status')
            ->setDescription('Show the information of the current Chamilo installation')
            ->addOption(
                'show-pass',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set a value to show the chamilo database password'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);
        $connection = $this->getConnection($input);
        if (!$connection) {
            $output->writeln('<comment>No connection to the database.</comment>');

            return 0;
        }
        $_configuration = $this->getConfigurationArray();
        $showPass = $input->getOption('show-pass');
        $query = "SELECT selected_value FROM settings_current WHERE variable = 'chamilo_database_version'";
        $data = $connection->executeQuery($query);
        $data = $data->fetch();
        $chamiloVersion = $data['selected_value'];

        if (empty($chamiloVersion)) {
            $query = "SELECT selected_value FROM settings_current WHERE variable = 'dokeos_database_version'";
            $data = $connection->executeQuery($query);
            $data = $data->fetch();
            $chamiloVersion = $data['selected_value'];
        }

        if (!empty($chamiloVersion)) {
            $output->writeln('<comment>Database connection works.</comment>');
        }

        $output->writeln('<comment>Chamilo $_configuration info:</comment>');
        $output->writeln('');

        $output->writeln('<comment>Chamilo $_configuration[root_web]:</comment> <info>'.$_configuration['root_web'].'</info>');
        if (isset($_configuration['root_sys'])) {
            $output->writeln('<comment>Chamilo $_configuration[root_sys]:</comment> <info>'.$_configuration['root_sys'].'</info>');
        }

        //$output->writeln('<comment>Chamilo $_configuration[db_driver]:</comment> <info>'.$_configuration['db_driver'].'</info>');
        $output->writeln('<comment>Chamilo $_configuration[main_database]:</comment> <info>'.$_configuration['main_database'].'</info>');
        $output->writeln('<comment>Chamilo $_configuration[db_host]:</comment> <info>'.$_configuration['db_host'].'</info>');
        $output->writeln('<comment>Chamilo $_configuration[db_user]:</comment> <info>'.$_configuration['db_user'].'</info>');
        $dbPassword = $_configuration['db_password'];
        if (empty($showPass)) {
            $dbPassLen = strlen($dbPassword);
            $dbPassword = str_repeat('*', $dbPassLen);
        }
        $output->writeln('<comment>Chamilo $_configuration[db_password]:</comment> <info>'.$dbPassword.'</info>');

        if (isset($_configuration['db_port'])) {
            $output->writeln('<comment>Chamilo $_configuration[db_port]:</comment> <info>'.$_configuration['db_port'].'</info>');
        }

        if (isset($_configuration['single_database'])) {
            $output->writeln('<comment>Chamilo $_configuration[single_database]:</comment> <info>'.$_configuration['single_database'].'</info>');
        }

        if (isset($_configuration['db_prefix'])) {
            $output->writeln('<comment>Chamilo $_configuration[db_prefix]:</comment> <info>'.$_configuration['db_prefix'].'</info>');
        }

        if (isset($_configuration['db_glue'])) {
            $output->writeln('<comment>Chamilo $_configuration[db_glue]:</comment> <info>'.$_configuration['db_glue'].'</info>');
        }

        if (isset($_configuration['db_prefix'])) {
            $output->writeln('<comment>Chamilo $_configuration[table_prefix]:</comment> <info>'.$_configuration['table_prefix'].'</info>');
        }
        $output->writeln('');

        if (empty($chamiloVersion)) {
            $output->writeln("<comment>Please check your Chamilo installation carefully the <info>'chamilo_database_version'</info> setting does not exist.</comment>");
        } else {
            //$output->writeln('<comment>Chamilo database settings:</comment>');
            //$output->writeln("<comment>Chamilo setting_current['".$databaseSetting."']:</comment> <info>".$chamiloVersion."</info>");
        }

        if (isset($_configuration['system_version'])) {
            $output->writeln('<comment>Chamilo $_configuration[system_version]:</comment> <info>'.$_configuration['system_version'].'</info>');
        }

        if (!version_compare(substr($chamiloVersion, 0, 5), substr($_configuration['system_version'], 0, 5), '==')) {
            /*$output->writeln("<error>Please check carefully your Chamilo installation. </error>");
            $output->writeln("<comment>The configuration.php file and the 'chamilo_database_version' setting are not synced.</comment>");*/
        }

        return 0;
    }
}
