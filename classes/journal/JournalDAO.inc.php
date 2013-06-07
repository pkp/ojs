<?php

/**
 * @file classes/journal/JournalDAO.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalDAO
 * @ingroup journal
 * @see Journal
 *
 * @brief Operations for retrieving and modifying Journal objects.
 */

import('lib.pkp.classes.context.ContextDAO');
import('classes.journal.Journal');

define('JOURNAL_FIELD_TITLE', 1);
define('JOURNAL_FIELD_SEQUENCE', 2);

class JournalDAO extends ContextDAO {
	/**
	 * Constructor
	 */
	function JournalDAO() {
		parent::ContextDAO();
	}

	/**
	 * Construct a new Journal.
	 * @return DataObject
	 */
	function newDataObject() {
		return new Journal();
	}

	/**
	 * Internal function to return a Journal object from a row.
	 * @param $row array
	 * @return Journal
	 */
	function _fromRow($row) {
		$journal = parent::_fromRow($row);
		$journal->setPrimaryLocale($row['primary_locale']);
		$journal->setEnabled($row['enabled']);
		HookRegistry::call('JournalDAO::_returnJournalFromRow', array(&$journal, &$row));
		return $journal;
	}

	/**
	 * Insert a new journal.
	 * @param $journal Journal
	 */
	function insertObject(&$journal) {
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

		$journal->setId($this->getInsertId());
		return $journal->getId();
	}

	/**
	 * Update an existing journal.
	 * @param $journal Journal
	 */
	function updateObject(&$journal) {
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
	 * Delete a journal by ID, INCLUDING ALL DEPENDENT ITEMS.
	 * @param $journalId int
	 */
	function deleteById($journalId) {
		$journalSettingsDao = DAORegistry::getDAO('JournalSettingsDAO');
		$journalSettingsDao->deleteById($journalId);

		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$sectionDao->deleteByJournalId($journalId);

		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issueDao->deleteByJournalId($journalId);

		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplateDao->deleteEmailTemplatesByJournal($journalId);

		$rtDao = DAORegistry::getDAO('RTDAO');
		$rtDao->deleteVersionsByJournal($journalId);

		$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
		$subscriptionDao->deleteSubscriptionsByJournal($journalId);
		$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		$subscriptionDao->deleteSubscriptionsByJournal($journalId);

		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionTypeDao->deleteSubscriptionTypesByJournal($journalId);

		$giftDao = DAORegistry::getDAO('GiftDAO');
		$giftDao->deleteGiftsByAssocId(ASSOC_TYPE_JOURNAL, $journalId);

		$announcementDao = DAORegistry::getDAO('AnnouncementDAO');
		$announcementDao->deleteByAssoc(ASSOC_TYPE_JOURNAL, $journalId);

		$announcementTypeDao = DAORegistry::getDAO('AnnouncementTypeDAO');
		$announcementTypeDao->deleteByAssoc(ASSOC_TYPE_JOURNAL, $journalId);

		$articleDao = DAORegistry::getDAO('ArticleDAO');
		$articleDao->deleteByJournalId($journalId);

		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO');
		$pluginSettingsDao->deleteById($journalId);

		$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');
		$reviewFormDao->deleteByAssoc(ASSOC_TYPE_JOURNAL, $journalId);

		parent::deleteById($journalId);
	}

	/**
	 * Retrieve all journals.
	 * @param $enabledOnly boolean True to return enabled journals only
	 * @param $rangeInfo object optional
	 * @param $sortBy JOURNAL_FIELD_... optional sorting parameter
	 * @param $searchField JOURNAL_FIELD_... optional filter parameter
	 * @param $searchMatch string 'is', 'contains', 'startsWith' optional
	 * @param $search string optional
	 * @return DAOResultFactory containing matching journals
	 */
	function getJournals($enabledOnly = false, $rangeInfo = null, $sortBy = JOURNAL_FIELD_SEQUENCE, $searchField = null, $searchMatch = null, $search = null) {
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

		// If we need to join on the journal name (for sort or filter),
		// include it.
		if ($needTitleJoin) {
			$joinSql .= ' LEFT JOIN journal_settings jspl ON (jspl.setting_name = ? AND jspl.locale = ? AND jspl.journal_id = j.journal_id) LEFT JOIN journal_settings jsl ON (jsl.setting_name = ? AND jsl.locale = ? AND jsl.journal_id = j.journal_id)';
			$params = array_merge(
				array(
					'name',
					AppLocale::getPrimaryLocale(),
					'name',
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
		$result = $this->retrieveRange(
			"SELECT	j.*
			FROM	journals j
				$joinSql
				$whereSql
				$orderBySql",
			$params, $rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Retrieve the IDs and titles of all journals in an associative array.
	 * @return array
	 */
	function &getTitles($enabledOnly = false) {
		$journals = array();
		$journalIterator = $this->getJournals($enabledOnly);
		while ($journal = $journalIterator->next()) {
			$journals[$journal->getId()] = $journal->getLocalizedName();
		}
		return $journals;
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
			$dao = DAORegistry::getDAO($daoName);
			$dao->deleteAllPubIds($journalId, $pubIdType);
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
			$dao = DAORegistry::getDAO($daoName);
			if ($assocType == $daoAssocType) {
				$excludedId = $assocId;
			} else {
				$excludedId = 0;
			}
			if ($dao->pubIdExists($pubIdType, $pubId, $excludedId, $journalId)) return true;
		}
		return false;
	}

	//
	// Protected methods
	//
	/**
	 * Get the table name for this context.
	 * @return string
	 */
	protected function _getTableName() {
		return 'journals';
	}

	/**
	 * Get the table name for this context's settings table.
	 * @return string
	 */
	protected function _getSettingsTableName() {
		return 'journal_settings';
	}

	/**
	 * Get the name of the primary key column for this context.
	 * @return string
	 */
	protected function _getPrimaryKeyColumn() {
		return 'journal_id';
	}
}

?>
