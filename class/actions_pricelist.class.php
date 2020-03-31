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

		$error = 0; // Error counter

		if (strpos($parameters['context'], 'productservicelist') !== false) {
			$this->resprints = '<option value="PriceListChangePrice">' . $langs->trans("PriceListChangePrice") . '</option>';
		}

		if (!$error) {
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
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
		dol_include_once('abricot/includes/class/class.form.core.php');
		global $user, $db, $langs, $massaction, $conf;
		$langs->load('pricelist@pricelist');

		$error = 0; // Error counter

		if (strpos($parameters['context'], 'productservicelist') !== false)
		{
			if ($massaction == 'PriceListChangePrice'){

				$this->massactionChangePrice['changePrice'] = 1;
				$this->massactionChangePrice['massaction'] = $massaction;
				$this->massactionChangePrice['toselect'] = $parameters['toselect'];
			}
		}

		if (! $error) {
			return 0; // or return 1 to replace standard code
		} else {
			return -1;
		}
	}

	/** Action change Price
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

	/**
	 * Formulaire changement de prix en masse
	 * @return string
	 */
	private function displayFormChangePrice(){
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
							'.$formA->texte('','reduc_chgmt','20',null,null,'required="required"; style="width:4em"').'%
						</td>
					</tr>
					<tr>
						<td>'.$langs->trans('EffectiveDate').'</td>
						<td>
							'.$form->select_date('','start_date',0,0,0,'date_select',1,1,1).'
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
	 * Action changement de prix en masse
	 */
	private function changePriceMassaction(){
		dol_include_once('pricelist/class/pricelist.class.php');
		global $langs, $db, $user;

		$Tproducts = GETPOST('toselect');
		$percent = GETPOST('reduc_chgmt','int');

		$reason = GETPOST('motif_changement','alpha');
		if($reason == "") $reason = $langs->trans('MassActionChangePrice');

		$changeDate = GETPOST('start_date','alpha');
		$changeDate = date('Y-m-d',strtotime($changeDate));

		$today = strtotime(date("Y-m-d"));

		$product = new Product($db);
		$pricelist = new Pricelist($db);

		$updated = 0;
		$ignored = 0;

		foreach ($Tproducts as $productID){
			$product->fetch($productID);
			// Derniere Date de modification du prix ('' si aucune)
			$lastPriceUpdate = $product->array_options['options_last_date_price'];

			$next = strtotime(date('Y',$lastPriceUpdate) + 1 . '-' . date('m-d',$lastPriceUpdate));

			if (!$next || $next <= $today){
				$pricelist->fk_product = $productID;
				$pricelist->reduction = $percent;
				$pricelist->date_start = strtotime($changeDate);
				$pricelist->reason = $reason;
				$pricelist->create($user);
				$updated++;
			}
			else {
				$ignored++;
			}
		}
		setEventMessage($langs->trans('massactionUpdateMessage',$updated,$ignored));
	}
}
