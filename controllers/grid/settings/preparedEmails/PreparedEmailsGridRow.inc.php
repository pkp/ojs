<?php

/**
 * @file controllers/grid/settings/preparedEmails/PreparedEmailsGridRow.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PreparedEmailsGridRow
 * @ingroup controllers_grid_settings_PreparedEmails
 *
 * @brief Handle PreparedEmails grid row requests.
 */

import('lib.pkp.classes.controllers.grid.settings.preparedEmails.PKPPreparedEmailsGridRow');

class PreparedEmailsGridRow extends PKPPreparedEmailsGridRow {
	/**
	 * Constructor
	 */
	function PreparedEmailsGridRow() {
		parent::PKPPreparedEmailsGridRow();
	}

	//
	// Overridden parent class methods
	//
	/**
	 * Return the context ID.
	 * @param $request PKPRequest
	 * @return int Journal ID.
	 */
	function getContextId($request) {
		$journal = $request->getJournal();
		return $journal->getId();
	}
}

?>
