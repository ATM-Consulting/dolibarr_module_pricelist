<?php
require('config.php');
dol_include_once('pricelist/class/pricelist.class.php');
dol_include_once('/categories/class/categorie.class.php');
dol_include_once('abricot/includes/class/class.form.core.php');

if(is_file(DOL_DOCUMENT_ROOT."/lib/product.lib.php")) dol_include_once("/lib/product.lib.php");
else dol_include_once("/core/lib/product.lib.php");

global $langs;

$langs->Load("other");
$langs->Load("bank");

$fk_product = GETPOST('fk_product','int');
$toselect = GETPOST('toselect');
$save = __get('save');
$massaction=__get('massaction','list');
$action=__get('action','list');
$confirm=__get('confirm','no');

$name_chmt = GETPOST('name_chmt','text');
$reason = GETPOST('motif_changement','text');
$price_chgmt = GETPOST('price_chgmt','int');
$reduc_chgmt = GETPOST('reduc_chgmt','int');
$date_start_day = GETPOST('start_dateday','text');
$date_start_month = GETPOST('start_datemonth','text');
$date_start_year = GETPOST('start_dateyear','text');
$date_start = strtotime($date_start_year.'-'.$date_start_month.'-'.$date_start_day);
/*$date_end_day = GETPOST('end_dateday','text');
$date_end_month = GETPOST('end_datemonth','text');
$date_end_year = GETPOST('end_dateyear','text');
$date_end = strtotime($date_end_year.'-'.$date_end_month.'-'.$date_end_day);*/



$pricelist = new Pricelist($db);
$product = new Product($db);
$result=$product->fetch($fk_product);

$object = $product;
$display_confirm = 0;

$form = new Form($db);
$formA = new TFormCore($db);

/*
 *  ACTIONS
 */

$back = $_SERVER['PHP_SELF'].'?fk_product='.$fk_product;

if ($action  == 'confirmDate' && $confirm == 'yes'){
	$date_start = strtotime(GETPOST('date_start','text'));
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
	header("Location: ".$back);
	exit;
}

if ($action == 'changePriceProduct' && isset($save)){
	$now = strtotime(date("Y-m-d"));

	if ($date_start < $now){
		setEventMessage('inferiorDateError', 'errors');
	}
	else{
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
		//$pricelist->date_end =$date_end;
		if ($pricelist->lastYear($fk_product,$date_start)){
			$display_confirm = 1;
		}
		else{
			$pricelist->create($user);
			header("Location: ".$back);
			exit;
		}
	}
}

if ($action = 'massactionDeletePriceListConfirm' && $confirm == 'yes'){
	$TSelectedPricelist = GETPOST('toselect');
	if (! empty($TSelectedPricelist)){
		foreach ($TSelectedPricelist as $priceListId){
			$pricelist->fetch($priceListId);
			$pricelist->delete($user);
		}
	}
}

/*
 * VIEW
 */

// Header

llxHeader('',$langs->trans('Pricelist'),'','');
$head=product_prepare_head($product);
$titre=$langs->trans("CardProduct".$product->type);
dol_fiche_head($head, 'pricelisttab', $titre, '0');

// Card

print '<table class="border" width="100%">';

// Ref
print '<tr>';
print '<td width="15%">'.$langs->trans("Ref").'</td><td colspan="2">';
print $form->showrefnav($object,'fk_product','',1,'fk_product');
print '</td>';
print '</tr>';
// Label
print '<tr><td>'.$langs->trans("Label").'</td><td>'.(!empty($object->libelle) ? $object->libelle : $object->label).'</td>';
print '</tr>';
// TVA
print '<tr><td>'.$langs->trans("VATRate").'</td><td>'.vatrate($object->tva_tx.($object->tva_npr?'*':''),true).'</td></tr>';
// Price
print '<tr><td>'.$langs->trans("SellingPrice").'</td><td>'.price($object->price).' HT </td></tr>';
// Status (to sell)
print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td>';
print $object->getLibStatut(2,0);
print '</td></tr>';
// Description
print '<tr><td>'.$langs->trans("Description").'</td><td>'.$object->description.'</td></tr>';

print "</table>\n";

print "</div>\n";

