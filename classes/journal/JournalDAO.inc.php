<?php

/**
 * @file classes/journal/JournalDAO.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalDAO
 * @ingroup journal
 * @see Journal
 *
 * @brief Operations for retrieving and modifying Journal objects.
 */

// $Id$


import ('classes.journal.Journal');

class JournalDAO extends DAO {
	/**
	 * Retrieve a journal by ID.
	 * @param $journalId int
	 * @return Journal
	 */
	function &getJournal($journalId) {
		$result =& $this->retrieve(
			'SELECT * FROM journals WHERE journal_id = ?', $journalId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnJournalFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		unset($result);
		return $returner;
	}

	/**
	 * Retrieve a journal by path.
	 * @param $path string
	 * @return Journal
	 */
	function &getJournalByPath($path) {
		$returner = null;
		$result =& $this->retrieve(
			'SELECT * FROM journals WHERE path = ?', $path
		);

		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnJournalFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		unset($result);
		return $returner;
	}

	/**
	 * Internal function to return a Journal object from a row.
	 * @param $row array
	 * @return Journal
	 */
	function &_returnJournalFromRow(&$row) {
		$journal = new Journal();
		$journal->setId($row['journal_id']);
		$journal->setPath($row['path']);
		$journal->setSequence($row['seq']);
		$journal->setEnabled($row['enabled']);
		$journal->setPrimaryLocale($row['primary_locale']);

		HookRegistry::call('JournalDAO::_returnJournalFromRow', array(&$journal, &$row));

		return $journal;
	}

	/**
	 * Insert a new journal.
	 * @param $journal Journal
	 */
	function insertJournal(&$journal) {
		$this->update(
			'INSERT INTO journals
				(path, seq, enabled, primary_locale)
				VALUES
				(?, ?, ?, ?)',
			array(
				$journal->getPath(),
				$journal->getSequence() == null ? 0 : $journal->getSequence(),
				$journal->getEnabled() ? 1 : 0,
				$journal->getPrimaryLocale()
			)
		);

		$journal->setId($this->getInsertJournalId());
		return $journal->getId();
	}

	/**
	 * Update an existing journal.
	 * @param $journal Journal
	 */
	function updateJournal(&$journal) {
		return $this->update(
			'UPDATE journals
				SET
					path = ?,
					seq = ?,
					enabled = ?,
					primary_locale = ?
				WHERE journal_id = ?',
			array(
				$journal->getPath(),
				$journal->getSequence(),
				$journal->getEnabled() ? 1 : 0,
				$journal->getPrimaryLocale(),
				$journal->getId()
			)
		);
	}

	/**
	 * Delete a journal, INCLUDING ALL DEPENDENT ITEMS.
	 * @param $journal Journal
	 */
	function deleteJournal(&$journal) {
		return $this->deleteJournalById($journal->getId());
	}

	/**
	 * Delete a journal by ID, INCLUDING ALL DEPENDENT ITEMS.
	 * @param $journalId int
	 */
	function deleteJournalById($journalId) {
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$journalSettingsDao->deleteSettingsByJournal($journalId);

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$sectionDao->deleteSectionsByJournal($journalId);

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issueDao->deleteIssuesByJournal($journalId);

		$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplateDao->deleteEmailTemplatesByJournal($journalId);

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$rtDao->deleteVersionsByJournal($journalId);

		$subscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
		$subscriptionDao->deleteSubscriptionsByJournal($journalId);
		$subscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		$subscriptionDao->deleteSubscriptionsByJournal($journalId);

		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionTypeDao->deleteSubscriptionTypesByJournal($journalId);

		$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
		$announcementDao->deleteAnnouncementsByAssocId(ASSOC_TYPE_JOURNAL, $journalId);

		$announcementTypeDao =& DAORegistry::getDAO('AnnouncementTypeDAO');
		$announcementTypeDao->deleteAnnouncementTypesByAssocId(ASSOC_TYPE_JOURNAL, $journalId);

		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$articleDao->deleteArticlesByJournalId($journalId);

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$roleDao->deleteRoleByJournalId($journalId);

		$groupDao =& DAORegistry::getDAO('GroupDAO');
		$groupDao->deleteGroupsByAssocId(ASSOC_TYPE_JOURNAL, $journalId);

		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
		$pluginSettingsDao->deleteSettingsByJournalId($journalId);

		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewFormDao->deleteByAssocId(ASSOC_TYPE_JOURNAL, $journalId);

		return $this->update(
			'DELETE FROM journals WHERE journal_id = ?', $journalId
		);
	}

	/**
	 * Retrieve all journals.
	 * @return DAOResultFactory containing matching journals
	 */
	function &getJournals($rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT * FROM journals ORDER BY seq',
			false, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnJournalFromRow');
		return $returner;
	}

	/**
	 * Retrieve all enabled journals
	 * @return array Journals ordered by sequence
	 */
	function &getEnabledJournals() {
		$result =& $this->retrieve(
			'SELECT * FROM journals WHERE enabled=1 ORDER BY seq'
		);

		$resultFactory = new DAOResultFactory($result, $this, '_returnJournalFromRow');
		return $resultFactory;
	}

	/**
	 * Retrieve the IDs and titles of all journals in an associative array.
	 * @return array
	 */
	function &getJournalTitles() {
		$journals = array();

		$journalIterator =& $this->getJournals();
		while ($journal =& $journalIterator->next()) {
			$journals[$journal->getId()] = $journal->getLocalizedTitle();
			unset($journal);
		}
		unset($journalIterator);

		return $journals;
	}

	/**
	 * Retrieve enabled journal IDs and titles in an associative array
	 * @return array
	 */
	function &getEnabledJournalTitles() {
		$journals = array();

		$journalIterator =& $this->getEnabledJournals();
		while ($journal =& $journalIterator->next()) {
			$journals[$journal->getId()] = $journal->getLocalizedTitle();
			unset($journal);
		}
		unset($journalIterator);

		return $journals;
	}

	/**
	 * Check if a journal exists with a specified path.
	 * @param $path the path of the journal
	 * @return boolean
	 */
	function journalExistsByPath($path) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM journals WHERE path = ?', $path
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Sequentially renumber journals in their sequence order.
	 */
	function resequenceJournals() {
		$result =& $this->retrieve(
			'SELECT journal_id FROM journals ORDER BY seq'
		);

		for ($i=1; !$result->EOF; $i++) {
			list($journalId) = $result->fields;
			$this->update(
				'UPDATE journals SET seq = ? WHERE journal_id = ?',
				array(
					$i,
					$journalId
				)
			);

			$result->moveNext();
		}

		$result->close();
		unset($result);
	}

	/**
	 * Get the ID of the last inserted journal.
	 * @return int
	 */
	function getInsertJournalId() {
		return $this->getInsertId('journals', 'journal_id');
	}
}

?>
