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
 * Class PricelistMassactionIgnored
 */
class PricelistMassactionIgnored extends SeedObject
{
	/** @var string $table_element Table name in SQL */
	public $table_element = 'pricelist_massaction_ignored';

	/** @var string $table_element Table name in SQL */
	public $element = 'pricelist_massaction_ignored';

	public $fields=array(
		'fk_product'	=>array('type'=>'int'),
		'fk_massaction' =>array('type'=>'int')
	);

	/**
	 * PricelistMassactionIgnored constructor.
	 * @param $db Database Connector
	 */
	public function __construct($db)
	{
		$this->db = $db;
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

	/** getNom
	 * @param int $withpicto Add picto into link
	 * @param string $moreparams Add more parameters in the URL
	 * @return string
	 */
	public function getNomUrl($withpicto = 0, $moreparams = '')
	{
		global $langs;

		$result = '';
		$label = '<u>' . $langs->trans("Showpricelist") . '</u>';

		$linkclose = '" title="' . dol_escape_htmltag($label, 1) . '" class="classfortooltip">';
		$link = '<a href="' . dol_buildpath('/pricelist/card.php', 1) . '?id=' . $this->id . urlencode($moreparams) . $linkclose;

		$linkend = '</a>';

		$picto = 'generic';

		if ($withpicto) $result .= ($link . img_object($label, $picto, 'class="classfortooltip"') . $linkend);
		if ($withpicto && $withpicto != 2) $result .= ' ';

		$result .= $link . $this->ref . $linkend;

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

	/** Get all pricelists Ignored of a massactioon
	 * @param $db
	 * @param $id int ID of massaction
	 * @return array all pricelists
	 */
	public static function getAllByMassaction($db, $id)
	{
		$sql = 'SELECT';
		$sql.= ' rowid';
		$sql.= ' ,fk_product';
		$sql.= ' ,fk_massaction';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'pricelist_massaction_ignored';
		$sql.= ' WHERE fk_massaction='.$id;

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
					$TPricelist[$obj->rowid]['fk_product'] = $obj->fk_product;
					$TPricelist[$obj->rowid]['fk_massaction'] = $obj->fk_massaction;
				}
				$i++;
			}
		}

		return $TPricelist;
	}
}