// Confrim Form
if ($display_confirm){
	print $formA->begin_form('pricelistproduct.php?fk_product='.$fk_product,'confirmDate');
	print $formA->hidden('name_chmt',$name_chmt);
	print $formA->hidden('motif_changement',$reason);
	print $formA->hidden('price_chgmt',$price_chgmt);
	print $formA->hidden('reduc_chgmt',$reduc_chgmt);
	print $formA->hidden('date_start',$date_start_year.'-'.$date_start_month.'-'.$date_start_day);
	print $form->formconfirm('pricelistproduct.php?fk_product='.$fk_product,$langs->trans('confirmDate'),$langs->trans('confirmDateQuestion'),'confirmDate',null,'yes', 0, 200, 500, '1');
	print $formA->end_form();
}


// Form

print '<table class="notopnoleftnoright" width="100%" border="0" style="margin-bottom: 2px;" summary="">';
print '<tbody><tr>';
print '<td class="nobordernopadding" valign="middle"><div class="titre">'.$langs->trans('ProductsPipeServices').'</div></td>';
print '</tr></tbody>';
print '</table>';
print '<form action="'.$_SERVER['PHP_SELF'].'?fk_product='.$fk_product.'" method="POST">';
print '<input type="hidden" name="action" value="changePriceProduct">';
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
/*
print '<tr><td width="30%">';
print $langs->trans('EndEffectiveDate');
print '</td><td>';
$form->select_date('','end_date',0,0,1,'date_select_end',1,1);
print '</td></tr>';
*/
print '<tr><td width="30%">';
print $langs->trans('Motif');
print '</td><td>';
print '<textarea name="motif_changement"></textarea>';
print '</td></tr>';

print '</table>';

print '<center><br><input type="submit" class="button" value="'.$langs->trans("Apply").'" name="save">&nbsp;';

print '</form>';

//  List

$TPricelist = $pricelist->getAllByProductId($fk_product);

dol_include_once('abricot/includes/class/class.listview.php');
$listview = new Listview($db, 'pricelist_view');
$nbLine = !empty($user->conf->MAIN_SIZE_LISTE_LIMIT) ? $user->conf->MAIN_SIZE_LISTE_LIMIT : $conf->global->MAIN_SIZE_LISTE_LIMIT;

$formA->begin_form('massactionDeletePriceList','massactionDeletePriceList');

if ($massaction == 'massactionDeletePriceList'){
	$page = 'pricelistproduct.php?fk_product='.$fk_product.'&action=massactionDeletePriceList';
	print '<div style="padding-top: 2em;">';
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassDeletion"), $langs->trans("ConfirmMassDeletionQuestion", count($toselect)), "delete", null, '', 0, 200, 500, 1);
	print '</div>';
}

print $listview->renderArray($db, $TPricelist, array(
	'view_type' => 'list'
	, 'allow-fields-select' => true
	, 'limit' => array(
		'nbLine' => $nbLine
	)
	, 'subQuery' => array()
	, 'link' => array()
	, 'type' => array(
	)
	, 'search' => array(
//		'date_start' => array('search_type' => 'calendars', 'allow_is_null' => true)
//		, 'tms' => array('search_type' => 'calendars', 'allow_is_null' => false)
//		, 'ref' => array('search_type' => true, 'table' => 't', 'field' => 'ref')
//		, 'label' => array('search_type' => true, 'table' => array('t', 't'), 'field' => array('label')) // input text de recherche sur plusieurs champs
//		, 'status' => array('search_type' => Pricelist::$TStatus, 'to_translate' => true) // select html, la clé = le status de l'objet, 'to_translate' à true si nécessaire
	)
	, 'translate' => array()

	, 'list' => array(
		'title' => $langs->trans('PriceList')
		, 'image' => 'title_generic.png'
		, 'picto_precedent' => '<'
		, 'picto_suivant' => '>'
		, 'noheader' => 0
		, 'messageNothing' => $langs->trans('NoPriceList')
		, 'picto_search' => img_picto('', 'search.png', '', 0)
		, 'massactions' => array(
			'massactionDeletePriceList' => $langs->trans('Delete')
		)
		, 'arrayofselected' => $toselect
	)
	, 'title' => array(
		'rowid' => 'ID'
		, 'date_start' => $langs->trans('DateStart')
		//, 'date_end' => $langs->trans('DateEnd')
		, 'price' => $langs->trans('NewPrice')
		, 'reduction' => $langs->trans('Percent')
		, 'reason' => $langs->trans('Motif')
		, 'selectedfields' => '' // For massaction checkbox
	)
	, 'eval' => array()
));
?>
<?php

$formA->end();

llxFooter();
$db->close();


