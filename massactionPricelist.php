<?php
require('config.php');
dol_include_once('pricelist/class/pricelist.class.php');
dol_include_once('pricelist/class/pricelistMassaction.class.php');
dol_include_once('pricelist/class/pricelistMassactionIgnored.class.php');
dol_include_once('abricot/includes/class/class.form.core.php');
dol_include_once('product/class/product.class.php');

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
$selectModified = GETPOST('selectModified');
$selectIgnored = GETPOST('selectIgnored');
$massaction=GETPOST('massaction','alpha');
$pricelistMassaction->fetch($id);


/*
 *  ACTIONS
 */

if($action == 'delete' && isset($id)){
	$pricelistMassaction->delete($user);
	header("Location: massactionPricelistList.php");
	exit;
}

function formatArray($db,array &$TPricelist){
	$product = new Product($db);
	foreach ($TPricelist as $id => &$pricelist){
		$product->fetch($pricelist['fk_product']);
		$pricelist['product_link'] = $product->getNomUrl();
		$pricelist['product_label'] = $product->label;
	}
}

// Valid products
$TPricelists = $pricelist->getAllOfMassaction($db,$id);
formatArray($db,$TPricelists);

// Ignored products
$TPricelistsIgnored = $pricelistMassactionsIgnored->getAllByMassaction($db,$id);
formatArray($db,$TPricelistsIgnored);

/*
 * VIEW
 */

$general_propreties = array(
	'view_type' => 'list'
	, 'allow-fields-select' => $pricelistMassaction->isPassed()
	, 'limit' => array()
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
		)
	, 'title' => array(
			'product_link' => $langs->trans('Product')
			,'product_label' => $langs->trans('Label')
		)
	, 'eval' => array()
);

// To allow checkboxes
if (!$pricelistMassaction->isPassed()){
	$general_propreties['list']['massactions'] = array('massactionDeletePriceList' => $langs->trans('Delete'));
	$general_propreties['title']['selectedfields'] = '';
}

// Modified Products
$modified_propreties = $general_propreties;
$general_propreties['list']['arrayofselected'] = $selectModified;
$modified_propreties['list']['title'] = $langs->trans('ModifiedProducts');

// Ignored Products
$ignored_propreties = $general_propreties;
$general_propreties['list']['arrayofselected'] = $selectIgnored;
$ignored_propreties['list']['title'] = $langs->trans('IgnoredProducts');

// Header
llxHeader('',$langs->trans('MassactionsPricelist'),'','');

// Card
print '<table class="border" width="100%">';
// Date
print '<tr>';
print '<td width="15%">'.$langs->trans("Date").'</td><td colspan="2">';
print date('d/m/Y', $pricelistMassaction->date_change);
print '</td>';
print '</tr>';
// Reason
print '<tr><td>'.$langs->trans("Reason").'</td><td>'.$pricelistMassaction->reason.'</td>';
print '</tr>';
// Changement
print '<tr><td>'.$langs->trans("Change").'</td><td>'.vatrate($pricelistMassaction->reduc,true).'</td></tr>';
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

print $formA->begin_form(null,'massactionDeletePriceList');

if ($massaction == 'massactionDeletePriceList'){
	var_dump($selectIgnored);
	var_dump($selectModified);
	exit;
}

// Valid products
$listview = new Listview($db, 'modified_products');
print $listview->renderArray($db, $TPricelists,$modified_propreties);

print $formA->end_form();
print $formA->begin_form(null,'massactionDeletePriceList');

// Ignored products
$listview = new Listview($db, 'ignored_products');
print $listview->renderArray($db, $TPricelistsIgnored, $ignored_propreties);

print $formA->end_form();

llxFooter();
$db->close();
