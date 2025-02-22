<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\Console\Migration;

use Glpi\Console\AbstractCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DynamicRowFormatCommand extends AbstractCommand
{
   /**
    * Error code returned if migration failed on, at least, one table.
    *
    * @var integer
    */
    const ERROR_MIGRATION_FAILED_FOR_SOME_TABLES = 1;

   /**
    * Error code returned if some tables are still using MyISAM engine.
    *
    * @var integer
    */
    const ERROR_INNODB_REQUIRED = 2;

    protected $requires_db_up_to_date = false;

    protected function configure()
    {
        parent::configure();

        $this->setName('glpi:migration:dynamic_row_format');
        $this->setDescription(__('Convert database tables to "Dynamic" row format (required for "utf8mb4" character support).'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkForPrerequisites();
        $this->upgradeRowFormat();

        return 0; // Success
    }

   /**
    * Check for migration prerequisites.
    *
    * @return void
    */
    private function checkForPrerequisites(): void
    {

       // Check that all tables are using InnoDB engine
        if (($myisam_count = $this->db->getMyIsamTables()->count()) > 0) {
            $msg = sprintf(__('%d tables are using the deprecated MyISAM storage engine.'), $myisam_count)
            . ' '
            . sprintf(__('Run the "php bin/console %1$s" command to migrate them.'), 'glpi:migration:myisam_to_innodb');
            throw new \Glpi\Console\Exception\EarlyExitException('<error>' . $msg . '</error>', self::ERROR_INNODB_REQUIRED);
        }
    }

   /**
    * Upgrade row format from 'Compact'/'Redundant' to 'Dynamic'.
    * This is mandatory to support large indexes.
    *
    * @return void
    */
    private function upgradeRowFormat(): void
    {

        $table_iterator = $this->db->listTables(
            'glpi\_%',
            [
            'row_format'   => ['COMPACT', 'REDUNDANT'],
            ]
        );

        if (0 === $table_iterator->count()) {
            $this->output->writeln('<info>' . __('No migration needed.') . '</info>');
            return;
        }

        $this->output->writeln(
            sprintf(
                '<info>' . __('Found %s table(s) requiring a migration to "ROW_FORMAT=DYNAMIC".') . '</info>',
                $table_iterator->count()
            )
        );

        $this->askForConfirmation();

        $tables = [];
        foreach ($table_iterator as $table_data) {
            $tables[] = $table_data['TABLE_NAME'];
        }
        sort($tables);

        $progress_bar = new ProgressBar($this->output);
        $errors = 0;

        foreach ($progress_bar->iterate($tables) as $table) {
            $this->writelnOutputWithProgressBar(
                sprintf(__('Migrating table "%s"...'), $table),
                $progress_bar,
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $result = $this->db->query(
                sprintf('ALTER TABLE `%s` ROW_FORMAT = DYNAMIC', $table)
            );

            if (!$result) {
                $this->writelnOutputWithProgressBar(
                    sprintf(__('<error>Error migrating table "%s".</error>'), $table),
                    $progress_bar,
                    OutputInterface::VERBOSITY_QUIET
                );
                 $errors++;
            }
        }

        $this->output->write(PHP_EOL);

        if ($errors) {
            throw new \Glpi\Console\Exception\EarlyExitException(
                '<error>' . __('Errors occured during migration.') . '</error>',
                self::ERROR_MIGRATION_FAILED_FOR_SOME_TABLES
            );
        }

        $this->output->writeln('<info>' . __('Migration done.') . '</info>');
    }
}
