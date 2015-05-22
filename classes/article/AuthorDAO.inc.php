<?php

/**
 * @file classes/article/AuthorDAO.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorDAO
 * @ingroup article
 * @see Author
 *
 * @brief Operations for retrieving and modifying Author objects.
 */

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
		$params = array(
			'affiliation',
			$firstName, $middleName, $lastName,
			$affiliation, $country
		);
		if ($journalId !== null) $params[] = (int) $journalId;

		$result =& $this->retrieve(
			'SELECT DISTINCT
				aa.submission_id
			FROM	authors aa
				LEFT JOIN articles a ON (aa.submission_id = a.article_id)
				LEFT JOIN author_settings asl ON (asl.author_id = aa.author_id AND asl.setting_name = ?)
			WHERE	aa.first_name = ?
				AND a.status = ' . STATUS_PUBLISHED . '
				AND (aa.middle_name = ?' . (empty($middleName)?' OR aa.middle_name IS NULL':'') . ')
				AND aa.last_name = ?
				AND (asl.setting_value = ?' . (empty($affiliation)?' OR asl.setting_value IS NULL':'') . ')
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
	 * alphabetized authors for all enabled journals are returned.
	 * @param $journalId int Optional journal ID to restrict results to
	 * @param $initial An initial the last names must begin with
	 * @param $rangeInfo Range information
	 * @param $includeEmail Whether or not to include the email in the select distinct
	 * @param $disallowRepeatedEmail Whether or not to include duplicated emails in the array
	 * @return array Authors ordered by sequence
	 */
	function &getAuthorsAlphabetizedByJournal($journalId = null, $initial = null, $rangeInfo = null, $includeEmail = false, $disallowRepeatedEmail = false) {
		$authors = array();
		$params = array(
			'affiliation', AppLocale::getPrimaryLocale(),
			'affiliation', AppLocale::getLocale()
		);

		if (isset($journalId)) $params[] = $journalId;
		$params[] = AUTHOR_TOC_DEFAULT;
		$params[] = AUTHOR_TOC_SHOW;
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
				' . ($includeEmail?'aa.email AS email,':'CAST(\'\' AS CHAR) AS email,') . '
				0 AS primary_contact,
				0 AS seq,
				aa.first_name,
				aa.middle_name,
				aa.last_name,
				CASE WHEN asl.setting_value = \'\' THEN NULL ELSE SUBSTRING(asl.setting_value FROM 1 FOR 255) END AS affiliation_l,
				asl.locale,
				CASE WHEN aspl.setting_value = \'\' THEN NULL ELSE SUBSTRING(aspl.setting_value FROM 1 FOR 255) END AS affiliation_pl,
				aspl.locale AS primary_locale,
				CASE WHEN aa.country = \'\' THEN NULL ELSE aa.country END AS country
			FROM	authors aa
				LEFT JOIN author_settings aspl ON (aa.author_id = aspl.author_id AND aspl.setting_name = ? AND aspl.locale = ?)
				LEFT JOIN author_settings asl ON (aa.author_id = asl.author_id AND asl.setting_name = ? AND asl.locale = ?)
				'.($disallowRepeatedEmail?" LEFT JOIN authors aa2 ON (aa.email=aa2.email AND aa.author_id < aa2.author_id) ":"").'
				JOIN articles a ON (a.article_id = aa.submission_id AND a.status = ' . STATUS_PUBLISHED . ')
				JOIN published_articles pa ON (pa.article_id = a.article_id)
				JOIN issues i ON (pa.issue_id = i.issue_id AND i.published = 1)
				JOIN sections s ON (a.section_id = s.section_id)
				JOIN journals j ON (a.journal_id = j.journal_id)
			WHERE ' . (isset($journalId)?'a.journal_id = ?':'j.enabled = 1') . '
				AND (aa.last_name IS NOT NULL AND aa.last_name <> \'\')
				AND ((s.hide_author = 0 AND a.hide_author = ?) OR a.hide_author = ?)
				' .	($disallowRepeatedEmail?' AND aa2.email IS NULL ':'')
				. $initialSql . '
			ORDER BY aa.last_name, aa.first_name',
			$params,
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnSimpleAuthorFromRow');
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
				(submission_id, first_name, middle_name, last_name, country, email, url, primary_contact, seq)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$author->getSubmissionId(),
				$author->getFirstName(),
				$author->getMiddleName() . '', // make non-null
				$author->getLastName(),
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
		$authors =& $this->getAuthorsBySubmissionId($submissionId);
		foreach ($authors as $author) {
			$this->deleteAuthor($author);
		}
	}

	function getAdditionalFieldNames() {
		return array('orcid');
	}
}

?>
