<?php
require('config.php');
dol_include_once('pricelist/class/pricelist.class.php');
dol_include_once('/categories/class/categorie.class.php');
dol_include_once('abricot/includes/class/class.form.core.php');
dol_include_once('core/lib/admin.lib.php');

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

$type_chmt = GETPOST('type_chmt','text');
$reason = GETPOST('motif_changement','text');
$price_chgmt = GETPOST('price_chgmt','int');
$reduc_chgmt = GETPOST('reduc_chgmt','int');

$date_change = GETPOST('date_change','text');
$date_change = str_replace('/', '-', $date_change);
$date_change = date('Y-m-d', strtotime($date_change));

$pricelist = new Pricelist($db);
$product = new Product($db);
$result=$product->fetch($fk_product);
$hookmanager->initHooks(array('pricelistproduct'));

$object = $product;
$display_confirm = 0;
$now = strtotime(date("Y-m-d"));

$form = new Form($db);
$formA = new TFormCore($db);

$limit = GETPOST('limit');
if ($limit != ''){
	dolibarr_set_const($db,'PRICELISTPRODUCT_SIZE_LISTE_LIMIT',$limit);
}
$nbLine = $conf->global->PRICELISTPRODUCT_SIZE_LISTE_LIMIT;

$page = (GETPOST("page", 'int')?GETPOST("page", 'int'):0);
if (empty($page) || $page == -1) { $page = 0; }

/*
 *  ACTIONS
 */

$back = $_SERVER['PHP_SELF'].'?fk_product='.$fk_product;

// Pricelist form
if ($action == 'changePriceProduct' && isset($save)){
	if (strtotime($date_change) < $now){ // Date OK
		setEventMessage('inferiorDateError', 'errors');
	}
	else{
		$pricelist->fk_product = $fk_product;

		$pricelist->reduction = '';
		$pricelist->price = '';

		if ($type_chmt == 'reduc') {
			$pricelist->reduc = $reduc_chgmt;
		}
		if ($type_chmt == 'price') {
			$pricelist->price = $price_chgmt;
		}

		$pricelist->reason = $reason;
		$pricelist->date_change = $date_change;

		if ($pricelist->checkDate($db,$fk_product,$date_change)){
			$pricelist->create($user);
			header("Location: ".$back);
			exit;
		}
		else{
			$display_confirm = 1;
		}
	}
}

// Confirm change price when already changed less than 1 year ago
if ($action  == 'confirmDate' && $confirm == 'yes'){
	$pricelist->fk_product = $fk_product;

	$pricelist->reduc = '';
	$pricelist->price = '';

	if ($type_chmt == 'reduc') {
		$pricelist->reduc = $reduc_chgmt;
	}
	if ($type_chmt == 'price') {
		$pricelist->price = $price_chgmt;
	}

	$pricelist->reason = $reason;
	$pricelist->date_change = $date_change;
	$pricelist->create($user);
	header("Location: ".$back);
	exit;
}


// Massaction delete
if ($action = 'massactionDeletePriceListConfirm' && $confirm == 'yes'){
	$TSelectedPricelist = json_decode(GETPOST('toSelectConfirm'),true);
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
	print $formA->hidden('type_chmt',$type_chmt);
	print $formA->hidden('motif_changement',$reason);
	print $formA->hidden('price_chgmt',$price_chgmt);
	print $formA->hidden('reduc_chgmt',$reduc_chgmt);
	print $formA->hidden('date_change',$date_change);
	print $form->formconfirm('pricelistproduct.php?fk_product='.$fk_product,$langs->trans('confirmDate'),$langs->trans('confirmDateQuestion'),'confirmDate',null,'yes', 0, 200, 500, '1');
	print $formA->end_form();
}


// Form
// Form Header
print '<table class="notopnoleftnoright" width="100%" border="0" style="margin-bottom: 2px;" summary="">';
print '<tbody><tr>';
print '<td class="nobordernopadding" valign="middle"><div class="titre">'.$langs->trans('Pricelist').'</div></td>';
print '</tr></tbody>';
print '</table>';
print '<form id="new_rpricelist" action="'.$_SERVER['PHP_SELF'].'?fk_product='.$fk_product.'" method="POST">';
print '<input type="hidden" name="action" value="changePriceProduct">';
print '<table class="border" width="100%">';

