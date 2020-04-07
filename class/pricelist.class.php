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

if (!class_exists('SeedObject'))
{
	/**
	 * Needed if $form->showLinkedObjectBlock() is call or for session timeout on our module page
	 */
	define('INC_FROM_DOLIBARR', true);
	require_once dirname(__FILE__).'/../config.php';
}

include_once DOL_DOCUMENT_ROOT .'/cron/class/cronjob.class.php';

class Pricelist extends SeedObject
{
	/** @var string $table_element Table name in SQL */
	public $table_element = 'pricelist';

	/** @var string $table_element Table name in SQL */
	public $element = 'pricelist';

	public $fields = array(
		'entity'=>array('type'=>'int'),
		'fk_product'=>array('type'=>'int'),
		'price'=>array('type'=>'double'),
		'reduc'=>array('type'=>'integer'),
		'reason'=>array('type'=>'text'),
		'date_change'=>array('type'=>'datetime'),
		'fk_user'=>array('type'=>'integer'),
		'fk_massaction'=>array('type'=>'integer')
	);

	/**
	 * pricelist constructor.
	 * @param DoliDB $db Database connector
	 */
	public function __construct($db)
	{
		global $conf;
		$this->db = $db;
		$this->entity = $conf->entity;
		$this->init();
	}

	/**
	 * @param	User	$user		User object
	 * @param 	bool 	$notrigger  false=launch triggers after, true=disable triggers
	 * @return  int
	 */
	public function save($user, $notrigger = false)
	{
		return $this->create($user, $notrigger);
	}

	/**
	 * Delete pricelist
	 * @param   User	$user		User object
	 * @param 	bool 	$notrigger  false=launch triggers after, true=disable triggers
	 * @return  int
	 */
	public function delete(User &$user, $notrigger = false)
	{
		$this->deleteObjectLinked();

		unset($this->fk_element); // avoid conflict with standard Dolibarr comportment
		return parent::delete($user, $notrigger);
	}

	/** Delete all Pricelists of a Product
	 * @param User $user
	 * @param $fk_product
	 * @return int
	 */
	public function deleteAllOfProduct(User &$user, $fk_product){
		$TPriceLists = $this->getAllByProductId($this->db, $fk_product);
		foreach ($TPriceLists as $priceList) {
			$this->fetch($priceList['rowid']);
			if ($this->delete($user) < 1)
				return -1;
		}
		return 1;
	}

	/**
	 * Create Pricelist // If start date is today, immediately changes the price of the product
	 * @param	User	$user		User registered
	 * @param 	bool 	$notrigger  false=launch triggers after, true=disable triggers
	 * @return	int					return id
	 */
	public function create(User &$user, $notrigger = false){
		$this->fk_user = $user->id;
		$this->entity = getEntity('products');
		$now = strtotime(date("Y-m-d"));

		if (strtotime($this->date_change) < $now){
			return -1;
		}
		if ($this->date_change == date('Y-m-d',$now)){ //Change immediatly the price
			$this->updatePricePricelist();
		}
		return parent::create($user, $notrigger);
	}

	/**
	 * updatePricePricelist function // Change Price according to Pricelist
	 */
	private function updatePricePricelist(){
		$user = new User($this->db);
		$user->fetch($this->fk_user);

		$product = new Product($this->db);
		$product->fetch($this->fk_product);

		if ($this->reduc != ''){ // Changement en %
			$new_price_min = $product->price_min + $product->price_min * $this->reduc/100;
			$new_price = $product->price + $product->price * $this->reduc/100;
		}
		else { // Changement prix de vente
			$new_price_min = $this->price;
			$new_price = $this->price;
		}
		$product->updatePrice($new_price, 'HT', $user,'',$new_price_min);

		// Changement extrafield correspondant
		$product->array_options['options_last_date_price'] = $this->date_change;
		$product->updateExtraField('last_date_price');

	}

	/**
	 * Get all pricelists according to a product ID
	 * @param $db DBHandler
	 * @param $productID Product linked
	 * @return array of pricelists (sorted by date change price
	 */
	public static function getAllByProductId($db, $productID){
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
		$sql.= ' WHERE fk_product='.$productID;
		$sql.= ' AND entity='.getEntity('products');
		$sql.= ' ORDER BY date_change DESC';

		$TPricelist = array();

		$resql=$db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				if ($obj)
				{
					$TPricelist[$obj->rowid]['rowid'] = $obj->rowid;
					$TPricelist[$obj->rowid]['date_creation'] = date("d/m/Y",strtotime($obj->date_creation));
					$TPricelist[$obj->rowid]['price'] = $obj->price;
					$TPricelist[$obj->rowid]['reduc'] = $obj->reduc;
					$TPricelist[$obj->rowid]['reason'] = $obj->reason;
					$TPricelist[$obj->rowid]['date_change'] = date("d/m/Y",strtotime($obj->date_change));
					$TPricelist[$obj->rowid]['fk_user'] = $obj->fk_user;
					$TPricelist[$obj->rowid]['fk_massaction'] = $obj->fk_massaction;
				}
				$i++;
			}
		}
		return $TPricelist;
	}

	/**
	 * Get all the Pricelists with today's date as beginning date
	 * @return array of rowid of pricelists
	 */
	public function getAllToday(){
		$now = date("Y-m-d").' 00:00:00';

		$sql = 'SELECT';
		$sql.= ' rowid,';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element;
		$sql.= ' WHERE date_change="'.$now.'"';
		$sql.= ' AND entity='.getEntity('products');

		$TPricelist = array();

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				if ($obj)
				{
					$TPricelist[$obj->rowid]= $obj->rowid;
				}
				$i++;
			}
		}
		return $TPricelist;
	}

	/**
	 * Cron : update all prices according to pricelist
	 * @return int 0 = success
	 */
	public function runUpdatePricelist(){
		$TPriceList = $this->getAllToday();
		$i = 0;
		foreach ($TPriceList as $idPriceList) {
			$this->fetch($idPriceList);
			$this->updatePricePricelist();
			$i++;
		}

		return 0;
	}

	/**
	 * Check if price change hasn't been done 1 year before the given date and isn't already planed 1 year after
	 * @param $db DBHandler
	 * @param $fk_product Int fk_product concerned
	 * @param $date_change date of predicted change
	 * @return bool True = yes, False = no
	 */
	public static function checkDate($db, $fk_product, $date_change){
		$min_date = date('Y-d-m',strtotime($date_change.' -1 year'));
		$max_date = date('Y-d-m',strtotime($date_change.' +1 year'));
		$max_date = $max_date.' 23:59:59.999';

		$sql = 'SELECT';
		$sql.= ' rowid';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'pricelist';
		$sql.= ' WHERE fk_product='.$fk_product;
		$sql.= ' AND entity='.getEntity('products');
		$sql.= ' AND date_change BETWEEN \''.$min_date.'\'';
		$sql.= ' AND \''.$max_date.'\'';
		$sql.= ' LIMIT 1';

		$TPricelist = array();

		$resql=$db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				if ($obj)
				{
					$TPricelist[$obj->rowid] = $obj->rowid;
				}
				$i++;
			}
		}
		return empty($TPricelist);
	}
}
