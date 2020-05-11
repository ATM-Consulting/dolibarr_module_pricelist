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

$sql = 'SELECT ';
$sql.= ' m.rowid,';
$sql.= ' m.reduc,';
$sql.= ' m.reason,';
$sql.= ' m.date_creation,';
$sql.= ' m.fk_user,';
$sql.= ' m.date_change,';
$sql.= ' count(distinct p.rowid) as nbok,';
$sql.= ' count(distinct i.rowid) as nbko';
$sql.= ' FROM '.MAIN_DB_PREFIX.'pricelist_massaction m';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'pricelist p ON m.rowid = p.fk_massaction';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'pricelist_massaction_ignored i ON m.rowid = i.fk_massaction';
$sql.= ' GROUP BY(m.rowid)';




$listConfig = array(
	'view_type' => 'list' // default = [list], [raw], [chart]
	,'allow-fields-select' => false
	,'limit'=>array(
			'nbLine' => $nbLine
			,'page'
		)
	,'list' => array(
			'title' => $langs->trans('PriceListMassactions')
		,'image' => 'title_generic.png'
		,'picto_precedent' => '<'
		,'picto_suivant' => '>'
		,'noheader' => 0
		,'messageNothing' => $langs->trans('NoPriceListMassactions')
		,'picto_search' => img_picto('', 'search.png', '', 0)
		,'massactions'=>array()
		)
	,'subQuery' => array()
	,'link' => array(

	)
	,'type' => array(
			'date_change' => 'date'
			,'date_creation' => 'date'
		)
	,'search' => array(
		'date_change' => array('search_type' => 'calendars', 'allow_is_null' => true)
		)
	,'translate' => array()
	,'hide' => array(
			'rowid' // important : rowid doit exister dans la query sql pour les checkbox de massaction
		)
	,'title'=>array(
		'date_change' => $langs->trans('EffectiveDate')
		, 'nbok' => $langs->trans('NbProductsOK')
		, 'nbko' => $langs->trans('NbProductsKO')
		, 'date_creation' => $langs->trans('DateRequest')
		, 'fk_user' => $langs->trans('User')
		, 'reduc' => $langs->trans('PercentList')
		, 'reason' => $langs->trans('Motif')
	)
	,'eval'=>array(
		'date_change' => 'getNomUrlMassaction(@rowid@)'
		,'fk_user' => '_getUserNomUrl(@val@)'
		)
);


print '<div id="list_massactions">';
print $formA->begin_form(null,'masssactionDeletePricelistElements');

print $listview->render($sql,$listConfig);

print $formA->end();
print '</div>';

llxFooter();
$db->close();

function getNomUrlMassaction($id){
	global $db;
	$pricelistMassactions = new PricelistMassaction($db);
	$pricelistMassactions->fetch($id);
	return $pricelistMassactions->getNomURL();
}

function _getUserNomUrl($fk_user)
{
	global $db;
	$u = new User($db);
	if ($u->fetch($fk_user) > 0)
	{
		return $u->getNomUrl(1);
	}
	return '';
}
