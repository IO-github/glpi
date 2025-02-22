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

namespace Glpi\Application\View\Extension;

use Entity;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @since 10.0.0
 */
class ConfigExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
         new TwigFunction('config', [$this, 'config']),
         new TwigFunction('entity_config', [$this, 'getEntityConfig']),
        ];
    }

   /**
    * Get GLPI configuration value.
    *
    * @param string $key
    *
    * @return mixed
    */
    public function config(string $key)
    {
        global $CFG_GLPI;

        return $CFG_GLPI[$key] ?? null;
    }

   /**
    * Get entity configuration value.
    *
    * @param string        $key              Configuration key.
    * @param null|int      $entity_id        Entity ID, defaults to current entity.
    * @param mixed         $default_value    Default value.
    * @param null|string   $inheritence_key  Key to use for inheritence check if different than key used to get value.
    *
    * @return mixed
    */
    public function getEntityConfig(string $key, ?int $entity_id = null, $default_value = -2, ?string $inheritence_key = null)
    {
        if ($inheritence_key === null) {
            $inheritence_key = $key;
        }

        return Entity::getUsedConfig($inheritence_key, $entity_id, $key, $default_value);
    }
}
