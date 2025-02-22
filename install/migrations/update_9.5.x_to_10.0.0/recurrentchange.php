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
 * @var DB $DB
 * @var Migration $migration
 */

$migration->displayMessage("Adding recurrent changes");

$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

$DB->updateOrDie(
    'glpi_crontasks',
    [
      'itemtype' => 'CommonITILRecurrentCron',
      'name'     => 'RecurrentItems'
    ],
    [
      'itemtype' => 'TicketRecurrent',
      'name'     => 'ticketrecurrent',
    ],
    "CommonITILReccurent crontask"
);

$recurrent_change_table = 'glpi_recurrentchanges';
if (!$DB->tableExists($recurrent_change_table)) {
    $DB->queryOrDie("CREATE TABLE `$recurrent_change_table` (
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `name` varchar(255) DEFAULT NULL,
         `comment` text,
         `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `is_recursive` tinyint NOT NULL DEFAULT '0',
         `is_active` tinyint NOT NULL DEFAULT '0',
         `changetemplates_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `begin_date` timestamp NULL DEFAULT NULL,
         `periodicity` varchar(255) DEFAULT NULL,
         `create_before` int NOT NULL DEFAULT '0',
         `next_creation_date` timestamp NULL DEFAULT NULL,
         `calendars_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `end_date` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `entities_id` (`entities_id`),
         KEY `is_recursive` (`is_recursive`),
         KEY `is_active` (`is_active`),
         KEY `changetemplates_id` (`changetemplates_id`),
         KEY `next_creation_date` (`next_creation_date`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};");
}

$migration->addRight('recurrentchange', ALLSTANDARDRIGHT, [
   'change' => UPDATE
]);
