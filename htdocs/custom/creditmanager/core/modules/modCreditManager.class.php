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
 * Contributor of this script: https://github.com/joelmpunga Joel MPUNGA
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modCreditManager extends DolibarrModules
{
	public function __construct($db)
	{
		global $langs;

		$this->db = $db;
		$this->numero = 560000;

		$this->family = "financial";
		$this->module_position = '55';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Gestion des crédits heures (types, soldes, débit timesheets)";

		$this->version = '1.0';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'credit';
		$this->editor_name = 'Joel MPUNGA and Doddy MATABARO';

		$this->dirs = array();

		$this->module_parts = array(
			'triggers' => 1,
			'hooks' => array(
				'data' => array('tasktimelist'),
				'entity' => '0',
			),
		);

		$this->depends = array('modSociete', 'modProjet', 'modFicheinter', 'modContrat', 'modFacture');
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->phpmin = array(7, 4);
		$this->langfiles = array("creditmanager@creditmanager");

		$this->config_page_url = array();

		$this->const = array();

		$this->boxes = array();

		// Tabs for thirdparty (client card)
		$this->tabs = array(
			'thirdparty:+creditmanager:Credits:creditmanager:empty($user->socid) && $user->hasRight("creditmanager","read"):/custom/creditmanager/tabs/thirdpartyCredits.php?socid=__ID__',
		);

		$this->rights = array();
		$this->rights_class = 'creditmanager';
		$r = 0;

		$r++;
		$this->rights[$r][0] = $this->numero + $r;
		$this->rights[$r][1] = 'Lire / utiliser les crédits';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'read';

		$r++;
		$this->rights[$r][0] = $this->numero + $r;
		$this->rights[$r][1] = 'Créer / modifier les crédits';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'write';

		$r++;
		$this->rights[$r][0] = $this->numero + $r;
		$this->rights[$r][1] = 'Supprimer les crédits';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'delete';

		$r++;
		$this->rights[$r][0] = $this->numero + $r;
		$this->rights[$r][1] = 'Administrer le module Credit Manager';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'creditmanager_admin';

		$r++;
		$this->rights[$r][0] = $this->numero + $r;
		$this->rights[$r][1] = 'Voir ses crédits (portail client)';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'creditmanager_client';

		// Menus
		$this->menu = array();
		$r = 0;

		// Top menu - appears in the main horizontal bar
		$this->menu[$r++] = array(
			'fk_menu'  => '',
			'type'     => 'top',
			'titre'    => 'CreditManager',
			'prefix'   => img_picto('', $this->picto, 'class="pictofixedwidth em092"'),
			'mainmenu' => 'creditmanager',
			'leftmenu' => '',
			'url'      => '/custom/creditmanager/index.php',
			'langs'    => 'creditmanager@creditmanager',
			'position' => 100,
			'enabled'  => 'isModEnabled("creditmanager")',
			'perms'    => '1',
			'target'   => '',
			'user'     => 2,
		);

		// Left menu - Dashboard
		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=creditmanager',
			'type'     => 'left',
			'titre'    => 'CreditManagerDashboard',
			'prefix'   => img_picto('', $this->picto, 'class="paddingright pictofixedwidth em092"'),
			'mainmenu' => 'creditmanager',
			'leftmenu' => 'creditmanager_dashboard',
			'url'      => '/custom/creditmanager/index.php',
			'langs'    => 'creditmanager@creditmanager',
			'position' => 1001,
			'enabled'  => 'isModEnabled("creditmanager")',
			'perms'    => '$user->hasRight("creditmanager","read")',
			'target'   => '',
			'user'     => 2,
		);

		// Left menu - Balances
		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=creditmanager',
			'type'     => 'left',
			'titre'    => 'CreditBalances',
			'mainmenu' => 'creditmanager',
			'leftmenu' => 'creditmanager_balances',
			'url'      => '/custom/creditmanager/balance_list.php',
			'langs'    => 'creditmanager@creditmanager',
			'position' => 1010,
			'enabled'  => 'isModEnabled("creditmanager")',
			'perms'    => '$user->hasRight("creditmanager","read")',
			'target'   => '',
			'user'     => 2,
		);

		// Left menu - Movements
		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=creditmanager',
			'type'     => 'left',
			'titre'    => 'CreditMovements',
			'mainmenu' => 'creditmanager',
			'leftmenu' => 'creditmanager_movements',
			'url'      => '/custom/creditmanager/movement_list.php',
			'langs'    => 'creditmanager@creditmanager',
			'position' => 1020,
			'enabled'  => 'isModEnabled("creditmanager")',
			'perms'    => '$user->hasRight("creditmanager","read")',
			'target'   => '',
			'user'     => 2,
		);

		// Left menu - Alerts
		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=creditmanager',
			'type'     => 'left',
			'titre'    => 'CreditAlerts',
			'mainmenu' => 'creditmanager',
			'leftmenu' => 'creditmanager_alerts',
			'url'      => '/custom/creditmanager/alert_list.php',
			'langs'    => 'creditmanager@creditmanager',
			'position' => 1030,
			'enabled'  => 'isModEnabled("creditmanager")',
			'perms'    => '$user->hasRight("creditmanager","read")',
			'target'   => '',
			'user'     => 2,
		);

		// Left menu - Admin section (separator)
		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=creditmanager',
			'type'     => 'left',
			'titre'    => 'CreditManagerSetup',
			'mainmenu' => 'creditmanager',
			'leftmenu' => 'creditmanager_admin',
			'url'      => '/custom/creditmanager/admin/credit_types.php',
			'langs'    => 'creditmanager@creditmanager',
			'position' => 1090,
			'enabled'  => 'isModEnabled("creditmanager")',
			'perms'    => '$user->hasRight("creditmanager","creditmanager_admin")',
			'target'   => '',
			'user'     => 0,
		);
	}

	public function init($options = '')
	{
		$result = $this->_load_tables('/custom/creditmanager/sql/', '');
		if ($result < 0) {
			return -1;
		}

		// Backward compatibility for existing installs that predate timesheet linkage columns.
		$this->addCreditsMovementsColumns();

		// Keep backward compatibility for UI/legacy queries expecting these fields on llx_element_time.
		$this->addElementTimeCreditColumns();

		return $this->_init(array(), $options);
	}

	private function addElementTimeCreditColumns()
	{
		$table = MAIN_DB_PREFIX.'element_time';
		$cols = array(
			'fk_credit_type' => "ALTER TABLE ".$table." ADD COLUMN fk_credit_type INTEGER NULL",
			'credit_status' => "ALTER TABLE ".$table." ADD COLUMN credit_status VARCHAR(20) DEFAULT 'SUBMITTED'",
			'credit_debit_reference' => "ALTER TABLE ".$table." ADD COLUMN credit_debit_reference VARCHAR(50) NULL",
			'credit_debit_date' => "ALTER TABLE ".$table." ADD COLUMN credit_debit_date DATETIME NULL",
			'credit_debit_amount' => "ALTER TABLE ".$table." ADD COLUMN credit_debit_amount DECIMAL(15,2) NULL",
			'credit_approval_date' => "ALTER TABLE ".$table." ADD COLUMN credit_approval_date DATETIME NULL",
		);

		foreach ($cols as $col => $sql) {
			$res = $this->db->query("SHOW COLUMNS FROM ".$table." LIKE '".$this->db->escape($col)."'");
			if ($res && $this->db->num_rows($res) == 0) {
				$this->db->query($sql);
			}
			if ($res) {
				$this->db->free($res);
			}
		}
	}

	private function addCreditsMovementsColumns()
	{
		$table = MAIN_DB_PREFIX.'credits_movements';
		$cols = array(
			'fk_element_time' => "ALTER TABLE ".$table." ADD COLUMN fk_element_time INTEGER NULL",
			'timesheet_elementtype' => "ALTER TABLE ".$table." ADD COLUMN timesheet_elementtype VARCHAR(32) NULL",
		);

		foreach ($cols as $col => $sql) {
			$res = $this->db->query("SHOW COLUMNS FROM ".$table." LIKE '".$this->db->escape($col)."'");
			if ($res && $this->db->num_rows($res) == 0) {
				$this->db->query($sql);
			}
			if ($res) {
				$this->db->free($res);
			}
		}
	}

}
