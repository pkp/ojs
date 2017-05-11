<?php

/**
 * @file classes/user/InterestDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class InterestDAO
 * @ingroup user
 * @see User
 *
 * @brief Operations for retrieving and modifying a user's review interests.
 */

import('lib.pkp.classes.controlledVocab.ControlledVocabDAO');

define('CONTROLLED_VOCAB_INTEREST', 'interest');

class InterestDAO extends ControlledVocabDAO {

	/**
	 * Create or return the Controlled Vocabulary for interests
	 * @return ControlledVocab
	 */
	function build() {
		return parent::build(CONTROLLED_VOCAB_INTEREST);
	}

	/**
	 * Get a list of controlled vocabulary entry IDs (corresponding to interest keywords) attributed to a user
	 * @param $userId int
	 * @return array
	 */
	function getUserInterestIds($userId) {
		$controlledVocab = $this->build();
		$result = $this->retrieveRange(
			'SELECT cve.controlled_vocab_entry_id FROM controlled_vocab_entries cve, user_interests ui WHERE cve.controlled_vocab_id = ? AND ui.controlled_vocab_entry_id = cve.controlled_vocab_entry_id AND ui.user_id = ?',
			array((int) $controlledVocab->getId(), (int) $userId)
		);

		$ids = array();
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$ids[] = $row['controlled_vocab_entry_id'];
			$result->MoveNext();
		}
		$result->Close();

		return $ids;
	}

	/**
	 * Get a list of user IDs attributed to an interest
	 * @param $userId int
	 * @return array
	 */
	function getUserIdsByInterest($interest) {
		$result = $this->retrieve('
			SELECT ui.user_id
			FROM user_interests ui
				INNER JOIN controlled_vocab_entry_settings cves ON (ui.controlled_vocab_entry_id = cves.controlled_vocab_entry_id)
			WHERE cves.setting_name = ? AND cves.setting_value = ?',
			array('interest', $interest)
		);

		$returner = array();
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[] = $row['user_id'];
			$result->MoveNext();
		}
		$result->Close();
		return $returner;

	}

	/**
	 * Get all user's interests
	 * @param $filter string (optional)
	 * @return object
	 */
	function getAllInterests($filter = null) {
		$controlledVocab = $this->build();
		$interestEntryDao = DAORegistry::getDAO('InterestEntryDAO');
		$iterator = $interestEntryDao->getByControlledVocabId($controlledVocab->getId(), null, $filter);

		// Sort by name.
		$interests = $iterator->toArray();
		usort($interests, create_function('$s1, $s2', 'return strcmp($s1->getInterest(), $s2->getInterest());'));

		// Turn back into an iterator.
		import('lib.pkp.classes.core.ArrayItemIterator');
		return new ArrayItemIterator($interests);
	}

	/**
	 * Update a user's set of interests
	 * @param $interests array
	 * @param $userId int
	 */
	function setUserInterests($interests, $userId) {
		// Remove duplicates
		$interests = isset($interests) ? $interests : array();
		$interests = array_unique($interests);

		// Trim whitespace
		$interests = array_map('trim', $interests);

		// Delete the existing interests association.
		$this->update(
			'DELETE FROM user_interests WHERE user_id = ?',
			array((int) $userId)
		);

		$interestEntryDao = DAORegistry::getDAO('InterestEntryDAO'); /* @var $interestEntryDao InterestEntryDAO */
		$controlledVocab = $this->build();

		// Store the new interests.
		foreach ((array) $interests as $interest) {
			$interestEntry = $interestEntryDao->getBySetting($interest, $controlledVocab->getSymbolic(),
				$controlledVocab->getAssocId(), $controlledVocab->getAssocType(), $controlledVocab->getSymbolic()
			);

			if(!$interestEntry) {
				$interestEntry = $interestEntryDao->newDataObject(); /* @var $interestEntry InterestEntry */
				$interestEntry->setInterest($interest);
				$interestEntry->setControlledVocabId($controlledVocab->getId());
				$interestEntry->setId($interestEntryDao->insertObject($interestEntry));
			}

			$this->update(
				'INSERT INTO user_interests (user_id, controlled_vocab_entry_id) VALUES (?, ?)',
				array((int) $userId, (int) $interestEntry->getId())
			);
		}
	}
}

?>
