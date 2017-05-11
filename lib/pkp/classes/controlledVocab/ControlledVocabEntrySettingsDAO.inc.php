<?php

/**
 * @file classes/controlledVocab/ControlledVocabEntrySettingsDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ControlledVocabEntrySettingsDAO
 * @ingroup controlled_vocabs
 *
 * @brief Operations for retrieving and modifying controlled vocabulary entry settings.
 */

import('lib.pkp.classes.db.SettingsDAO');

class ControlledVocabEntrySettingsDAO extends SettingsDAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Get the settings table name.
	 * @return string
	 */
	protected function _getTableName() {
		return 'controlled_vocab_entry_settings';
	}

	/**
	 * Get the primary key column name.
	 * @return string
	 */
	protected function _getPrimaryKeyColumn() {
		return 'controlled_vocab_entry_id';
	}
}

?>
