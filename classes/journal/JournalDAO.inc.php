<?php

/**
 * @file classes/journal/JournalDAO.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalDAO
 * @ingroup journal
 * @see Journal
 *
 * @brief Operations for retrieving and modifying Journal objects.
 */

import ('classes.journal.Journal');
import('lib.pkp.classes.metadata.MetadataTypeDescription');

define('JOURNAL_FIELD_TITLE', 1);
define('JOURNAL_FIELD_SEQUENCE', 2);

class JournalDAO extends DAO {
	/**
	 * Retrieve a journal by ID.
	 * @param $journalId int
	 * @return Journal
	 */
	function &getById($journalId) {
		$result =& $this->retrieve(
			'SELECT * FROM journals WHERE journal_id = ?',
			(int) $journalId
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
	 * Deprecated. @see JournalDAO::getById
	 */
	function &getJournal($journalId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		$returner =& $this->getById($journalId);
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
		if (HookRegistry::call('JournalDAO::deleteJournalById', array(&$this, &$journalId))) return;

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

		$giftDao =& DAORegistry::getDAO('GiftDAO');
		$giftDao->deleteGiftsByAssocId(ASSOC_TYPE_JOURNAL, $journalId);

		$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
		$announcementDao->deleteByAssoc(ASSOC_TYPE_JOURNAL, $journalId);

		$announcementTypeDao =& DAORegistry::getDAO('AnnouncementTypeDAO');
		$announcementTypeDao->deleteByAssoc(ASSOC_TYPE_JOURNAL, $journalId);

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
	 * @param $enabledOnly boolean True iff only enabled jourals wanted
	 * @param $rangeInfo object optional
	 * @param $sortBy JOURNAL_FIELD_... optional sorting parameter
	 * @param $searchField JOURNAL_FIELD_... optional filter parameter
	 * @param $searchMatch string 'is', 'contains', 'startsWith' optional
	 * @param $search string optional
	 * @return DAOResultFactory containing matching journals
	 */
	function &getJournals($enabledOnly = false, $rangeInfo = null, $sortBy = JOURNAL_FIELD_SEQUENCE, $searchField = null, $searchMatch = null, $search = null) {
		$joinSql = $whereSql = $orderBySql = '';
		$params = array();
		$needTitleJoin = false;

		// Handle sort conditions
		switch ($sortBy) {
			case JOURNAL_FIELD_TITLE:
				$needTitleJoin = true;
				$orderBySql = 'COALESCE(jsl.setting_value, jsl.setting_name)';
				break;
			case JOURNAL_FIELD_SEQUENCE:
				$orderBySql = 'j.seq';
				break;
		}

		// Handle search conditions
		switch ($searchField) {
			case JOURNAL_FIELD_TITLE:
				$needTitleJoin = true;
				$whereSql .= ($whereSql?' AND ':'') . ' COALESCE(jsl.setting_value, jsl.setting_name) ';
				switch ($searchMatch) {
					case 'is':
						$whereSql .= ' = ?';
						$params[] = $search;
						break;
					case 'contains':
						$whereSql .= ' LIKE ?';
						$params[] = "%search%";
						break;
					default: // $searchMatch === 'startsWith'
						$whereSql .= ' LIKE ?';
						$params[] = "$search%";
						break;
				}
				break;
		}

		// If we need to join on the journal title (for sort or filter),
		// include it.
		if ($needTitleJoin) {
			$joinSql .= ' LEFT JOIN journal_settings jspl ON (jspl.setting_name = ? AND jspl.locale = ? AND jspl.journal_id = j.journal_id) LEFT JOIN journal_settings jsl ON (jsl.setting_name = ? AND jsl.locale = ? AND jsl.journal_id = j.journal_id)';
			$params = array_merge(
				array(
					'title',
					AppLocale::getPrimaryLocale(),
					'title',
					AppLocale::getLocale()
				),
				$params
			);
		}

		// Handle filtering conditions
		if ($enabledOnly) $whereSql .= ($whereSql?'AND ':'') . 'j.enabled=1 ';

		// Clean up SQL strings
		if ($whereSql) $whereSql = "WHERE $whereSql";
		if ($orderBySql) $orderBySql = "ORDER BY $orderBySql";
		$result =& $this->retrieveRange(
			"SELECT	j.*
			FROM	journals j
				$joinSql
				$whereSql
				$orderBySql",
			$params, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnJournalFromRow');
		return $returner;
	}

	/**
	 * Retrieve all enabled journals
	 * @return array Journals ordered by sequence
	 */
	function &getEnabledJournals($rangeInfo = null) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		$returner =& $this->getJournals(true, $rangeInfo);
		return $returner;
	}

	/**
	 * Retrieve the IDs and titles of all journals in an associative array.
	 * @return array
	 */
	function &getJournalTitles($enabledOnly = false) {
		$journals = array();

		$journalIterator =& $this->getJournals($enabledOnly);
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
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		$titles =& $this->getJournalTitles(true);
		return $titles;
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
	 * Delete the public IDs of all publishing objects in a journal.
	 * @param $journalId int
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 */
	function deleteAllPubIds($journalId, $pubIdType) {
		$pubObjectDaos = array('IssueDAO', 'ArticleDAO', 'ArticleGalleyDAO', 'SuppFileDAO');
		foreach($pubObjectDaos as $daoName) {
			$dao =& DAORegistry::getDAO($daoName);
			$dao->deleteAllPubIds($journalId, $pubIdType);
			unset($dao);
		}
	}

	/**
	 * Check whether the given public ID exists for any publishing
	 * object in a journal.
	 * @param $journalId int
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @param $pubId string
	 * @param $assocType int The object type of an object to be excluded from
	 *  the search. Identified by one of the ASSOC_TYPE_* constants.
	 * @param $assocId int The id of an object to be excluded from the search.
	 * @return boolean
	 */
	function anyPubIdExists($journalId, $pubIdType, $pubId,
			$assocType = ASSOC_TYPE_ANY, $assocId = 0) {
		$pubObjectDaos = array(
			ASSOC_TYPE_ISSUE => 'IssueDAO',
			ASSOC_TYPE_ARTICLE => 'ArticleDAO',
			ASSOC_TYPE_GALLEY => 'ArticleGalleyDAO',
			ASSOC_TYPE_ISSUE_GALLEY => 'IssueGalleyDAO',
			ASSOC_TYPE_SUPP_FILE => 'SuppFileDAO'
		);
		foreach($pubObjectDaos as $daoAssocType => $daoName) {
			$dao =& DAORegistry::getDAO($daoName);
			if ($assocType == $daoAssocType) {
				$excludedId = $assocId;
			} else {
				$excludedId = 0;
			}
			if ($dao->pubIdExists($pubIdType, $pubId, $excludedId, $journalId)) return true;
			unset($dao);
		}
		return false;
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
