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

use DBConnection;
use Glpi\Console\AbstractCommand;
use Glpi\System\Requirement\DbTimezones;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class TimestampsCommand extends AbstractCommand
{
   /**
    * Error code returned when failed to migrate one table.
    *
    * @var integer
    */
    const ERROR_TABLE_MIGRATION_FAILED = 1;

   /**
    * Error code returned if DB configuration file cannot be updated.
    *
    * @var integer
    */
    const ERROR_UNABLE_TO_UPDATE_CONFIG = 2;

    protected function configure()
    {
        parent::configure();

        $this->setName('glpi:migration:timestamps');
        $this->setDescription(__('Convert "datetime" fields to "timestamp" to use timezones.'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
       //convert db

       // we are going to update datetime types to timestamp type
        $tbl_iterator = $this->db->getTzIncompatibleTables();

        $output->writeln(
            sprintf(
                '<info>' . __('Found %s table(s) requiring migration.') . '</info>',
                $tbl_iterator->count()
            )
        );

        if ($tbl_iterator->count() === 0) {
            $output->writeln('<info>' . __('No migration needed.') . '</info>');
        } else {
            if (!$input->getOption('no-interaction')) {
               // Ask for confirmation (unless --no-interaction)
               /** @var \Symfony\Component\Console\Helper\QuestionHelper $question_helper */
                $question_helper = $this->getHelper('question');
                $run = $question_helper->ask(
                    $input,
                    $output,
                    new ConfirmationQuestion(__('Do you want to continue?') . ' [Yes/no]', true)
                );
                if (!$run) {
                     $output->writeln(
                         '<comment>' . __('Migration aborted.') . '</comment>',
                         OutputInterface::VERBOSITY_VERBOSE
                     );
                       return 0;
                }
            }

            $progress_bar = new ProgressBar($output, $tbl_iterator->count());
            $progress_bar->start();

            foreach ($tbl_iterator as $table) {
                $progress_bar->advance(1);

                $tablealter = ''; // init by default

               // get accurate info from information_schema to perform correct alter
                $col_iterator = $this->db->request([
                'SELECT' => [
                  'table_name AS TABLE_NAME',
                  'column_name AS COLUMN_NAME',
                  'column_default AS COLUMN_DEFAULT',
                  'column_comment AS COLUMN_COMMENT',
                  'is_nullable AS IS_NULLABLE',
                ],
                'FROM'   => 'information_schema.columns',
                'WHERE'  => [
                  'table_schema' => $this->db->dbdefault,
                  'table_name'   => $table['TABLE_NAME'],
                  'data_type'    => 'datetime'
                ]
                ]);

                foreach ($col_iterator as $column) {
                     $nullable = false;
                     $default = null;
                     //check if nullable
                    if ('YES' === $column['IS_NULLABLE']) {
                        $nullable = true;
                    }

                     //guess default value
                    if (is_null($column['COLUMN_DEFAULT']) && !$nullable) { // no default
                      // Prevent MySQL/MariaDB to force "default current_timestamp on update current_timestamp"
                      // as "on update current_timestamp" could be a real problem on fields like "date_creation".
                        $default = "CURRENT_TIMESTAMP";
                    } else if ((is_null($column['COLUMN_DEFAULT']) || strtoupper($column['COLUMN_DEFAULT']) == 'NULL') && $nullable) {
                        $default = "NULL";
                    } else if (!is_null($column['COLUMN_DEFAULT']) && strtoupper($column['COLUMN_DEFAULT']) != 'NULL') {
                        if (preg_match('/^current_timestamp(\(\))?$/i', $column['COLUMN_DEFAULT']) === 1) {
                              $default = $column['COLUMN_DEFAULT'];
                        } else if ($column['COLUMN_DEFAULT'] < '1970-01-01 00:00:01') {
                        // Prevent default value to be out of range (lower to min possible value)
                            $defaultDate = new \DateTime('1970-01-01 00:00:01', new \DateTimeZone('UTC'));
                            $defaultDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                            $default = $this->db->quoteValue($defaultDate->format("Y-m-d H:i:s"));
                        } else if ($column['COLUMN_DEFAULT'] > '2038-01-19 03:14:07') {
                        // Prevent default value to be out of range (greater to max possible value)
                            $defaultDate = new \DateTime('2038-01-19 03:14:07', new \DateTimeZone('UTC'));
                            $defaultDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                            $default = $this->db->quoteValue($defaultDate->format("Y-m-d H:i:s"));
                        } else {
                            $default = $this->db->quoteValue($column['COLUMN_DEFAULT']);
                        }
                    }

                   //build alter
                    $tablealter .= "\n\t MODIFY COLUMN " . $this->db->quoteName($column['COLUMN_NAME']) . " TIMESTAMP";
                    if ($nullable) {
                            $tablealter .= " NULL";
                    } else {
                          $tablealter .= " NOT NULL";
                    }
                    if ($default !== null) {
                        $tablealter .= " DEFAULT $default";
                    }
                    if ($column['COLUMN_COMMENT'] != '') {
                        $tablealter .= " COMMENT '" . $this->db->escape($column['COLUMN_COMMENT']) . "'";
                    }
                    $tablealter .= ",";
                }
                $tablealter =  rtrim($tablealter, ",");

               // apply alter to table
                $query = "ALTER TABLE " . $this->db->quoteName($table['TABLE_NAME']) . " " . $tablealter . ";\n";
                $this->writelnOutputWithProgressBar(
                    '<comment>' . sprintf(__('Running %s'), $query) . '</comment>',
                    $progress_bar,
                    OutputInterface::VERBOSITY_VERBOSE
                );

                $result = $this->db->query($query);
                if (false === $result) {
                     $message = sprintf(
                         __('Update of `%s` failed with message "(%s) %s".'),
                         $table['TABLE_NAME'],
                         $this->db->errno(),
                         $this->db->error()
                     );
                     $this->writelnOutputWithProgressBar(
                         '<error>' . $message . '</error>',
                         $progress_bar,
                         OutputInterface::VERBOSITY_QUIET
                     );
                     return self::ERROR_TABLE_MIGRATION_FAILED;
                }
            }

            $progress_bar->finish();
            $this->output->write(PHP_EOL);
        }

        $properties_to_update = [
         DBConnection::PROPERTY_ALLOW_DATETIME => false,
        ];

        $timezones_requirement = new DbTimezones($this->db);
        if ($timezones_requirement->isValidated()) {
            $properties_to_update[DBConnection::PROPERTY_USE_TIMEZONES] = true;
        } else {
            $output->writeln(
                '<error>' . __('Timezones usage cannot be activated due to following errors:') . '</error>',
                OutputInterface::VERBOSITY_QUIET
            );
            foreach ($timezones_requirement->getValidationMessages() as $validation_message) {
                $output->writeln(
                    '<error> - ' . $validation_message . '</error>',
                    OutputInterface::VERBOSITY_QUIET
                );
            }
            $message = sprintf(
                __('Fix them and run the "php bin/console %1$s" command to enable timezones.'),
                'glpi:database:enable_timezones'
            );
            $output->writeln('<error>' . $message . '</error>', OutputInterface::VERBOSITY_QUIET);
        }

        if (!DBConnection::updateConfigProperties($properties_to_update)) {
            throw new \Glpi\Console\Exception\EarlyExitException(
                '<error>' . __('Unable to update DB configuration file.') . '</error>',
                self::ERROR_UNABLE_TO_UPDATE_CONFIG
            );
        }

        if ($tbl_iterator->count() > 0) {
            $output->writeln('<info>' . __('Migration done.') . '</info>');
        }

        return 0; // Success
    }
}
