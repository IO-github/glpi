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

use Glpi\RichText\RichText;
use Glpi\Toolbox\Sanitizer;
use Html;
use Toolbox;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * @since 10.0.0
 */
class DataHelpersExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
         new TwigFilter('formatted_datetime', [$this, 'getFormattedDatetime']),
         new TwigFilter('formatted_duration', [$this, 'getFormattedDuration']),
         new TwigFilter('formatted_number', [$this, 'getFormattedNumber']),
         new TwigFilter('html_to_text', [$this, 'getTextFromHtml']),
         new TwigFilter('picture_url', [$this, 'getPictureUrl']),
         new TwigFilter('relative_datetime', [$this, 'getRelativeDatetime']),
         new TwigFilter('safe_html', [$this, 'getSafeHtml'], ['is_safe' => ['html']]),
         new TwigFilter('verbatim_value', [$this, 'getVerbatimValue']),
         new TwigFilter('shortcut', [$this, 'underlineShortcutLetter'], ['is_safe' => ['html']]),
         new TwigFilter('enhanced_html', [$this, 'getEnhancedHtml'], ['is_safe' => ['html']]),
        ];
    }

   /**
    * Return date formatted to user preferred format.
    *
    * @param mixed $datetime
    *
    * @return string|null
    */
    public function getFormattedDatetime($datetime): ?string
    {
        if (!is_string($datetime)) {
            return null;
        }
        return Html::convDateTime($datetime);
    }

   /**
    * Return relative representation of given date.
    *
    * @param mixed $datetime
    *
    * @return string|null
    */
    public function getRelativeDatetime($datetime): ?string
    {
        if (!is_string($datetime)) {
            return null;
        }
        return Html::timestampToRelativeStr($datetime);
    }

   /**
    * Return human readable duration.
    *
    * @param mixed $duration
    *
    * @return string|null
    */
    public function getFormattedDuration($duration): ?string
    {
        if (!is_numeric($duration)) {
            return null;
        }
        return Html::timestampToString($duration);
    }

   /**
    * Return number formatted to user preferred format.
    *
    * @param mixed $number
    *
    * @return string
    */
    public function getFormattedNumber($number): string
    {
        return Html::formatNumber($number);
    }

   /**
    * Return URL for given picture.
    *
    * @param mixed $path
    *
    * @return null|string
    */
    public function getPictureUrl($path): ?string
    {
        if (!is_string($path)) {
            return null;
        }

        return Toolbox::getPictureUrl($path, true);
    }

   /**
    * Return string having its shortcut letter underlined.
    *
    * @param string $string
    * @param string $shortcut
    *
    * @return string
    */
    public function underlineShortcutLetter(string $string, string $shortcut_letter): string
    {
        if (empty($shortcut_letter)) {
            return $string;
        }
        return Toolbox::shortcut($string, $shortcut_letter);
    }

   /**
    * Return plain text from HTML (rich text).
    *
    * @param mixed $string             HTML string to be made safe
    * @param bool  $keep_presentation  Indicates whether the presentation elements have to be replaced by plaintext equivalents
    * @param bool  $compact            Indicates whether the output should be compact (limited line length, no links URL, ...)
    *
    * @return mixed
    */
    public function getTextFromHtml($string, bool $keep_presentation = true, bool $compact = false)
    {
        if (!is_string($string)) {
            return $string;
        }

        return RichText::getTextFromHtml($string, $keep_presentation, $compact);
    }

   /**
    * Return safe HTML (rich text).
    * Value will be made safe, whenever it has been sanitize (value fetched from DB),
    * or not (value computed during runtime).
    * Result will not be escaped, to prevent having to use `|raw` filter.
    *
    * @param mixed $string
    *
    * @return mixed
    */
    public function getSafeHtml($string)
    {
        if (!is_string($string)) {
            return $string;
        }

        return RichText::getSafeHtml($string);
    }

   /**
    * Return enhanced HTML (rich text).
    * Value will be made safe, whenever it has been sanitize (value fetched from DB),
    * or not (value computed during runtime).
    * Result will not be escaped, to prevent having to use `|raw` filter.
    *
    * @param mixed $string
    *
    * @return mixed
    */
    public function getEnhancedHtml($string, array $params = [])
    {
        if (!is_string($string)) {
            return $string;
        }

        return RichText::getEnhancedHtml($string, $params);
    }

   /**
    * Return verbatim value for an itemtype field.
    * Returned value will be unsanitized if it has been transformed by GLPI sanitizing process (value fetched from DB).
    * Twig autoescaping system will then ensure that value is correctly escaped in redered HTML.
    *
    * @param mixed  $string
    *
    * @return mixed
    */
    public function getVerbatimValue($string)
    {
        if (!is_string($string)) {
            return $string;
        }

        return Sanitizer::getVerbatimValue($string);
    }
}
