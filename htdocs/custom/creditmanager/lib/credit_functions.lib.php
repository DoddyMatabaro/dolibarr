<?php
/* Copyright (C) 2026  Credit Manager module for Dolibarr
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * Contributor of this script: https://github.com/joelmpunga Joel MPUNGA
 */

/**
 * \file htdocs/custom/creditmanager/lib/credit_functions.lib.php
 * \ingroup creditmanager
 * \brief Validation helpers for credit debit operations
 */

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
dol_include_once('/creditmanager/class/CreditType.class.php');
dol_include_once('/creditmanager/class/CreditBalance.class.php');

/**
 * Check whether a debit of a given amount (hours) is allowed for a client / project / credit type.
 *
 * @param int     $fk_soc         Third-party ID
 * @param int     $fk_proj        Project ID (0 if not applicable)
 * @param int     $fk_credit_type Credit type ID
 * @param float   $amount         Amount in hours (same unit as balance)
 * @param DoliDB  $db             Database handler
 * @return array{ok:bool, error?:string} ok=true on success, or ok=false with error translation key
 */
function checkDebitPossible($fk_soc, $fk_proj, $fk_credit_type, $amount, $db)
{
	if ($fk_soc <= 0) {
		return array('ok' => false, 'error' => 'CreditDebitCheckNoClient');
	}
	if ($fk_credit_type <= 0) {
		return array('ok' => false, 'error' => 'CreditDebitCheckNoCreditType');
	}
	if ($amount <= 0) {
		return array('ok' => false, 'error' => 'CreditDebitCheckNoAmount');
	}

	$societe = new Societe($db);
	if ($societe->fetch($fk_soc) <= 0) {
		return array('ok' => false, 'error' => 'CreditDebitCheckClientNotFound');
	}

	if (isset($societe->status) && (int) $societe->status === 0) {
		return array('ok' => false, 'error' => 'CreditDebitCheckClientInactive');
	}

	$creditType = new CreditType($db);
	if ($creditType->fetch($fk_credit_type) <= 0) {
		return array('ok' => false, 'error' => 'CreditDebitCheckTypeNotFound');
	}

	if (empty($creditType->active)) {
		return array('ok' => false, 'error' => 'CreditDebitCheckTypeInactive');
	}

	$balance = new CreditBalance($db);
	if (!$balance->checkSufficientBalance($fk_soc, $fk_credit_type, $amount)) {
		return array('ok' => false, 'error' => 'CreditDebitCheckInsufficientBalance');
	}

	if ($fk_proj > 0) {
		require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
		$project = new Project($db);
		if ($project->fetch($fk_proj) <= 0) {
			return array('ok' => false, 'error' => 'CreditDebitCheckProjectNotFound');
		}
		$projSoc = !empty($project->socid) ? (int) $project->socid : (!empty($project->fk_soc) ? (int) $project->fk_soc : 0);
		if ($projSoc > 0 && $projSoc !== (int) $fk_soc) {
			return array('ok' => false, 'error' => 'CreditDebitCheckProjectClientMismatch');
		}
		$pst = isset($project->status) ? (int) $project->status : (isset($project->statut) ? (int) $project->statut : -1);
		if ($pst === 2) {
			return array('ok' => false, 'error' => 'CreditDebitCheckProjectClosed');
		}
	}

	return array('ok' => true);
}
