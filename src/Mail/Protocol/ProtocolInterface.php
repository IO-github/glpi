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

namespace Glpi\Mail\Protocol;

interface ProtocolInterface
{
   /**
    * Do not validate SSL certificate
    *
    * @param  bool $novalidatecert Set to true to disable certificate validation
    *
    * @return self
    */
    public function setNoValidateCert(bool $novalidatecert);

   /**
    * Open connection to server.
    *
    * @param  string      $host  hostname or IP address of POP3 server
    * @param  int|null    $port  server port, null value with fallback to default port
    * @param  string|bool $ssl   use 'SSL', 'TLS' or false
    */
    public function connect($host, $port = null, $ssl = false);

   /**
    * Login to server.
    *
    * @param  string $user      username
    * @param  string $password  password
    *
    * @return bool
    */
    public function login($user, $password);
}
