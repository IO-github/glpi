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

/**
 * @since 10.0.0
 */
class CleanSoftwareCron extends CommonDBTM
{
    const TASK_NAME = 'cleansoftware';

    const MAX_BATCH_SIZE = 2000;

    protected static $notable = true;

   /**
    * Get task description
    *
    * @return string
    */
    public static function getTaskDescription(): string
    {
        return __("Remove software versions with no installation and software with no version");
    }

   /**
    * Get task's parameter description
    *
    * @return string
    */
    public static function getParameterDescription(): string
    {
        return __('Max items to handle in one execution');
    }

    public static function cronInfo($name)
    {
        return [
         'description' => self::getTaskDescription(),
         'parameter' => self::getParameterDescription(),
        ];
    }

   /**
    * Clean unused software and software versions
    *
    * @param int $max Max items to handle
    * @return int Number of deleted items
    */
    public static function run(?int $max): int
    {
        $total = 0;

       // Delete software versions with no installation
        $total += self::deleteItems(
            self::getVersionsWithNoInstallationCriteria(),
            new SoftwareVersion(),
            $max
        );

       // Move software with no versions in the thrashbin
        $total += self::deleteItems(
            self::getSoftwareWithNoVersionsCriteria(),
            new Software(),
            $max - $total
        );

        return $total;
    }

   /**
    * Run from cronTask
    *
    * @param CronTask $task
    */
    public static function cronCleanSoftware(CronTask $task)
    {
        $max = $task->fields['param'];
        $total = self::run($max);
        $task->addVolume($total);

        return 1;
    }

   /**
    * Get all software versions which are not installed
    *
    * @return array
    */
    protected static function getVersionsWithNoInstallationCriteria(): array
    {
        return [
         'SELECT' => 'id',
         'FROM'   => SoftwareVersion::getTable(),
         'WHERE'  => [
            'NOT' => [
               'OR' => [
                  [
                     'id' => new QuerySubQuery([
                        'SELECT' => 'softwareversions_id',
                        'FROM'   => Item_SoftwareVersion::getTable(),
                     ])
                  ],
                  [
                     'id' => new QuerySubQuery([
                        'SELECT' => 'softwareversions_id_buy',
                        'FROM'   => SoftwareLicense::getTable(),
                     ]),
                  ],
                  [
                     'id' => new QuerySubQuery([
                        'SELECT' => 'softwareversions_id_use',
                        'FROM'   => SoftwareLicense::getTable(),
                     ]),
                  ],
               ],
            ],
         ],
        ];
    }

   /**
    * Get all software with no versions
    *
    * @return array
    */
    protected static function getSoftwareWithNoVersionsCriteria(): array
    {
        return [
         'SELECT' => 'id',
         'FROM'   => Software::getTable(),
         'WHERE'  => [
            'is_deleted' => 0,
            'NOT' => [
               'id' => new QuerySubQuery([
                  'SELECT' => 'softwares_id',
                  'FROM'   => SoftwareVersion::getTable(),
               ]),
            ]
         ]
        ];
    }

   /**
    * Delete given items
    *
    * @param array         $scope   Items to delete
    * @param CommonDBTM    $em      EM for this itemtype
    * @param int           $max     Max number of items to handle
    *
    * @return int Number of items deleted
    */
    protected static function deleteItems(
        array $scope,
        CommonDBTM $em,
        int $max
    ): int {
        global $DB;

        $total = 0;

        do {
            $scope['LIMIT'] = min($max - $total, self::MAX_BATCH_SIZE);
            $items = $DB->request($scope);
            $count = count($items);
            $total += $count;

            foreach ($items as $item) {
                $em->delete($item);
            }

           // Stop if no items found
        } while ($count > 0);

        return $total;
    }
}
