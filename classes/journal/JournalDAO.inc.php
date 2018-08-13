<?php

/**
 * @file classes/journal/JournalDAO.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
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
import('lib.pkp.classes.metadata.MetadataTypeDescription');

define('JOURNAL_FIELD_TITLE', 1);
define('JOURNAL_FIELD_SEQUENCE', 2);

class JournalDAO extends ContextDAO {

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
	 * Update an existing journal.
	 * @param $journal Journal
	 */
	function updateObject($journal) {
		return $this->update(
			'UPDATE journals
			SET	path = ?,
				seq = ?,
				enabled = ?,
				primary_locale = ?
			WHERE journal_id = ?',
			array(
				$journal->getPath(),
				(float) $journal->getSequence(),
				$journal->getEnabled() ? 1 : 0,
				$journal->getPrimaryLocale(),
				(int) $journal->getId()
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
		$emailTemplateDao->deleteEmailTemplatesByContext($journalId);

		$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
		$subscriptionDao->deleteByJournalId($journalId);
		$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		$subscriptionDao->deleteByJournalId($journalId);

		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionTypeDao->deleteByJournal($journalId);

		$announcementDao = DAORegistry::getDAO('AnnouncementDAO');
		$announcementDao->deleteByAssoc(ASSOC_TYPE_JOURNAL, $journalId);

		$announcementTypeDao = DAORegistry::getDAO('AnnouncementTypeDAO');
		$announcementTypeDao->deleteByAssoc(ASSOC_TYPE_JOURNAL, $journalId);

		$articleDao = DAORegistry::getDAO('ArticleDAO');
		$articleDao->deleteByContextId($journalId);

		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO');
		$pluginSettingsDao->deleteByContextId($journalId);

		$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');
		$reviewFormDao->deleteByAssoc(ASSOC_TYPE_JOURNAL, $journalId);

		parent::deleteById($journalId);
	}

	/**
	 * Retrieve the IDs and titles of all journals in an associative array.
	 * @return array
	 */
	function getTitles($enabledOnly = false) {
		$journals = array();
		$journalIterator = $this->getAll($enabledOnly);
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
		$pubObjectDaos = array('IssueDAO', 'ArticleDAO', 'ArticleGalleyDAO');
		foreach($pubObjectDaos as $daoName) {
			$dao = DAORegistry::getDAO($daoName);
			$dao->deleteAllPubIds($journalId, $pubIdType);
		}
		import('lib.pkp.classes.submission.SubmissionFileDAODelegate');
		$submissionFileDaoDelegate = new SubmissionFileDAODelegate();
		$submissionFileDaoDelegate->deleteAllPubIds($journalId, $pubIdType);

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
	 * @param $forSameType boolean Whether only the same objects should be considered.
	 * @return boolean
	 */
	function anyPubIdExists($journalId, $pubIdType, $pubId,
			$assocType = ASSOC_TYPE_ANY, $assocId = 0, $forSameType = false) {

		$pubObjectDaos = array(
			ASSOC_TYPE_ISSUE => DAORegistry::getDAO('IssueDAO'),
			ASSOC_TYPE_ARTICLE => Application::getSubmissionDAO(),
			ASSOC_TYPE_GALLEY => Application::getRepresentationDAO(),
			ASSOC_TYPE_ISSUE_GALLEY => DAORegistry::getDAO('IssueGalleyDAO'),
			ASSOC_TYPE_SUBMISSION_FILE => DAORegistry::getDAO('SubmissionFileDAO')
		);
		if ($forSameType) {
			$dao = $pubObjectDaos[$assocType];
			$excludedId = $assocId;
			if ($dao->pubIdExists($pubIdType, $pubId, $excludedId, $journalId)) return true;
			return false;
		}
		foreach($pubObjectDaos as $daoAssocType => $dao) {
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
