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

namespace tests\units\Glpi\Toolbox;

/**
 * Test class for src/Glpi/Toolbox/versionparser.class.php
 */
class VersionParser extends \GLPITestCase
{
    protected function versionsProvider()
    {
        return [
         [
            'version'             => '',
            'keep_stability_flag' => false,
            'normalized'          => '',
            'stable'              => true,
            'dev'                 => false,
         ],
         [
            'version'             => '9.5+2.0',
            'keep_stability_flag' => false,
            'normalized'          => '9.5+2.0', // not semver compatible, cannot be normalized
            'stable'              => true,
            'dev'                 => false,
         ],
         [
            'version'             => '0.89',
            'keep_stability_flag' => false,
            'normalized'          => '0.89.0',
            'stable'              => true,
            'dev'                 => false,
         ],
         [
            'version'             => '9.2',
            'keep_stability_flag' => false,
            'normalized'          => '9.2.0',
            'stable'              => true,
            'dev'                 => false,
         ],
         [
            'version'             => '9.2',
            'keep_stability_flag' => true, // should have no effect
            'normalized'          => '9.2.0',
            'stable'              => true,
            'dev'                 => false,
         ],
         [
            'version'             => '9.4.1.1',
            'keep_stability_flag' => false,
            'normalized'          => '9.4.1',
            'stable'              => true,
            'dev'                 => false,
         ],
         [
            'version'             => '10.0.0-dev',
            'keep_stability_flag' => false,
            'normalized'          => '10.0.0',
            'stable'              => false,
            'dev'                 => true,
         ],
         [
            'version'             => '10.0.0-dev',
            'keep_stability_flag' => true,
            'normalized'          => '10.0.0-dev',
            'stable'              => false,
            'dev'                 => true,
         ],
         [
            'version'             => '10.0.0-alpha',
            'keep_stability_flag' => false,
            'normalized'          => '10.0.0',
            'stable'              => false,
            'dev'                 => false,
         ],
         [
            'version'             => '10.0.0-alpha2',
            'keep_stability_flag' => true,
            'normalized'          => '10.0.0-alpha2',
            'stable'              => false,
            'dev'                 => false,
         ],
         [
            'version'             => '10.0.0-beta1',
            'keep_stability_flag' => false,
            'normalized'          => '10.0.0',
            'stable'              => false,
            'dev'                 => false,
         ],
         [
            'version'             => '10.0.0-beta1',
            'keep_stability_flag' => true,
            'normalized'          => '10.0.0-beta1',
            'stable'              => false,
            'dev'                 => false,
         ],
         [
            'version'             => '10.0.0-rc3',
            'keep_stability_flag' => false,
            'normalized'          => '10.0.0',
            'stable'              => false,
            'dev'                 => false,
         ],
         [
            'version'             => '10.0.0-rc',
            'keep_stability_flag' => true,
            'normalized'          => '10.0.0-rc',
            'stable'              => false,
            'dev'                 => false,
         ],
         [
            'version'             => '10.0.3',
            'keep_stability_flag' => true,
            'normalized'          => '10.0.3',
            'stable'              => true,
            'dev'                 => false,
         ],
        ];
    }

   /**
    * @dataProvider versionsProvider
    */
    public function testGetNormalizeVersion(string $version, bool $keep_stability_flag, string $normalized, bool $stable, bool $dev): void
    {
        $version_parser = $this->newTestedInstance();
        $this->string($version_parser->getNormalizedVersion($version, $keep_stability_flag))->isEqualTo($normalized);
    }

   /**
    * @dataProvider versionsProvider
    */
    public function testIsStableRelease(string $version, bool $keep_stability_flag, string $normalized, bool $stable, bool $dev): void
    {
        $version_parser = $this->newTestedInstance();
        $this->boolean($version_parser->isStableRelease($version))->isEqualTo($stable);
    }

   /**
    * @dataProvider versionsProvider
    */
    public function testIsDevVersion(string $version, bool $keep_stability_flag, string $normalized, bool $stable, bool $dev): void
    {
        $version_parser = $this->newTestedInstance();
        $this->boolean($version_parser->isDevVersion($version))->isEqualTo($dev);
    }
}
