<?php
require('config.php');
dol_include_once('pricelist/class/pricelist.class.php');
dol_include_once('pricelist/class/pricelistMassaction.class.php');
dol_include_once('pricelist/class/pricelistMassactionIgnored.class.php');
dol_include_once('abricot/includes/class/class.form.core.php');
dol_include_once('product/class/product.class.php');
dol_include_once('core/lib/admin.lib.php');

if(is_file(DOL_DOCUMENT_ROOT."/lib/product.lib.php")) dol_include_once("/lib/product.lib.php");
else dol_include_once("/core/lib/product.lib.php");

global $langs,$db;
$langs->Load("other");

$pricelist = new Pricelist($db);
$pricelistMassaction = new PricelistMassaction($db);
$pricelistMassactionsIgnored = new PricelistMassactionIgnored($db);
$form = new Form($db);
$formA = new TFormCore($db);


$id = GETPOST('id','int');
$action = GETPOST('action','alpha');
$toselect = GETPOST('toselect');
$massaction=GETPOST('massaction','alpha');
$pricelistMassaction->fetch($id);
$confirm=__get('confirm','no');

$limit = GETPOST('limit');
if ($limit != ''){
	dolibarr_set_const($db,'PRICELIST_SIZE_LISTE_LIMIT',$limit);
}
$nbLine = $conf->global->PRICELIST_SIZE_LISTE_LIMIT;

$page = (GETPOST("page", 'int')?GETPOST("page", 'int'):0);
if (empty($page) || $page == -1) { $page = 0; }

$hookmanager->initHooks(array('pricelistcard'));

/*
 *  ACTIONS
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');


// Suppression d'éléments dans la liste
if ($action == 'deleteElements' && $confirm == 'yes') {
	$TSelectedPricelist = json_decode(GETPOST('toSelectConfirm'), true);
	if (!empty($TSelectedPricelist)) {
		foreach ($TSelectedPricelist as $priceListId) {
			$pricelist->fetch($priceListId);
			$pricelist->delete($user);
		}
	}
}

// Suppression
if ($action == 'confirm_delete' && isset($id)){
	$pricelistMassaction->delete($user);
	header("Location: massactionPricelistList.php");
	exit;
}

// Edited les tableaux de données (pour ajouter les liens)
function formatArray($db,array &$TPricelist){
	$product = new Product($db);
	foreach ($TPricelist as $id => &$pricelist){
		$product->fetch($pricelist['fk_product']);
		$pricelist['product_link'] = $product->getNomUrl();
		$pricelist['product_label'] = $product->label;
	}
}

// Modified Products
$pricelistsSql = 'SELECT';
$pricelistsSql.=' rowid,';
$pricelistsSql.=' fk_product,';
$pricelistsSql.=' fk_product as product_label,';
$pricelistsSql.=' reason,';
$pricelistsSql.=' date_change,';
$pricelistsSql.=' fk_user,';
$pricelistsSql.=' fk_massaction';
$pricelistsSql.=' FROM llx_pricelist';
$pricelistsSql.=' WHERE fk_massaction='.$pricelistMassaction->id;

// Ignored Products
$pricelistIgnoredSql = 'SELECT';
$pricelistIgnoredSql.=' rowid,';
$pricelistIgnoredSql.=' fk_product,';
$pricelistIgnoredSql.=' fk_product as product_label,';
$pricelistIgnoredSql.=' fk_massaction';
$pricelistIgnoredSql.=' FROM llx_pricelist_massaction_ignored';
$pricelistIgnoredSql.=' WHERE fk_massaction='.$pricelistMassaction->id;

/*
 * VIEW
 */

$general_propreties = array(
	'view_type' => 'list'
	, 'limit' => array(
		'nbLine' => $nbLine
		,'page' => $page
	)
	, 'subQuery' => array()
	, 'link' => array()
	, 'type' => array()
	, 'search' => array()
	, 'translate' => array()

	, 'list' => array(
		'image' => 'title_products.png'
		, 'picto_precedent' => '<'
		, 'picto_suivant' => '>'
		, 'noheader' => 0
		, 'messageNothing' => $langs->trans('NoProducts')
		, 'picto_search' => img_picto('', 'search.png', '', 0)
		, 'param_url' => 'id='.$pricelistMassaction->id
		)
	,'hide' => array(
		'rowid'
		)
	, 'title' => array(
		'fk_product' => $langs->trans('Product')
		,'product_label' => $langs->trans('Label')
		)
	, 'eval' => array(
		'fk_product' => '_getObjectNomUrl(\'@val@\')'
		,'product_label' => '_getLabel(\'@val@\')'
	)
);

