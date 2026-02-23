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

/**
 * \file    core/triggers/interface_99_modCreditManager_CreditManagerTriggers.class.php
 * \ingroup creditmanager
 * \brief   Triggers for Credit Manager - Ficheinter (timesheet) refund on reopen/delete
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/creditmanager/class/CreditDebit.class.php';

/**
 * Class of triggers for Credit Manager module
 */
class InterfaceCreditManagerTriggers extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		parent::__construct($db);
		$this->family = "financial";
		$this->description = "Credit Manager triggers - Refund credits when Ficheinter is reopened or deleted";
		$this->version = self::VERSIONS['prod'];
		$this->picto = 'creditmanager@creditmanager';
	}

	/**
	 * Function called when a Dolibarr business event is done.
	 *
	 * @param string       $action     Event action code
	 * @param CommonObject $object     Object
	 * @param User         $user       User
	 * @param Translate    $langs      Langs
	 * @param Conf         $conf       Conf
	 * @return int                     <0 if KO, 0 if nothing done, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (!isModEnabled('creditmanager')) {
			return 0;
		}

		// Only handle Ficheinter (intervention/timesheet) events
		if (!in_array($action, array('FICHINTER_REOPEN', 'FICHINTER_DELETE', 'FICHINTER_MODIFY'))) {
			return 0;
		}

		$fk_timesheet = isset($object->id) ? (int) $object->id : 0;
		if ($fk_timesheet <= 0) {
			return 0;
		}

		// Check if llx_fichinter has credit columns because they are required
		$res = $this->db->query("SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "fichinter LIKE 'credit_status'");
		if (!$res || $this->db->num_rows($res) == 0) {
			return 0;
		}
		$this->db->free($res);

		switch ($action) {
			case 'FICHINTER_REOPEN':
				// Reopening: Validation/DEBITED -> DRAFT. If was debited, refund.
				$creditStatus = isset($object->credit_status) ? $object->credit_status : '';
				if (in_array($creditStatus, array('DEBITED', 'APPROVED'))) {
					$debit = new CreditDebit($this->db);
					$result = $debit->refundCredits($fk_timesheet);
					if ($result > 0) {
						dol_syslog("CreditManager: Refunded credits for Ficheinter #" . $fk_timesheet . " (reopened)");
						return 1;
					}
					if ($result < 0) {
						$this->errors[] = $debit->error;
						return -1;
					}
				}
				break;

			case 'FICHINTER_DELETE':
				// Deletion: If was debited, refund before delete (object still exists when trigger runs)
				$debit = new CreditDebit($this->db);
				$result = $debit->refundCredits($fk_timesheet);
				if ($result > 0) {
					dol_syslog("CreditManager: Refunded credits for deleted Ficheinter #" . $fk_timesheet);
					return 1;
				}
				if ($result < 0) {
					$this->errors[] = $debit->error;
					return -1;
				}
				break;

			case 'FICHINTER_MODIFY':
				// Modification after debit: Log warning if duree was changed and status is DEBITED.
				// Note: We cannot prevent the modification here (trigger runs after). User must "Reopen" to modify.
				$creditStatus = isset($object->credit_status) ? $object->credit_status : '';
				if ($creditStatus === 'DEBITED') {
					dol_syslog("CreditManager: Ficheinter #" . $fk_timesheet . " modified while DEBITED - user should Reopen to modify duration");
				}
				break;
		}

		return 0;
	}
}
