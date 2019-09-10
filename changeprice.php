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
}


/**
 * View
 */
$form = new Form($db);
$formA = new TFormCore($db);

$title=$langs->trans('changePrice');
llxHeader('', $title);

/*
 *  ACTIONS
 */

if ($action == 'changePriceAll' && isset($save)){
	$now = strtotime(date("Y-m-d"));

	$name_chmt = GETPOST('name_chmt','text');
	$reason = GETPOST('motif_changement','text');
	$price_chgmt = GETPOST('price_chgmt','int');
	$reduc_chgmt = GETPOST('reduc_chgmt','int');
	$date_start_day = GETPOST('start_dateday','text');
	$date_start_month = GETPOST('start_datemonth','text');
	$date_start_year = GETPOST('start_dateyear','text');
	$date_start = strtotime($date_start_year.'-'.$date_start_month.'-'.$date_start_day);

	if ($date_start < $now){
		setEventMessage('inferiorDateError', 'errors');
	}
	else{
		$form->form_confirm('','','','','','','','','');
		/*
		$pricelist->fk_product = $fk_product;

		$pricelist->reduction = '';
		$pricelist->price = '';

		if ($name_chmt == 'reduc') {
			$pricelist->reduction = $reduc_chgmt;
		}
		if ($name_chmt == 'price') {
			$pricelist->price = $price_chgmt;
		}

		$pricelist->reason =$reason;
		$pricelist->date_start =$date_start;

		$pricelist->create($user);
		*/
	}
}

/*
 * VIEW
 */

print '<table class="notopnoleftnoright" width="100%" border="0" style="margin-bottom: 2px;" summary="">';
print '<tbody><tr>';
print '<td class="nobordernopadding" valign="middle"><div class="titre">'.$langs->trans('ChangePrice').'</div></td>';
print '</tr></tbody>';
print '</table>';
print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
print '<input type="hidden" name="action" value="changePriceAll">';
print '<table class="border" width="100%">';

print '<tr><td width="30%">';
print $langs->trans('TypeChange');
print '</td><td>';
?>
	<input type="radio" id="id_reduc" name="name_chmt" value="reduc" onchange="handleChange();" checked>
	<label for="reduc"><?=$langs->trans('Percent') ?></label>
	<input type="radio" id="id_price" name="name_chmt" value="price" onchange="handleChange();">
	<label for="price"><?=$langs->trans('Price') ?></label>

	<script>
        $(document).ready(function () {
            $('.input_price').hide();
        });
        function handleChange() {
            if ($('#id_reduc').prop('checked')) {
                $('.input_price').hide();
                $('.input_reduc').show();
            } else {
                $('.input_price').show();
                $('.input_reduc').hide();
            }
        }
	</script>
<?php
print '</td></tr>';

print '<tr class = "input_price"><td width="30%">';
print $langs->trans('Price');
print '</td><td>';
print $formA->texte('','price_chgmt','0',null,null,'style="width:4em"'); print 'HT';
print '</td></tr>';

print '<tr class = "input_reduc"><td width="30%">';
print $langs->trans('Percent');
print '</td><td>';
print $formA->texte('','reduc_chgmt','20',null,null,'style="width:4em"'); print '%';
print '</td></tr>';

print '<tr><td width="30%">';
print $langs->trans('EffectiveDate');
print '</td><td>';
$form->select_date('','start_date',0,0,0,'date_select',1,1);
print '</td></tr>';

print '<tr><td width="30%">';
print $langs->trans('Motif');
print '</td><td>';
print '<textarea name="motif_changement"></textarea>';
print '</td></tr>';

print '</table>';

print '<center><br><input type="submit" class="button" value="'.$langs->trans("Apply").'" name="save">&nbsp;';

print '</form>';

$formA->end();

llxFooter();
$db->close();
