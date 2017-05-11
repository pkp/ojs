<?php

/**
 * @file classes/user/InterestEntryDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class InterestsEntryDAO
 * @ingroup user
 * @see User
 *
 * @brief Operations for retrieving and modifying a user's review interests.
 */


import('lib.pkp.classes.user.InterestEntry');
import('lib.pkp.classes.controlledVocab.ControlledVocabEntryDAO');

class InterestEntryDAO extends ControlledVocabEntryDAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return PaperTypeEntry
	 */
	function newDataObject() {
		return new InterestEntry();
	}

	/**
	 * Get the list of non-localized additional fields to store.
	 * @return array
	 */
	function getAdditionalFieldNames() {
		return array('interest');
	}

	/**
	 * Retrieve an iterator of controlled vocabulary entries matching a
	 * particular controlled vocabulary ID.
	 * @param $controlledVocabId int
	 * @param $rangeInfo RangeInfo optional range information for result
	 * @param $filter string Optional filter to match to beginnings of results
	 * @return object DAOResultFactory containing matching CVE objects
	 */
	function getByControlledVocabId($controlledVocabId, $rangeInfo = null, $filter = null) {
		$params = array((int) $controlledVocabId);
		if ($filter) {
			$params[] = 'interest';
			$params[] = $filter . '%';
		}

		$result = $this->retrieveRange(
			'SELECT	cve.*
			FROM	controlled_vocab_entries cve
				JOIN user_interests ui ON (cve.controlled_vocab_entry_id = ui.controlled_vocab_entry_id)
				' . ($filter?'JOIN controlled_vocab_entry_settings cves ON (cves.controlled_vocab_entry_id = cve.controlled_vocab_entry_id)':'') . '
			WHERE cve.controlled_vocab_id = ?
			' . ($filter?'AND cves.setting_name=? AND LOWER(cves.setting_value) LIKE LOWER(?)':'') . '
			GROUP BY cve.controlled_vocab_entry_id
			ORDER BY seq',
			$params,
			$rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}
}

?>
