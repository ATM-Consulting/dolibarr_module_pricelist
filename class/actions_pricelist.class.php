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

/**
 * \file    class/actions_pricelist.class.php
 * \ingroup pricelist
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class Actionspricelist
 */
class Actionspricelist
{
	/**
	 * @var DoliDb        Database handler (result of a new DoliDB)
	 */
	public $db;

	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	private $massactionChangePrice = array();

	/**
	 * Constructor
	 * @param DoliDB $db Database connector
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/** Add option in massaction of lists
	 * @param $parameters
	 * @param $object
	 * @param $action
	 * @param $hookmanager
	 * @return int
	 */
	public function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs;
		$langs->load('pricelist@pricelist');

		if (strpos($parameters['context'], 'productservicelist') !== false) {
			$this->resprints = '<option value="PriceListChangePrice">' . $langs->trans("PriceListChangePrice") . '</option>';
		}

		return 0; // or return 1 to replace standard code
	}

	/** Functions related to massaction
	 * @param $parameters
	 * @param $object
	 * @param $action
	 * @param $hookmanager
	 * @return int
	 */
	public function doMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $massaction;
		$langs->load('pricelist@pricelist');

		if (strpos($parameters['context'], 'productservicelist') !== false)
		{
			if ($massaction == 'PriceListChangePrice'){
				$this->massactionChangePrice['changePrice'] = 1;
				$this->massactionChangePrice['massaction'] = $massaction;
				$this->massactionChangePrice['toselect'] = $parameters['toselect'];
			}
		}
		return 0;
	}

	/** Hook used to display form and do action linked to massaction
	 * @param $parameters
	 * @param $object
	 * @param $action
	 * @param $hookmanager
	 */
	public function printFieldPreListTitle($parameters, &$object, &$action, $hookmanager){
		$confirmChangePrice = GETPOST('confirmChangePrice');
		if ($this->massactionChangePrice['changePrice'] && ! $confirmChangePrice){
			print $this->displayFormChangePrice();
		}
		if($confirmChangePrice){
			$toselect = GETPOST('toselect');
			$this->changePriceMassaction();
		}
	}

	/** Fromulaire de changement de prix (massaction)
	 * @return string
	 */
	private function displayFormChangePrice(){
		dol_include_once('abricot/includes/class/class.form.core.php');
		global $db, $langs;
		$formA = new TFormCore($db);
		$form = new Form($db);
		$res = '<div class="formChangePrice">
			<form action="'.$_SERVER['PHP_SELF'].'" method="POST">
				<input type="hidden" name="massaction" value='.$this->massactionChangePrice['massaction'].'>
				<input type="hidden" name="toselect" value="'.htmlspecialchars(json_encode($this->massactionChangePrice['toselect'])).'">
				<input type="hidden" name="confirmChangePrice" value="confirmChangePrice">
				<table class="valid centpercent" width="100%">
					<tr calss="validtitre">
						<td class="validtitre">
							Modification du prix de vente
						</td>
					</tr>
					<tr>
						<td>'.$langs->trans('Percent').'</td>
						<td>
							'.$formA->texte('','reduc_chgmt','20',null,null,'style="width:4em" required="required"').'%
						</td>
					</tr>
					<tr>
						<td>'.$langs->trans('EffectiveDate').'</td>
						<td>
							'.$form->select_date('','date_change',0,0,0,'date_select',1,1,1).'
						</td>
					</tr>
					<tr>
						<td>'.$langs->trans('Motif').'</td>
						<td>
							<textarea name="motif_changement" required="required"></textarea>
						</td>
					</tr>
					<tr>
						<td>
							<input type="submit" class="butAction" value="'.$langs->trans("Valid").'" name="confirmChangePriceMassAction">
						</td>
					</tr>
				</table>
		</div>
		<br/>';
		return $res;
	}

	/**
	 * Action liée au formulaire
	 */
	private function changePriceMassaction(){
		dol_include_once('pricelist/class/pricelist.class.php');
		dol_include_once('pricelist/class/pricelistMassaction.class.php');
		dol_include_once('pricelist/class/pricelistMassactionIgnored.class.php');
		global $db, $user,$langs;

		//Tableau de produits à modifier
		$TIDproducts = GETPOST('toselect');
		//Réduction (en %)
		$percent = GETPOST('reduc_chgmt','int');
		//Motif de changement de prix
		$reason = GETPOST('motif_changement','alpha');
		// Date prévue du changement (jj/mm/aaaa)
		$date_change = GETPOST('date_change','alpha');
		$date_change = str_replace('/', '-', $date_change);
		$date_change = date('Y-m-d', strtotime($date_change));

		$pricelistMassaction = new PricelistMassaction($db);

		$updated = 0;
		$ignored = 0;

		$pricelistMassaction->reduc = $percent;
		$pricelistMassaction->reason = $reason;
		$pricelistMassaction->date_change = $date_change;
		$pricelistMassaction->fk_user = $user->id;

		$plMassactionId = $pricelistMassaction->create($user);

		foreach ($TIDproducts as $product_id){
			if(Pricelist::checkDate($db,$product_id,$date_change)){
				$pricelist = new Pricelist($db);
				$pricelist->fk_product = $product_id;
				$pricelist->reduc = $percent;
				$pricelist->reason = $reason;
				$pricelist->date_change = $date_change;
				$pricelist->fk_massaction = $plMassactionId;
				$pricelist->create($user);
				$updated++;
			}
			else {
				$pricelistMassactionIgnored = new PricelistMassactionIgnored($db);
				$pricelistMassactionIgnored->fk_product = $product_id;
				$pricelistMassactionIgnored->fk_massaction = $plMassactionId;
				$pricelistMassactionIgnored->create($user);
				$ignored++;
			}
		}
		setEventMessage($langs->trans('massactionUpdateMessage',$updated,$ignored));
	}
}
