<?php

/**
 * @file classes/article/AuthorDAO.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorDAO
 * @ingroup article
 * @see Author
 *
 * @brief Operations for retrieving and modifying Author objects.
 */

// $Id$


import('classes.article.Author');
import('classes.article.Article');
import('lib.pkp.classes.submission.PKPAuthorDAO');

class AuthorDAO extends PKPAuthorDAO {
	/**
	 * Constructor
	 */
	function AuthorDAO() {
		parent::PKPAuthorDAO();
	}

	/**
	 * Retrieve all authors for a submission.
	 * @param $submissionId int
	 * @return array Authors ordered by sequence
	 */
	function &getAuthorsByArticle($submissionId) {
		$authors = array();

		$result =& $this->retrieve(
			'SELECT * FROM authors WHERE submission_id = ? ORDER BY seq',
			(int) $submissionId
		);

		while (!$result->EOF) {
			$authors[] =& $this->_returnAuthorFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $authors;
	}

	/**
	 * Retrieve all published submissions associated with authors with
	 * the given first name, middle name, last name, affiliation, and country.
	 * @param $journalId int (null if no restriction desired)
	 * @param $firstName string
	 * @param $middleName string
	 * @param $lastName string
	 * @param $affiliation string
	 * @param $country string
	 */
	function &getPublishedArticlesForAuthor($journalId, $firstName, $middleName, $lastName, $affiliation, $country) {
		$publishedArticles = array();
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$params = array($firstName, $middleName, $lastName, $affiliation, $country);
		if ($journalId !== null) $params[] = $journalId;

		$result =& $this->retrieve(
			'SELECT DISTINCT
				aa.submission_id
			FROM	authors aa
				LEFT JOIN articles a ON (aa.submission_id = a.article_id)
			WHERE	aa.first_name = ?
				AND a.status = ' . STATUS_PUBLISHED . '
				AND (aa.middle_name = ?' . (empty($middleName)?' OR aa.middle_name IS NULL':'') . ')
				AND aa.last_name = ?
				AND (aa.affiliation = ?' . (empty($affiliation)?' OR aa.affiliation IS NULL':'') . ')
				AND (aa.country = ?' . (empty($country)?' OR aa.country IS NULL':'') . ') ' .
				($journalId!==null?(' AND a.journal_id = ?'):''),
			$params
		);

		while (!$result->EOF) {
			$row =& $result->getRowAssoc(false);
			$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($row['submission_id']);
			if ($publishedArticle) {
				$publishedArticles[] =& $publishedArticle;
			}
			$result->moveNext();
			unset($publishedArticle);
		}

		$result->Close();
		unset($result);

		return $publishedArticles;
	}

	/**
	 * Retrieve all published authors for a journal in an associative array by
	 * the first letter of the last name, for example:
	 * $returnedArray['S'] gives array($misterSmithObject, $misterSmytheObject, ...)
	 * Keys will appear in sorted order. Note that if journalId is null,
	 * alphabetized authors for all journals are returned.
	 * @param $journalId int
	 * @param $initial An initial the last names must begin with
	 * @return array Authors ordered by sequence
	 */
	function &getAuthorsAlphabetizedByJournal($journalId = null, $initial = null, $rangeInfo = null) {
		$authors = array();
		$params = array();

		if (isset($journalId)) $params[] = $journalId;
		if (isset($initial)) {
			$params[] = String::strtolower($initial) . '%';
			$initialSql = ' AND LOWER(aa.last_name) LIKE LOWER(?)';
		} else {
			$initialSql = '';
		}

		$result =& $this->retrieveRange(
			'SELECT DISTINCT
				CAST(\'\' AS CHAR) AS url,
				0 AS author_id,
				0 AS submission_id,
				CAST(\'\' AS CHAR) AS email,
				0 AS primary_contact,
				0 AS seq,
				aa.first_name AS first_name,
				aa.middle_name AS middle_name,
				aa.last_name AS last_name,
				aa.affiliation AS affiliation,
				aa.country
			FROM	authors aa,
				articles a,
				published_articles pa,
				issues i
			WHERE	i.issue_id = pa.issue_id
				AND i.published = 1
				AND aa.submission_id = a.article_id ' .
				(isset($journalId)?'AND a.journal_id = ? ':'') . '
				AND pa.article_id = a.article_id
				AND a.status = ' . STATUS_PUBLISHED . '
				AND (aa.last_name IS NOT NULL AND aa.last_name <> \'\')' .
				$initialSql . '
			ORDER BY aa.last_name, aa.first_name',
			empty($params)?false:$params,
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnAuthorFromRow');
		return $returner;
	}

	/**
	 * Retrieve the IDs of all authors for a submission.
	 * @param $submissionId int
	 * @return array int ordered by sequence
	 */
	function &getAuthorIdsByArticle($submissionId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		$returner =& $this->getAuthorIdsBySubmissionId($submissionId);
		return $returner;
	}

	/**
	 * Get a new data object
	 * @return DataObject
	 */
	function newDataObject() {
		return new Author();
	}

	/**
	 * Insert a new Author.
	 * @param $author Author
	 */
	function insertAuthor(&$author) {
		$this->update(
			'INSERT INTO authors
				(submission_id, first_name, middle_name, last_name, affiliation, country, email, url, primary_contact, seq)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$author->getSubmissionId(),
				$author->getFirstName(),
				$author->getMiddleName() . '', // make non-null
				$author->getLastName(),
				$author->getAffiliation() . '', // make non-null
				$author->getCountry(),
				$author->getEmail(),
				$author->getUrl(),
				(int) $author->getPrimaryContact(),
				(float) $author->getSequence()
			)
		);

		$author->setId($this->getInsertAuthorId());
		$this->updateLocaleFields($author);

		return $author->getId();
	}

	/**
	 * Update an existing Author.
	 * @param $author Author
	 */
	function updateAuthor(&$author) {
		$returner = $this->update(
			'UPDATE authors
			SET	first_name = ?,
				middle_name = ?,
				last_name = ?,
				affiliation = ?,
				country = ?,
				email = ?,
				url = ?,
				primary_contact = ?,
				seq = ?
			WHERE	author_id = ?',
			array(
				$author->getFirstName(),
				$author->getMiddleName() . '', // make non-null
				$author->getLastName(),
				$author->getAffiliation() . '', // make non-null
				$author->getCountry(),
				$author->getEmail(),
				$author->getUrl(),
				(int) $author->getPrimaryContact(),
				(float) $author->getSequence(),
				(int) $author->getId()
			)
		);
		$this->updateLocaleFields($author);
		return $returner;
	}

	/**
	 * Delete authors by submission.
	 * @param $submissionId int
	 */
	function deleteAuthorsByArticle($submissionId) {
		$authors =& $this->getAuthorsByArticle($submissionId);
		foreach ($authors as $author) {
			$this->deleteAuthor($author);
		}
	}
}

?>
