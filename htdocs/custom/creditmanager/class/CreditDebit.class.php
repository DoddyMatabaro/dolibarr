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
 * \file       htdocs/custom/creditmanager/class/CreditDebit.class.php
 * \ingroup    creditmanager
 * \brief      Debit and refund logic for credits (timesheet integration)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class to manage credit debit and refund operations
 */
class CreditDebit
{
	/** @var DoliDB Database handler */
	public $db;

	/** @var string Error message */
	public $error = '';

	/** @var array Error messages */
	public $errors = array();

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Refund credits for a timesheet that was previously debited.
	 * Finds the debit movement linked to this timesheet and creates an inverse (credit) movement.
	 *
	 * @param int $fk_timesheet Ficheinter (timesheet) ID
	 * @return int <0 if KO, >0 if OK, 0 if nothing to do (not debited or no credit columns)
	 */
	public function refundCredits($fk_timesheet)
	{
		global $conf, $user;

		$fk_timesheet = (int) $fk_timesheet;
		if ($fk_timesheet <= 0) {
			$this->error = 'Invalid timesheet id';
			return -1;
		}

		// Check if llx_fichinter has credit columns (Phase 2 extension)
		$table = MAIN_DB_PREFIX . 'fichinter';
		$res = $this->db->query("SHOW COLUMNS FROM " . $table . " LIKE 'credit_status'");
		if (!$res || $this->db->num_rows($res) == 0) {
			dol_syslog(__CLASS__ . '::refundCredits - credit_status column not found, skipping');
			return 0;
		}
		$this->db->free($res);

		// Find the debit movement for this timesheet
		$sql = "SELECT rowid, fk_soc, fk_credit_type, amount, balance_after";
		$sql .= " FROM " . MAIN_DB_PREFIX . "credits_movements";
		$sql .= " WHERE fk_timesheet = " . $fk_timesheet;
		$sql .= " AND amount < 0"; // Debit = negative amount
		$sql .= " AND entity = " . ((int) $conf->entity);

		$res = $this->db->query($sql);
		if (!$res) {
			$this->error = $this->db->lasterror();
			return -1;
		}

		$debitMovement = $this->db->fetch_object($res);
		$this->db->free($res);

		if (!$debitMovement) {
			dol_syslog(__CLASS__ . '::refundCredits - No debit movement found for timesheet ' . $fk_timesheet);
			return 0; // Nothing to refund
		}

		$socid = (int) $debitMovement->fk_soc;
		$creditTypeId = (int) $debitMovement->fk_credit_type;
		$refundAmount = abs((float) $debitMovement->amount);
		$fkParentMovement = (int) $debitMovement->rowid;

		if ($refundAmount <= 0 || $socid <= 0 || $creditTypeId <= 0) {
			$this->error = 'Invalid debit movement data';
			return -1;
		}

		$this->db->begin();

		try {
			// Update balance: add back the refunded amount
			$sqlBal = "SELECT rowid, balance FROM " . MAIN_DB_PREFIX . "credits_balance";
			$sqlBal .= " WHERE fk_soc = " . $socid;
			$sqlBal .= " AND fk_credit_type = " . $creditTypeId;
			$sqlBal .= " AND entity = " . ((int) $conf->entity);

			$resBal = $this->db->query($sqlBal);
			if (!$resBal) {
				$this->db->rollback();
				$this->error = $this->db->lasterror();
				return -1;
			}

			$objBal = $this->db->fetch_object($resBal);
			$this->db->free($resBal);

			if (!$objBal) {
				// Create balance row if needed
				$newBalance = $refundAmount;
				$sqlIns = "INSERT INTO " . MAIN_DB_PREFIX . "credits_balance (entity, fk_soc, fk_credit_type, balance, tms)";
				$sqlIns .= " VALUES (" . ((int) $conf->entity) . ", " . $socid . ", " . $creditTypeId . ", " . price2num($newBalance, 'MT') . ", CURRENT_TIMESTAMP)";
				if (!$this->db->query($sqlIns)) {
					$this->db->rollback();
					$this->error = $this->db->lasterror();
					return -1;
				}
			} else {
				$newBalance = (float) $objBal->balance + $refundAmount;
				$sqlUpd = "UPDATE " . MAIN_DB_PREFIX . "credits_balance";
				$sqlUpd .= " SET balance = " . price2num($newBalance, 'MT') . ", tms = CURRENT_TIMESTAMP";
				$sqlUpd .= " WHERE rowid = " . ((int) $objBal->rowid);
				if (!$this->db->query($sqlUpd)) {
					$this->db->rollback();
					$this->error = $this->db->lasterror();
					return -1;
				}
			}

			// Create refund movement (credit = positive amount)
			$description = 'Remboursement - Ficheinter #' . $fk_timesheet;
			$sqlMov = "INSERT INTO " . MAIN_DB_PREFIX . "credits_movements";
			$sqlMov .= " (entity, fk_soc, fk_credit_type, date_movement, amount, balance_after, type_movement, description,";
			$sqlMov .= " fk_timesheet, fk_invoice, fk_attribution, fk_parent_movement, fk_user_creat, tms)";
			$sqlMov .= " VALUES (";
			$sqlMov .= (int) $conf->entity . ", ";
			$sqlMov .= $socid . ", ";
			$sqlMov .= $creditTypeId . ", ";
			$sqlMov .= "'" . $this->db->idate(dol_now()) . "', ";
			$sqlMov .= price2num($refundAmount, 'MT') . ", ";
			$sqlMov .= price2num($newBalance, 'MT') . ", ";
			$sqlMov .= "'REFUND', ";
			$sqlMov .= "'" . $this->db->escape($description) . "', ";
			$sqlMov .= $fk_timesheet . ", ";
			$sqlMov .= "NULL, NULL, ";
			$sqlMov .= $fkParentMovement . ", ";
			$sqlMov .= ((int) $user->id) . ", ";
			$sqlMov .= "CURRENT_TIMESTAMP)";

			if (!$this->db->query($sqlMov)) {
				$this->db->rollback();
				$this->error = $this->db->lasterror();
				return -1;
			}

			// Update timesheet: clear debit fields and set status back to APPROVED or DRAFT
			$resCol = $this->db->query("SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "fichinter LIKE 'credit_debit_reference'");
			if ($resCol && $this->db->num_rows($resCol) > 0) {
				$this->db->free($resCol);
				$sqlFich = "UPDATE " . MAIN_DB_PREFIX . "fichinter";
				$sqlFich .= " SET credit_debit_reference = NULL, credit_debit_date = NULL, credit_debit_amount = NULL";
				$sqlFich .= ", credit_status = 'APPROVED'"; // Back to approved, not debited
				$sqlFich .= " WHERE rowid = " . $fk_timesheet;
				$this->db->query($sqlFich); // Best effort
			}

			$this->db->commit();
			dol_syslog(__CLASS__ . '::refundCredits - Refunded ' . $refundAmount . ' for timesheet ' . $fk_timesheet);
			return 1;
		} catch (Exception $e) {
			$this->db->rollback();
			$this->error = $e->getMessage();
			return -1;
		}
	}
}