// Type de changement
print '<tr><td width="30%">';
print $langs->trans('TypeChange');
print '</td><td>';
?>
		<input type="radio" id="id_reduc" name="type_chmt" value="reduc" onchange="handleChange();" checked>
		<label for="reduc"><?=$langs->trans('Percent') ?></label>
		<input type="radio" id="id_price" name="type_chmt" value="price" onchange="handleChange();">
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

// Prix
print '<tr class = "input_price"><td width="30%">';
print $langs->trans('Price');
print '</td><td>';
print $formA->texte('','price_chgmt','0',null,null,'required="required" style="width:4em"'); print 'HT';
print '</td></tr>';

// Réduc
print '<tr class = "input_reduc"><td width="30%">';
print $langs->trans('Percent');
print '</td><td>';
print $formA->texte('','reduc_chgmt','20',null,null,'required="required" style="width:4em"'); print '%';
print '</td></tr>';

// Date de début
print '<tr><td width="30%">';
print $langs->trans('EffectiveDate');
print '</td><td>';
$form->select_date('','date_change',0,0,0,'required="required" date_select',1,1);
print '</td></tr>';

// Motif
print '<tr><td width="30%">';
print $langs->trans('Motif');
print '</td><td>';
print '<textarea name="motif_changement" required="required"></textarea>';
print '</td></tr>';

print '</table>';
//Confirm
print '<center><br><input type="submit" class="button" value="'.$langs->trans("Apply").'" name="save">&nbsp;</center>';
print '</form>';

//  List
print '<div id="list-pricelist">';

$sql = 'SELECT';
$sql.= ' rowid,';
$sql.= ' date_creation,';
$sql.= ' price,';
$sql.= ' reduc,';
$sql.= ' reason,';
$sql.= ' date_change,';
$sql.= ' fk_user,';
$sql.= ' fk_massaction';
$sql.= ' FROM '.MAIN_DB_PREFIX.'pricelist';
$sql.= ' WHERE fk_product='.$fk_product;
$sql.= ' AND entity='.getEntity('products');
$sql.= ' ORDER BY date_change DESC';

dol_include_once('abricot/includes/class/class.listview.php');
$listview = new Listview($db, 'pricelist_view');

print $formA->begin_form(null,'massactionDeletePriceList');

if ($massaction == 'massactionDeletePriceList'){
	$page = 'pricelistproduct.php?fk_product='.$fk_product.'&action=massactionDeletePriceList';
	print '<div style="padding-top: 2em;">';
	print $formA->hidden('toSelectConfirm', dol_escape_htmltag(json_encode($toselect)));
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassDeletion"), $langs->trans("ConfirmMassDeletionQuestion", count($toselect)), "delete", null, '', 0, 200, 500, 1);
	print '</div>';
}

$listConfig = array(
	'view_type' => 'list'
	, 'allow-fields-select' => true
	, 'limit' => array(
			'nbLine' => $nbLine
			,'page'
		)
	, 'subQuery' => array()
	, 'link' => array()
	, 'type' => array(
		'date_change' => 'date'
		,'date_creation' => 'date'
	)
	, 'search' => array()
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
		, 'date_creation' => $langs->trans('DateRequest')
		, 'date_change' => $langs->trans('EffectiveDate')
		, 'price' => $langs->trans('ChangePrice')
		, 'reduc' => $langs->trans('PercentList')
		, 'reason' => $langs->trans('Motif')
		, 'selectedfields' => '' // For massaction checkbox
		)
	, 'eval' => array(
		'reduc' => 'computeReduc(\'@reduc@\')'
	)
);

print $listview->render($sql,$listConfig);

$formA->end();
print '</div>';

llxFooter();
$db->close();

function computeReduc($value){
	if ($value == '' || $value == 0) return "/";
	return $value;
}

