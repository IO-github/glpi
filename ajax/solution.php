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

use Glpi\Http\Response;
use Glpi\RichText\RichText;

$AJAX_INCLUDE = 1;

include('../inc/includes.php');
header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

// Mandatory parameter: solutiontemplates_id
$solutiontemplates_id = $_POST['solutiontemplates_id'] ?? null;
if ($solutiontemplates_id === null) {
    Response::sendError(400, "Missing or invalid parameter: 'solutiontemplates_id'");
} else if ($solutiontemplates_id == 0) {
   // Reset form
    echo json_encode([
      'content' => ""
    ]);
    die;
}

// We can't render the twig template at this state for some cases (e.g. massive
// actions: we don't have one but multiple items so it net possible to parse the
// values yet).
$apply_twig = true;

// Mandatory parameter: items_id
$parents_id = $_POST['items_id'] ?? 0;
if ($parents_id == 0) {
    $apply_twig  = false;
}

// Mandatory parameter: itemtype
$parents_itemtype = $_POST['itemtype'] ?? '';
if (empty($parents_itemtype) || !is_subclass_of($parents_itemtype, CommonITILObject::class)) {
    $apply_twig  = false;
}

// Load solution template
$template = new SolutionTemplate();
if (!$template->getFromDB($solutiontemplates_id)) {
    Response::sendError(400, "Unable to load template: $solutiontemplates_id");
}

if ($apply_twig) {
   // Load parent item
    $parent = new $parents_itemtype();
    if (!$parent->getFromDB($parents_id)) {
        Response::sendError(400, "Unable to load parent item: $parents_itemtype $parents_id");
    }

   // Render template content using twig
    $template->fields['content'] = $template->getRenderedContent($parent);
} else {
    $template->fields['content'] = RichText::getSafeHtml($template->fields['content']);
}

// Return json response with the template fields
echo json_encode($template->fields);
