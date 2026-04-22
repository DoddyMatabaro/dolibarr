<?php
/* Copyright (C) 2026  Credit Manager module for Dolibarr
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/custom/creditmanager/lib/creditmanager.lib.php
 *	\ingroup    creditmanager
 *	\brief      Library of functions for module Credit Manager
 */

/**
 * Force all Credit Manager left menu entries to the same level as Dashboard (Menubase::menuLeftCharger adds them with level 0 when fk_menu=-1 and fk_leftmenu is empty).
 * If fk_menu points to another left entry, Eldy shows those lines as level > 0 and omits pictos.
 *
 * @param DoliDB $db             Database handler
 * @param bool   $useSessionCache  If true, run at most once per session (for web pages)
 * @return void
 */
function creditmanagerEnsureLeftMenuFlat(DoliDB $db, $useSessionCache = true)
{
	global $conf;

	if (!isModEnabled('creditmanager')) {
		return;
	}

	if ($useSessionCache && !empty($_SESSION['creditmanager_menu_flat_ok'])) {
		return;
	}

	$module = 'creditmanager';
	$sql = "UPDATE ".$db->prefix()."menu SET";
	$sql .= " fk_menu = -1,";
	$sql .= " fk_mainmenu = 'creditmanager',";
	$sql .= " fk_leftmenu = NULL";
	$sql .= " WHERE module = '".$db->escape($module)."'";
	$sql .= " AND type = 'left'";
	$sql .= " AND mainmenu = 'creditmanager'";
	$sql .= " AND entity IN (0, ".((int) $conf->entity).")";

	$db->query($sql);

	if ($useSessionCache) {
		$_SESSION['creditmanager_menu_flat_ok'] = 1;
	}
}

/**
 *  Prepare admin pages header (tabs)
 *
 *  @return	array		Array of tabs
 */
function creditmanagerAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("creditmanager@creditmanager");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/custom/creditmanager/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/custom/creditmanager/admin/credit_types.php", 1);
	$head[$h][1] = $langs->trans("CreditTypes");
	$head[$h][2] = 'credit_types';
	$h++;

	$head[$h][0] = dol_buildpath("/custom/creditmanager/admin/attribution.php", 1);
	$head[$h][1] = $langs->trans("CreditAttribution");
	$head[$h][2] = 'attribution';
	$h++;

	$head[$h][0] = dol_buildpath("/custom/creditmanager/admin/tools.php", 1);
	$head[$h][1] = $langs->trans("CreditManagerTools");
	$head[$h][2] = 'tools';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'creditmanager');
	complete_head_from_modules($conf, $langs, null, $head, $h, 'creditmanager', 'remove');

	return $head;
}

/**
 * Read access for internal users (admin/finance/pm/staff).
 * Portal users are handled separately with creditmanagerCanReadClientPortal().
 *
 * @param User $user
 * @return bool
 */
function creditmanagerCanReadModule($user)
{
	return !empty($user->rights->creditmanager->read);
}

/**
 * Access to admin setup/maintenance screens.
 *
 * @param User $user
 * @return bool
 */
function creditmanagerCanManageAdmin($user)
{
	return !empty($user->admin) || !empty($user->rights->creditmanager->creditmanager_admin);
}

/**
 * Access to credit types management (finance/admin).
 *
 * @param User $user
 * @return bool
 */
function creditmanagerCanManageCreditTypes($user)
{
	return creditmanagerCanManageAdmin($user) || !empty($user->rights->creditmanager->credit_types_manage);
}

/**
 * Access to credit attribution management (finance/admin).
 *
 * @param User $user
 * @return bool
 */
function creditmanagerCanManageAttributions($user)
{
	return creditmanagerCanManageAdmin($user) || !empty($user->rights->creditmanager->attribution_manage);
}

/**
 * Access to manual timesheet debit (pm/admin).
 *
 * @param User $user
 * @return bool
 */
function creditmanagerCanManualDebit($user)
{
	return creditmanagerCanManageAdmin($user) || !empty($user->rights->creditmanager->timesheet_manual_debit);
}

/**
 * Export permission for module reports/lists.
 *
 * @param User $user
 * @return bool
 */
function creditmanagerCanExport($user)
{
	return creditmanagerCanManageAdmin($user) || !empty($user->rights->creditmanager->reports_export);
}

/**
 * Portal read access for users linked to a thirdparty.
 *
 * @param User $user
 * @return bool
 */
function creditmanagerCanReadClientPortal($user)
{
	return !empty($user->rights->creditmanager->client_portal_read) || !empty($user->rights->creditmanager->creditmanager_client);
}
