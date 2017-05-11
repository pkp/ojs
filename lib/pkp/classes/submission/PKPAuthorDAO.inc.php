<?php

/**
 * @file classes/submission/PKPAuthorDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPAuthorDAO
 * @ingroup submission
 * @see PKPAuthor
 *
 * @brief Operations for retrieving and modifying PKPAuthor objects.
 */


import('lib.pkp.classes.submission.PKPAuthor');

abstract class PKPAuthorDAO extends DAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Retrieve an author by ID.
	 * @param $authorId int Author ID
	 * @param $submissionId int Optional submission ID to correlate the author against.
	 * @return Author
	 */
	function getById($authorId, $submissionId = null) {
		$params = array((int) $authorId);
		if ($submissionId !== null) $params[] = (int) $submissionId;
		$result = $this->retrieve(
			'SELECT a.*,
				ug.show_title
			FROM	authors a
				JOIN user_groups ug ON (a.user_group_id=ug.user_group_id)
			WHERE	a.author_id = ?'
				. ($submissionId !== null?' AND a.submission_id = ?':''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve all authors for a submission.
	 * @param $submissionId int Submission ID.
	 * @param $sortByAuthorId bool Use author Ids as indexes in the array
	 * @param $useIncludeInBrowse bool Whether to limit to just include_in_browse authors
	 * @return array Authors ordered by sequence
	 */
	function getBySubmissionId($submissionId, $sortByAuthorId = false, $useIncludeInBrowse = false) {
		$authors = array();
		$params = array((int) $submissionId);
		if ($useIncludeInBrowse) $params[] = 1;

		$result = $this->retrieve(
			'SELECT	a.*, ug.show_title
			FROM	authors a
				JOIN user_groups ug ON (a.user_group_id=ug.user_group_id)
			WHERE	a.submission_id = ? ' .
			($useIncludeInBrowse ? ' AND a.include_in_browse = ?' : '')
			. ' ORDER BY seq',
			$params
		);

		while (!$result->EOF) {
			$row = $result->getRowAssoc(false);
			if ($sortByAuthorId) {
				$authorId = $row['author_id'];
				$authors[$authorId] = $this->_fromRow($row);
			} else {
				$authors[] = $this->_fromRow($row);
			}
			$result->MoveNext();
		}

		$result->Close();
		return $authors;
	}

	/**
	 * Retrieve the number of authors assigned to a submission
	 * @param $submissionId int Submission ID.
	 * @return int
	 */
	function getAuthorCountBySubmissionId($submissionId) {
		$result = $this->retrieve(
			'SELECT COUNT(*) FROM authors WHERE submission_id = ?',
			(int) $submissionId
		);

		$returner = $result->fields[0];

		$result->Close();
		return $returner;
	}

	/**
	 * Update the localized data for this object
	 * @param $author object
	 */
	function updateLocaleFields($author) {
		$this->updateDataObjectSettings(
			'author_settings',
			$author,
			array(
				'author_id' => $author->getId()
			)
		);
	}

	/**
	 * Internal function to return an Author object from a row.
	 * @param $row array
	 * @return Author
	 */
	function _fromRow($row) {
		$author = $this->newDataObject();
		$author->setId($row['author_id']);
		$author->setSubmissionId($row['submission_id']);
		$author->setFirstName($row['first_name']);
		$author->setMiddleName($row['middle_name']);
		$author->setLastName($row['last_name']);
		$author->setSuffix($row['suffix']);
		$author->setCountry($row['country']);
		$author->setEmail($row['email']);
		$author->setUrl($row['url']);
		$author->setUserGroupId($row['user_group_id']);
		$author->setPrimaryContact($row['primary_contact']);
		$author->setSequence($row['seq']);
		$author->setIncludeInBrowse($row['include_in_browse']);
		$author->_setShowTitle($row['show_title']); // Dependent

		$this->getDataObjectSettings('author_settings', 'author_id', $row['author_id'], $author);

		HookRegistry::call('AuthorDAO::_fromRow', array(&$author, &$row));
		return $author;
	}

	/**
	 * Internal function to return an Author object from a row. Simplified
	 * not to include object settings.
	 * @param $row array
	 * @return Author
	 */
	function _returnSimpleAuthorFromRow($row) {
		$author = $this->newDataObject();
		$author->setId($row['author_id']);
		$author->setSubmissionId($row['submission_id']);
		$author->setFirstName($row['first_name']);
		$author->setMiddleName($row['middle_name']);
		$author->setLastName($row['last_name']);
		$author->setSuffix($row['suffix']);
		$author->setCountry($row['country']);
		$author->setEmail($row['email']);
		$author->setUrl($row['url']);
		$author->setUserGroupId($row['user_group_id']);
		$author->setPrimaryContact($row['primary_contact']);
		$author->setSequence($row['seq']);
		$author->setIncludeInBrowse($row['include_in_browse'] == 1 ? true : false);

		$author->setAffiliation($row['affiliation_l'], $row['locale']);
		$author->setAffiliation($row['affiliation_pl'], $row['primary_locale']);

		HookRegistry::call('AuthorDAO::_returnSimpleAuthorFromRow', array(&$author, &$row));
		return $author;
	}

	/**
	 * Get a new data object
	 * @return DataObject
	 */
	abstract function newDataObject();

	/**
	 * Get field names for which data is localized.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('biography', 'competingInterests', 'affiliation');
	}

	/**
	 * @copydoc DAO::getAdditionalFieldNames()
	 */
	function getAdditionalFieldNames() {
		return array_merge(parent::getAdditionalFieldNames(), array(
			'orcid',
		));
	}

	/**
	 * Insert a new Author.
	 * @param $author Author
	 */
	function insertObject($author) {
		// Set author sequence to end of author list
		if(!$author->getSequence()) {
			$authorCount = $this->getAuthorCountBySubmissionId($author->getSubmissionId());
			$author->setSequence($authorCount + 1);
		}
		// Reset primary contact for submission to this author if applicable
		if ($author->getPrimaryContact()) {
			$this->resetPrimaryContact($author->getId(), $author->getSubmissionId());
		}

		$this->update(
			'INSERT INTO authors (
				submission_id, first_name, middle_name, last_name, suffix, country,
				email, url, user_group_id, primary_contact, seq, include_in_browse
			) VALUES (
				?, ?, ?, ?, ?, ?,
				?, ?, ?, ?, ?, ?
			)',
				array(
						(int) $author->getSubmissionId(),
						$author->getFirstName(),
						$author->getMiddleName() . '', // make non-null
						$author->getLastName(),
						$author->getSuffix() . '',
						$author->getCountry(),
						$author->getEmail(),
						$author->getUrl(),
						(int) $author->getUserGroupId(),
						(int) $author->getPrimaryContact(),
						(float) $author->getSequence(),
						(int) $author->getIncludeInBrowse() ? 1 : 0,
				)
		);

		$author->setId($this->getInsertId());
		$this->updateLocaleFields($author);

		return $author->getId();
	}

	/**
	 * Update an existing Author.
	 * @param $author Author
	 */
	function updateObject($author) {
		// Reset primary contact for submission to this author if applicable
		if ($author->getPrimaryContact()) {
			$this->resetPrimaryContact($author->getId(), $author->getSubmissionId());
		}
		$returner = $this->update(
			'UPDATE	authors
			SET	first_name = ?,
				middle_name = ?,
				last_name = ?,
				suffix = ?,
				country = ?,
				email = ?,
				url = ?,
				user_group_id = ?,
				primary_contact = ?,
				seq = ?,
				include_in_browse = ?
			WHERE	author_id = ?',
			array(
				$author->getFirstName(),
				$author->getMiddleName() . '', // make non-null
				$author->getLastName(),
				$author->getSuffix() . '',
				$author->getCountry(),
				$author->getEmail(),
				$author->getUrl(),
				(int) $author->getUserGroupId(),
				(int) $author->getPrimaryContact(),
				(float) $author->getSequence(),
				(int) $author->getIncludeInBrowse() ? 1 : 0,
				(int) $author->getId()
			)
		);
		$this->updateLocaleFields($author);
		return $returner;
	}

	/**
	 * Delete an Author.
	 * @param $author Author Author object to delete.
	 */
	function deleteObject($author) {
		return $this->deleteById($author->getId());
	}

	/**
	 * Delete an author by ID.
	 * @param $authorId int Author ID
	 * @param $submissionId int Optional submission ID.
	 */
	function deleteById($authorId, $submissionId = null) {
		$params = array((int) $authorId);
		if ($submissionId) $params[] = (int) $submissionId;
		$this->update(
			'DELETE FROM authors WHERE author_id = ?' .
			($submissionId?' AND submission_id = ?':''),
			$params
		);
		$this->update('DELETE FROM author_settings WHERE author_id = ?', array((int) $authorId));
	}

	/**
	 * Sequentially renumber a submission's authors in their sequence order.
	 * @param $submissionId int Submission ID.
	 */
	function resequenceAuthors($submissionId) {
		$result = $this->retrieve(
			'SELECT author_id FROM authors WHERE submission_id = ? ORDER BY seq',
			(int) $submissionId
		);

		for ($i=1; !$result->EOF; $i++) {
			list($authorId) = $result->fields;
			$this->update(
				'UPDATE authors SET seq = ? WHERE author_id = ?',
				array(
					$i,
					$authorId
				)
			);

			$result->MoveNext();
		}
		$result->Close();
	}

	/**
	 * Retrieve the primary author for a submission.
	 * @param $submissionId int Submission ID.
	 * @return Author
	 */
	function getPrimaryContact($submissionId) {
		$result = $this->retrieve(
			'SELECT a.*, ug.show_title
				FROM authors a
			JOIN user_groups ug ON (a.user_group_id=ug.user_group_id)
			WHERE submission_id = ? AND primary_contact = 1',
			(int) $submissionId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Remove other primary contacts from a submission and set to authorId
	 * @param $authorId int Author ID.
	 * @param $submissionId int Submission ID.
	 */
	function resetPrimaryContact($authorId, $submissionId) {
		$this->update(
			'UPDATE authors SET primary_contact = 0 WHERE primary_contact = 1 AND submission_id = ?',
			(int) $submissionId
		);
		$this->update(
			'UPDATE authors SET primary_contact = 1 WHERE author_id = ? AND submission_id = ?',
			array((int) $authorId, (int) $submissionId)
		);
	}

	/**
	 * Get the ID of the last inserted author.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('authors', 'author_id');
	}

	/**
	 * Delete authors by submission.
	 * @param $submissionId int
	 */
	function deleteBySubmissionId($submissionId) {
		$authors = $this->getBySubmissionId($submissionId);
		foreach ($authors as $author) {
			$this->deleteObject($author);
		}
	}
}

?>
