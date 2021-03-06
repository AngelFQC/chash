<?php

namespace Chash\Command\Database;

use Chash\Command\Common\DatabaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class DropDatabaseCommand.
 */
class DropDatabaseCommand extends DatabaseCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName('db:drop_databases')
            ->setDescription('Drops all databases from the current Chamilo install');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $helper = $this->getHelperSet()->get('question');
        $question = new ConfirmationQuestion(
            '<question>Are you sure you want to drop all database in this portal? (y/N)</question>',
            false
        );
        if (!$helper->ask($input, $output, $question)) {
            return 0;
        }

        $question = new ConfirmationQuestion('<question>Are you really sure? (y/N)</question>', false);
        if (!$helper->ask($input, $output, $question)) {
            return 0;
        }

        $_configuration = $this->getConfigurationArray();
        $connection = $this->getConnection($input);

        if ($connection) {
            $list = $_configuration = $this->getHelper('configuration')->getAllDatabases();
            $currentDatabases = $connection->getSchemaManager()->listDatabases();
            if (is_array($list)) {
                $output->writeln('<comment>Starting Chamilo drop database process.</comment>');
                foreach ($list as $db) {
                    if (in_array($db, $currentDatabases)) {
                        $output->writeln("Dropping DB: $db");
                        $connection->getSchemaManager()->dropDatabase($db);
                    } else {
                        $output->writeln("DB: $db was already dropped.");
                    }
                }
                $output->writeln('<comment>End drop database process.</comment>');
            }
        } else {
            $output->writeln("<comment>Can't established connection with the database. Probably it was already deleted.</comment>");
        }

        return 0;
    }
}
