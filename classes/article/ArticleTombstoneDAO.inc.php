<?php

/**
 * @file classes/article/ArticleTombstoneDAO.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleTombstoneDAO
 * @ingroup article
 * @see ArticleTombstoneDAO
 *
 * @brief Operations for retrieving and modifying ArticleTombstone objects.
 */

import ('classes.article.ArticleTombstone');
import ('lib.pkp.classes.submission.SubmissionTombstoneDAO');

class ArticleTombstoneDAO extends SubmissionTombstoneDAO {
	/**
	 * Constructor.
	 */
	function ArticleTombstoneDAO() {
		parent::SubmissionTombstoneDAO();
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return ArticleTombstone
	 */
	function newDataObject() {
		return new ArticleTombstone();
	}

	/**
	 * @see lib/pkp/classes/submission/SubmissionTombstoneDAO::getById()
	 * @param $tombstoneId int
	 * @param $journalId int
	 */
	function &getById($tombstoneId, $journalId = null) {
		return parent::getById($tombstoneId, $journalId, 'journal_id');
	}

	/**
	 * @see lib/pkp/classes/submission/SubmissionTombstoneDAO::_fromRow()
	 */
	function &_fromRow($row) {
		$articleTombstone =& parent::_fromRow($row);
		$articleTombstone->setJournalId($row['journal_id']);
		$articleTombstone->setSectionId($row['section_id']);

		HookRegistry::call('ArticleTombstoneDAO::_fromRow', array(&$articleTombstone, &$row));

		return $articleTombstone;
	}

	/**
	 * Inserts a new submission tombstone into submission_tombstones table.
	 * @param $submissionTombstone SubmissionTombstone
	 * @return int Submission tombstone id.
	 */
	function insertObject(&$articleTombstone) {
		$this->update(
			sprintf('INSERT INTO submission_tombstones
				(journal_id, submission_id, date_deleted, section_id, set_spec, set_name, oai_identifier)
				VALUES
				(?, ?, %s, ?, ?, ?, ?)',
				$this->datetimeToDB(date('Y-m-d H:i:s'))
			),
			array(
				(int) $articleTombstone->getJournalId(),
				(int) $articleTombstone->getSubmissionId(),
				(int) $articleTombstone->getSectionId(),
				$articleTombstone->getSetSpec(),
				$articleTombstone->getSetName(),
				$articleTombstone->getOAIIdentifier()
			)
		);

		$articleTombstone->setId($this->getInsertTombstoneId());

		return $articleTombstone->getId();
	}

	/**
	 * Update a submission tombstone in the submission_tombstones table
	 * @param SubmissionTombstone object
	 * @return int SubmissionTombstone Id
	 */
	function updateObject(&$articleTombstone) {
		$returner = $this->update(
			sprintf('UPDATE	submission_tombstones SET
					journal_id = ?,
					submission_id = ?,
					date_deleted = %s,
					section_id = ?,
					set_spec = ?,
					set_name = ?,
					oai_identifier = ?
					WHERE	tombstone_id = ?',
				$this->datetimeToDB(date('Y-m-d H:i:s'))
			),
			array(
				(int) $articleTombstone->getJournalId(),
				(int) $articleTombstone->getSubmissionId(),
				(int) $articleTombstone->getSectionId(),
				$articleTombstone->getSetSpec(),
				$articleTombstone->getSetName(),
				$articleTombstone->getOAIIdentifier(),
				(int) $articleTombstone->getId()
			)
		);

		return $returner;
	}

	/**
	 * @see lib/pkp/classes/submission/SubmissionTombstoneDAO::deleteById()
	 * @param $tombstoneId int
	 * @param $journalId int
	 */
	function deleteById($tombstoneId, $journalId = null) {
		return parent::deleteById($tombstoneId, $journalId, 'journal_id');
	}

	/**
	 * @see lib/pkp/classes/submission/SubmissionTombstoneDAO::getSets()
	 * @param $journalId int
	 */
	function &getSets($journalId) {
		return parent::getSets($journalId, 'journal_id');
	}
}

?>