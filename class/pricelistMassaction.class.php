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

/**
 * Class PricelistMassaction
 */
class PricelistMassaction extends SeedObject
{
	/** @var string $table_element Table name in SQL */
	public $table_element = 'pricelist_massaction';

	/** @var string $table_element Table name in SQL */
	public $element = 'pricelist_massaction';

	public $fields=array(
		'entity'=>array('type'=>'int'),
		'reduc'=>array('type'=>'integer'),
		'reason'=>array('type'=>'text'),
		'date_change'=>array('type'=>'datetime'),
		'fk_user'=>array('type'=>'integer')
	);

	/**
	 * PricelistMassaction constructor.
	 * @param $db Database Connector
	 */
	public function __construct($db)
	{
		global $conf;
		$this->db = $db;
		$this->entity = $conf->entity;
		$this->init();
	}

	/** Save
	 * @param	User	$user		User object
	 * @param 	bool 	$notrigger  false=launch triggers after, true=disable triggers
	 * @return  int
	 */
	public function save($user, $notrigger = false)
	{
		return $this->create($user, $notrigger);
	}

	/**
	 * Delete
	 * @param   User	$user		User object
	 * @param 	bool 	$notrigger  false=launch triggers after, true=disable triggers
	 * @return  int
	 */
	public function delete(User &$user, $notrigger = false)
	{
		$TPricelists = Pricelist::getAllOfMassaction($this->db,$this->id);
		$TPricelistsIgnored = PricelistMassactionIgnored::getAllByMassaction($this->db,$this->id);

		foreach ($TPricelists as $pricelist){
			$objectPricelsit = new Pricelist($this->db);
			$objectPricelsit->fetch($pricelist['rowid']);
			$objectPricelsit->delete($user);
		}
		foreach ($TPricelistsIgnored as $pricelistIgnored){
			$objectPricelistIgnored = new PricelistMassactionIgnored($this->db);
			$objectPricelistIgnored->fetch($pricelistIgnored['rowid']);
			$objectPricelistIgnored->delete($user);
		}

		$this->deleteObjectLinked();

		unset($this->fk_element); // avoid conflict with standard Dolibarr comportment
		return parent::delete($user, $notrigger);
	}

	/**
	 * Create
	 * @param	User	$user		User registered
	 * @param 	bool 	$notrigger  false=launch triggers after, true=disable triggers
	 * @return	int					return id
	 */
	public function create(User &$user, $notrigger = false){
		return parent::create($user, $notrigger);
	}

	/** FetchAll Massactions
	 * @param int $limit
	 * @param bool $loadChild
	 * @param array $TFilter
	 * @return array all pricelist massactions
	 */
	public function fetchAll($limit = 0, $loadChild = true, $TFilter = array())
	{
		$TMassactions = parent::fetchAll($limit, $loadChild, $TFilter);
		foreach ($TMassactions as $index => $massaction){
			$TMassactions[$index]->date_change = date("d/m/Y",$massaction->date_change);
		}
		return $TMassactions;
	}

	/** getNom
	 * @param int $withpicto Add picto into link
	 * @param string $moreparams Add more parameters in the URL
	 * @return string
	 */
	public function getNomUrl($withpicto = 0, $moreparams = '')
	{
		global $langs;

		$result = '';
		$label = '<u>' . $langs->trans("ShowMassactionPricelist") . '</u>';

		$linkclose = '" title="' . dol_escape_htmltag($label, 1) . '" class="classfortooltip">';
		$link = '<a href="' . dol_buildpath('/pricelist/massactionPricelist.php', 1) . '?id=' . $this->id . urlencode($moreparams) . $linkclose;

		$linkend = '</a>';

		$picto = 'generic';

		if ($withpicto) $result .= ($link . img_object($label, $picto, 'class="classfortooltip"') . $linkend);
		if ($withpicto && $withpicto != 2) $result .= ' ';

		$result .= $link . date('d/m/Y',$this->date_change) . $linkend;

		return $result;
	}

	/** getNomStatic
	 * @param int $id Identifiant
	 * @param null $ref Ref
	 * @param int $withpicto Add picto into link
	 * @param string $moreparams Add more parameters in the URL
	 * @return string
	 */
	public static function getStaticNomUrl($id, $ref = null, $withpicto = 0, $moreparams = '')
	{
		global $db;

		$object = new pricelist($db);
		$object->fetch($id, false, $ref);

		return $object->getNomUrl($withpicto, $moreparams);
	}

	/** To know if massaction already changed the price
	 * @return bool (true = yes, false = no)
	 */
	public function isPassed(){
		return (date("Y-m-d") >= date("Y-m-d",$this->date_change));
	}
}
