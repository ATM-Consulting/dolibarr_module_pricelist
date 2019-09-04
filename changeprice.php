<?php
/* Copyright (C) 2019 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

if(empty($user->rights->produit->creer)) accessforbidden();
$permissiondellink = $user->rights->webhost->write;	// Used by the include of actions_dellink.inc.php

$langs->load('pricelist@pricelist');

$action = GETPOST('action');

$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'pricelistcard';   // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');

if (!empty($id) || !empty($ref)) $object->fetch($id, true, $ref);

$hookmanager->initHooks(array('pricelistchangeprice','pricelist'));


if ($object->isextrafieldmanaged)
{
	$extrafields = new ExtraFields($db);

	$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
	$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');
}

/*
 * Actions
 */

$parameters = array('id' => $id, 'ref' => $ref);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// Si vide alors le comportement n'est pas remplacé
if (empty($reshook))
{

	if ($cancel)
	{
		if (! empty($backtopage))
		{
			header("Location: ".$backtopage);
			exit;
		}
		$action='';
	}

	// For object linked
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be include, not include_once




	$error = 0;
	switch ($action) {
		case 'save':
		case 'back':
	}
}


/**
 * View
 */
$form = new Form($db);

$title=$langs->trans('changePrice');
llxHeader('', $title);

print '<table class="notopnoleftnoright" width="100%" border="0" style="margin-bottom: 2px;" summary="">';
print '<tbody><tr>';
print '<td class="nobordernopadding" valign="middle"><div class="titre">'.$langs->trans('ProductsPipeServices').'</div></td>';
print '</tr></tbody>';
print '</table>';
print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
print '<input type="hidden" name="action" value="changePrice">';
print '<table class="border" width="100%">';


print '<tr><td width="30%">';
print $langs->trans('DateBeginTarif');
print '</td><td>';
$form->select_date('','re',0,0,0,'date_select',1,1);
print '</td></tr>';

print '<tr><td width="30%">';
print $langs->trans('MotifChangement');
print '</td><td>';
print '<textarea name="motif_changement"></textarea>';
print '</td></tr>';

print '<tr><td width="30%">';
print $langs->trans('MotifChangement');
print '</td><td>';
print '<textarea name="motif_changement"></textarea>';
print '</td></tr>';

print '<tr><td width="30%">';
print $langs->trans('MotifChangement');
print '</td><td>';
print '<textarea name="motif_changement"></textarea>';
print '</td></tr>';

print '<tr><td width="30%">';
print $langs->trans('MotifChangement');
print '</td><td>';
print '<textarea name="motif_changement"></textarea>';
print '</td></tr>';

print '</table>';

print '<center><br><input type="submit" class="button" value="'.$langs->trans("Valid").'" name="save">&nbsp;';
print '<input type="submit" class="button" value="Annuler" name="back"></center>';

print '</form>';

llxFooter();
$db->close();
