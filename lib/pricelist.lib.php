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
 *	\file		lib/pricelist.lib.php
 *	\ingroup	pricelist
 *	\brief		This file is an example module library
 *				Put some comments here
 */

/**
 * @return array
 */
function pricelistAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load('pricelist@pricelist');

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/pricelist/admin/pricelist_setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;
    $head[$h][0] = dol_buildpath("/pricelist/admin/pricelist_about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@pricelist:/pricelist/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@pricelist:/pricelist/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'pricelist');

    return $head;
}

/**
 * Return array of tabs to used on pages for third parties cards.
 *
 * @param 	pricelist	$object		Object company shown
 * @return 	array				Array of tabs
 */
function pricelist_prepare_head(pricelist $object)
{
    global $langs, $conf;
    $h = 0;
    $head = array();
    $head[$h][0] = dol_buildpath('/pricelist/card.php', 1).'?id='.$object->id;
    $head[$h][1] = $langs->trans("pricelistCard");
    $head[$h][2] = 'card';
    $h++;

	// Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@pricelist:/pricelist/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@pricelist:/pricelist/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'pricelist');

	return $head;
}

/**
 * @param Form      $form       Form object
 * @param pricelist  $object     pricelist object
 * @param string    $action     Triggered action
 * @return string
 */
function getFormConfirmpricelist($form, $object, $action)
{
    global $langs, $user;

    $formconfirm = '';

    if ($action === 'valid' && !empty($user->rights->pricelist->write))
    {
        $body = $langs->trans('ConfirmValidatepricelistBody', $object->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmValidatepricelistTitle'), $body, 'confirm_validate', '', 0, 1);
    }
    elseif ($action === 'accept' && !empty($user->rights->pricelist->write))
    {
        $body = $langs->trans('ConfirmAcceptpricelistBody', $object->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmAcceptpricelistTitle'), $body, 'confirm_accept', '', 0, 1);
    }
    elseif ($action === 'refuse' && !empty($user->rights->pricelist->write))
    {
        $body = $langs->trans('ConfirmRefusepricelistBody', $object->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmRefusepricelistTitle'), $body, 'confirm_refuse', '', 0, 1);
    }
    elseif ($action === 'reopen' && !empty($user->rights->pricelist->write))
    {
        $body = $langs->trans('ConfirmReopenpricelistBody', $object->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmReopenpricelistTitle'), $body, 'confirm_refuse', '', 0, 1);
    }
    elseif ($action === 'delete' && !empty($user->rights->pricelist->write))
    {
        $body = $langs->trans('ConfirmDeletepricelistBody');
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmDeletepricelistTitle'), $body, 'confirm_delete', '', 0, 1);
    }
    elseif ($action === 'clone' && !empty($user->rights->pricelist->write))
    {
        $body = $langs->trans('ConfirmClonepricelistBody', $object->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmClonepricelistTitle'), $body, 'confirm_clone', '', 0, 1);
    }
    elseif ($action === 'cancel' && !empty($user->rights->pricelist->write))
    {
        $body = $langs->trans('ConfirmCancelpricelistBody', $object->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmCancelpricelistTitle'), $body, 'confirm_cancel', '', 0, 1);
    }

    return $formconfirm;
}
