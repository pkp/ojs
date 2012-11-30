<?php

/**
 * @file controllers/grid/admin/journal/JournalGridRow.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalGridRow
 * @ingroup controllers_grid_admin_journal
 *
 * @brief Journal grid row definition
 */

import('lib.pkp.controllers.grid.admin.context.ContextGridRow');

class JournalGridRow extends ContextGridRow {
	/**
	 * Constructor
	 */
	function JournalGridRow() {
		parent::ContextGridRow();
	}


	//
	// Overridden methods from ContextGridRow
	//
	/**
	 * Get the delete context row locale key.
	 * @return string
	 */
	function getConfirmDeleteKey() {
		return 'admin.journals.confirmDelete';
	}
}

?>
