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
$action=__get('action','list');

llxHeader('',$langs->trans('Pricelist'),'','');

$pricelist = new Pricelist($db);
$product = new Product($db);
$result=$product->fetch($fk_product);

$head=product_prepare_head($product, $user);
$titre=$langs->trans("CardProduct".$product->type);
$picto=($product->type==1?'service':'product');
dol_fiche_head($head, 'pricelisttab', $titre, 0, $picto);

/*
 *  ACTIONS
 */

if ($action == 'changePriceProduct'){

}

/*
 * VIEW
 */

// Card

$object = $product;
$form = new Form($db);
$formA = new TFormCore($db);

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
print '<tr><td>'.$langs->trans("Price").'</td><td>'.price($object->price).' HT </td></tr>';
// Status (to sell)
print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td>';
print $object->getLibStatut(2,0);
print '</td></tr>';

print "</table>\n";

print "</div>\n";

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
		<label for="reduc"><?=$langs->trans('Reduction') ?></label>
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
print $langs->trans('Reduction');
print '</td><td>';
print $formA->texte('','price_chgmt','20',null,null,'style="width:4em"'); print '%';
print '</td></tr>';

print '<tr><td width="30%">';
print $langs->trans('EffectiveDate');
print '</td><td>';
$form->select_date('','start_date',0,0,0,'date_select',1,1);
print '</td></tr>';

print '<tr><td width="30%">';
print $langs->trans('EndEffectiveDate');
print '</td><td>';
$form->select_date('','end_date',0,0,1,'date_select_end',1,1);
print '</td></tr>';

print '<tr><td width="30%">';
print $langs->trans('Motif');
print '</td><td>';
print '<textarea name="motif_changement"></textarea>';
print '</td></tr>';

print '</table>';

print '<center><br><input type="submit" class="button" value="'.$langs->trans("Apply").'" name="save">&nbsp;';

print '</form>';

//  List

dol_include_once('abricot/includes/class/class.listview.php');
$TPricelist = $pricelist->getAllByProductId($fk_product);
$listview = new Listview($db, 'pricelist_view');
$nbLine = !empty($user->conf->MAIN_SIZE_LISTE_LIMIT) ? $user->conf->MAIN_SIZE_LISTE_LIMIT : $conf->global->MAIN_SIZE_LISTE_LIMIT;

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
	, 'hide' => array(
		'rowid' // important : rowid doit exister dans la query sql pour les checkbox de massaction
	)
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
	)
	, 'title' => array(
		'rowid' => 'rowid'
		, 'date_start' => $langs->trans('DateStart')
		, 'date_end' => $langs->trans('DateEnd')
		, 'price' => $langs->trans('Price')
		, 'reduction' => $langs->trans('Reduc')
		, 'reason' => $langs->trans('Motif')
		, 'selectedfields' => '' // For massaction checkbox
	)
	, 'eval' => array()
));
?>

<?php

llxFooter();
$db->close();


