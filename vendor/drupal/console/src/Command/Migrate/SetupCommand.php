<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Migrate\SetupCommand.
 */

namespace Drupal\Console\Command\Migrate;

use Drupal\Console\Style\DrupalStyle;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Command\Shared\DatabaseTrait;
use Drupal\Console\Command\Shared\MigrationTrait;
use Drupal\migrate\Plugin\RequirementsInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

class SetupCommand extends Command
{
    use ContainerAwareCommandTrait;
    use DatabaseTrait;
    use MigrationTrait;

    protected function configure()
    {
        $this
            ->setName('migrate:setup')
            ->setDescription($this->trans('commands.migrate.setup.description'))
            ->addOption(
                'db-type',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.setup.options.db-type')
            )
            ->addOption(
                'db-host',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.setup.options.db-host')
            )
            ->addOption(
                'db-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.setup.options.db-name')
            )
            ->addOption(
                'db-user',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.setup.options.db-user')
            )
            ->addOption(
                'db-pass',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.migrate.setup.options.db-pass')
            )
            ->addOption(
                'db-prefix',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.migrate.setup.options.db-prefix')
            )
            ->addOption(
                'db-port',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.setup.options.db-port')
            )
            ->addOption(
                'files-directory',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.migrate.setup.options.files-directory')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // --db-type option
        $db_type = $input->getOption('db-type');
        if (!$db_type) {
            $db_type = $this->dbDriverTypeQuestion($output);
            $input->setOption('db-type', $db_type);
        }

        // --db-host option
        $db_host = $input->getOption('db-host');
        if (!$db_host) {
            $db_host = $this->dbHostQuestion($output);
            $input->setOption('db-host', $db_host);
        }

        // --db-name option
        $db_name = $input->getOption('db-name');
        if (!$db_name) {
            $db_name = $this->dbNameQuestion($output);
            $input->setOption('db-name', $db_name);
        }

        // --db-user option
        $db_user = $input->getOption('db-user');
        if (!$db_user) {
            $db_user = $this->dbUserQuestion($output);
            $input->setOption('db-user', $db_user);
        }

        // --db-pass option
        $db_pass = $input->getOption('db-pass');
        if (!$db_pass) {
            $db_pass = $this->dbPassQuestion($output);
            $input->setOption('db-pass', $db_pass);
        }

        // --db-prefix
        $db_prefix = $input->getOption('db-prefix');
        if (!$db_prefix) {
            $db_prefix = $this->dbPrefixQuestion($output);
            $input->setOption('db-prefix', $db_prefix);
        }

        // --db-port prefix
        $db_port = $input->getOption('db-port');
        if (!$db_port) {
            $db_port = $this->dbPortQuestion($output);
            $input->setOption('db-port', $db_port);
        }

         // --files-directory
        $files_directory = $input->getOption('files-directory');
        if (!$files_directory) {
            $files_directory = $io->ask(
                $this->trans('commands.migrate.setup.questions.files-directory'),
                ''
            );
            $input->setOption('files-directory', $files_directory);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
       

        $this->registerMigrateDB($input, $output);
        $this->migrateConnection = $this->getDBConnection($output, 'default', 'upgrade');

        if (!$drupal_version = $this->getLegacyDrupalVersion($this->migrateConnection)) {
            $io->error($this->trans('commands.migrate.setup.migrations.questions.not-drupal'));
            return;
        }
        
        $database = $this->getDBInfo();
        $version_tag = 'Drupal ' . $drupal_version;
        
        $this->createDatabaseStateSettings($database, $drupal_version);
        
        $migrations  = $this->getMigrations($version_tag);
        
        if ($migrations) {
            $io->info(
                sprintf(
                    $this->trans('commands.migrate.setup.messages.migrations-created'),
                    count($migrations),
                    $version_tag
                )
            );
        }
    }
}