// Modified Products
$modified_propreties = $general_propreties;
$modified_propreties['list']['title'] = $langs->trans('ModifiedProducts');
// To allow checkboxes only if not passed
if (!$pricelistMassaction->isPassed()){
	$modified_propreties['allow-fields-select'] = true;
	$modified_propreties['list']['massactions'] = array('masssactionDeletePricelistElements' => $langs->trans('Delete'));
	$modified_propreties['title']['selectedfields'] = $toselect;
}


// Ignored Products
$ignored_propreties = $general_propreties;
$ignored_propreties['list']['title'] = $langs->trans('IgnoredProducts');

// Header
llxHeader('',$langs->trans('MassactionsPricelist'),'','');

// Condifmation Suppression
if($action == 'delete' && isset($id)){
	print $form->formconfirm("massactionPricelist.php?id=".$id, $langs->trans("DeletePricelist"), $langs->trans("ConfirmDeletePricelist"), "confirm_delete", '', 0, 1);
}

// Card
print '<table class="border" width="100%">';
// Date Demande
print '<tr>';
print '<td width="15%">'.$langs->trans("DateRequest").'</td><td colspan="2">';
print date('d/m/Y', $pricelistMassaction->date_creation);
print '</td>';
print '</tr>';
// User
$user->fetch($pricelistMassaction->fk_user);
print '<tr><td>'.$langs->trans("User").'</td><td>'.$user->getNomUrl().'</td>';
print '</tr>';
// Separation
print '<tr><td></td><td></td></tr>';
// Date de mise en application
print '<tr>';
print '<td width="15%">'.$langs->trans("EffectiveDate").'</td><td colspan="2">';
print date('d/m/Y', $pricelistMassaction->date_change);
print '</td>';
print '</tr>';
// Reason
print '<tr><td>'.$langs->trans("Reason").'</td><td>'.$pricelistMassaction->reason.'</td>';
print '</tr>';
// Changement
print '<tr><td>'.$langs->trans("Percent").'</td><td>'.vatrate($pricelistMassaction->reduc,true).'</td></tr>';
// Status (already passed or not)
print '<tr><td>'.$langs->trans("Status").'</td><td>';
if ($pricelistMassaction->isPassed()){
	print $langs->trans('Passed');
}
else {
	print $langs->trans('ToCome');
}
print '</td></tr>';
print "</table>\n";

if (!$pricelistMassaction->isPassed()){
	print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&amp;id='.$id.'">'.$langs->trans("Delete").'</a></div>';
}
else {
	print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("MassactioinPassed").'">'.$langs->trans("Delete").'</a></div>';
}

print '<div id="modifiedProducts">';
print $formA->begin_form(null,'masssactionDeletePricelistElements');
if ($massaction == 'masssactionDeletePricelistElements'){
	print '<div style="padding-top: 2em;">';
	print $formA->hidden('toSelectConfirm', dol_escape_htmltag(json_encode($toselect)));
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassDeletion"), $langs->trans("ConfirmMassDeletionQuestion", count($toselect)), "deleteElements", null, '', 0, 200, 500, 1);
	print '</div>';
}


// Valid products
$listview = new Listview($db, 'modified_products');
print $listview->render($pricelistsSql,$modified_propreties);

$formA->end();

print '</div>';
print '<div id="ignoredProducts">';

// Ignored products
$listview = new Listview($db, 'ignored_products');
print $listview->render($pricelistIgnoredSql, $ignored_propreties);

print '<div>';

llxFooter();
$db->close();


function _getObjectNomUrl($id)
{
	global $db;

	$p = new Product($db);
	$res = $p->fetch($id);
	if ($res > 0)
	{
		return $p->getNomUrl(1);
	}

	return '';
}

function _getLabel($id)
{
	global $db;

	$p = new Product($db);
	$res = $p->fetch($id);
	if ($res > 0)
	{
		return $p->label;
	}

	return '';
}
