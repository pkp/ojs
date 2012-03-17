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

class ArticleTombstoneDAO extends DAO {
	/**
	 * Constructor.
	 */
	function ArticleTombstoneDAO() {
		parent::DAO();
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return ArticleTombstone
	 */
	function newDataObject() {
		return new ArticleTombstone();
	}

	/**
	 * Retrieve ArticleTombstone by id
	 * @param $tombstoneId int
	 * @return ArticleTombstone object
	 */
	function &getById($tombstoneId, $journalId = null) {
		$params = array((int) $tombstoneId);
		if ($journalId !== null) $params[] = (int) $journalId;
		$result =& $this->retrieve(
			'SELECT * FROM article_tombstones WHERE tombstone_id = ?'
			. ($journalId !== null ? ' AND journal_id = ?' : ''),
			$params
		);

		$articleTombstone =& $this->_fromRow($result->GetRowAssoc(false));

		$result->Close();
		unset($result);

		return $articleTombstone;
	}

	/**
	 * Retrieve ArticleTombstone by article id
	 * @param $articleId int
	 * @return ArticleTombstone object
	 */
	function &getByArticleId($articleId) {
		$result =& $this->retrieve(
			'SELECT * FROM article_tombstones WHERE submission_id = ?', (int) $articleId
		);

		$articleTombstone =& $this->_fromRow($result->GetRowAssoc(false));

		$result->Close();
		unset($result);

		return $articleTombstone;
	}

	/**
	 * Creates and returns an article tombstone object from a row
	 * @param $row array
	 * @return ArticleTombstone object
	 */
	function &_fromRow($row) {
		$articleTombstone = $this->newDataObject();
		$articleTombstone->setId($row['tombstone_id']);
		$articleTombstone->setJournalId($row['journal_id']);
		$articleTombstone->setSubmissionId($row['submission_id']);
		$articleTombstone->setDateDeleted($this->datetimeFromDB($row['date_deleted']));
		$articleTombstone->setSectionId($row['section_id']);
		$articleTombstone->setSetSpec($row['set_spec']);
		$articleTombstone->setSetName($row['set_name']);
		$articleTombstone->setOAIIdentifier($row['oai_identifier']);

		HookRegistry::call('ArticleTombstoneDAO::_fromRow', array(&$articleTombstone, &$row));

		return $articleTombstone;
	}

	/**
	 * Inserts a new article tombstone into article_tombstones table
	 * @param ArticleTombstone object
	 * @return int ArticleTombstone Id
	 */
	function insertObject(&$articleTombstone) {
		$this->update(
			sprintf('INSERT INTO article_tombstones
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
	 * Update an article tombstone in the article_tombstones table
	 * @param ArticleTombstone object
	 * @return int ArticleTombstone Id
	 */
	function updateObject(&$articleTombstone) {
		$returner = $this->update(
			sprintf('UPDATE	article_tombstones SET
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
	 * Delete ArticleTombstone by tombstone id
	 * @param $tombstoneId int
	 * @return boolean
	 */
	function deleteById($tombstoneId, $journalId = null) {
		$params = array((int) $tombstoneId);
		if (isset($journalId)) $params[] = (int) $journalId;

		$this->update('DELETE FROM article_tombstones WHERE tombstone_id = ?' . (isset($journalId) ? ' AND journal_id = ?' : ''),
			$params
		);
		if ($this->getAffectedRows()) {
			$articleTombstoneSettingsDao =& DAORegistry::getDAO('ArticleTombstoneSettingsDAO');
			return $articleTombstoneSettingsDao->deleteSettings($tombstoneId);
		}
		return false;
	}

	/**
	 * Delete ArticleTombstone by article id
	 * @param $articleId int
	 * @return boolean
	 */
	function deleteByArticleId($articleId) {
		$articleTombstone =& $this->getByArticleId($articleId);
		return $this->deleteById($articleTombstone->getId());
	}

	/**
	 * Retrieve all sets for article tombstones of a journal.
	 * @return array('setSpec' => setName)
	 */
	function &getSets($journalId) {
		$returner = array();

		$result =& $this->retrieve(
			'SELECT DISTINCT set_spec, set_name FROM article_tombstones WHERE journal_id = ?',
			(int) $journalId
		);

		while (!$result->EOF) {
			$returner[$result->fields[0]] = $result->fields[1];
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get the ID of the last inserted article tombstone
	 * @return int
	 */
	function getInsertTombstoneId() {
		return $this->getInsertId('article_tombstones', 'tombstone_id');
	}
}

?>