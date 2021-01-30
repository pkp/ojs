<?php

/**
 * @file classes/journal/JournalDAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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
	/** @copydoc SchemaDAO::$schemaName */
	var $schemaName = 'context';

	/** @copydoc SchemaDAO::$tableName */
	var $tableName = 'journals';

	/** @copydoc SchemaDAO::$settingsTableName */
	var $settingsTableName = 'journal_settings';

	/** @copydoc SchemaDAO::$primaryKeyColumn */
	var $primaryKeyColumn = 'journal_id';

	/** @var array Maps schema properties for the primary table to their column names */
	var $primaryTableColumns = [
		'id' => 'journal_id',
		'urlPath' => 'path',
		'enabled' => 'enabled',
		'seq' => 'seq',
		'primaryLocale' => 'primary_locale',
	];

	/**
	 * Create a new DataObject of the appropriate class
	 *
	 * @return DataObject
	 */
	public function newDataObject() {
		return new Journal();
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
		$pubObjectDaos = array('IssueDAO', 'PublicationDAO', 'ArticleGalleyDAO');
		foreach($pubObjectDaos as $daoName) {
			$dao = DAORegistry::getDAO($daoName);
			$dao->deleteAllPubIds($journalId, $pubIdType);
		}
		import('classes.submission.SubmissionFileDAO');
		$submissionFileDao = new SubmissionFileDAO();
		$submissionFileDao->deleteAllPubIds($journalId, $pubIdType);

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
			ASSOC_TYPE_SUBMISSION => DAORegistry::getDAO('SubmissionDAO'),
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
}
