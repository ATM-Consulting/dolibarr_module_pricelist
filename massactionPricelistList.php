<?php
require('config.php');
dol_include_once('pricelist/class/pricelist.class.php');
dol_include_once('pricelist/class/pricelistMassaction.class.php');
dol_include_once('pricelist/class/pricelistMassactionIgnored.class.php');
dol_include_once('abricot/includes/class/class.form.core.php');
dol_include_once('abricot/includes/class/class.listview.php');
dol_include_once('core/lib/admin.lib.php');


if(is_file(DOL_DOCUMENT_ROOT."/lib/product.lib.php")) dol_include_once("/lib/product.lib.php");
else dol_include_once("/core/lib/product.lib.php");


global $langs;
$langs->Load("other");
$langs->Load("pricelist@pricelist");

$pricelist = new Pricelist($db);
$pricelistMassactions = new PricelistMassaction($db);
$pricelistMassactionsIgnored = new PricelistMassactionIgnored($db);

$form = new Form($db);
$formA = new TFormCore($db);

$toselect = GETPOST('toselect');
$save = __get('save');
$massaction=__get('massaction','list');
$action=__get('action','list');

$limit = GETPOST('limit');
if ($limit != ''){
	dolibarr_set_const($db,'PRICELIST_MASSACTION_SIZE_LISTE_LIMIT',$limit);
}
$nbLine = $conf->global->PRICELIST_MASSACTION_SIZE_LISTE_LIMIT;

$page = (GETPOST("page", 'int')?GETPOST("page", 'int'):0);
if (empty($page) || $page == -1) { $page = 0; }

/*
 *  ACTIONS
 */


/*
 * VIEW
 */

// Header
llxHeader('',$langs->trans('MassactionsPricelist'),'','');

$listview = new Listview($db, 'massaction_view');
$nbLine = !empty($limit) ? $limit : $nbLine;

$massactions = new PricelistMassaction($db);
$TMassactions = $massactions->fetchAll();


foreach ($TMassactions as $id => $massaction){
	$TMassactions[$id]->ref = $massaction->getNomURL();
}

print $listview->renderArray($db, $TMassactions, array(
	'view_type' => 'list'
	, 'allow-fields-select' => false
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
			'title' => $langs->trans('PriceListMassactions')
		, 'image' => 'title_generic.png'
		, 'picto_precedent' => '<'
		, 'picto_suivant' => '>'
		, 'noheader' => 0
		, 'messageNothing' => $langs->trans('NoPriceListMassactions')
		, 'picto_search' => img_picto('', 'search.png', '', 0)
		)
	, 'title' => array(
		'ref' => $langs->trans('EffectiveDate')
		, 'reduc' => $langs->trans('PercentList')
		, 'reason' => $langs->trans('Motif')
		)
	, 'eval' => array()
));



llxFooter();
$db->close();
