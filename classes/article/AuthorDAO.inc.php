<?php

/**
 * @file classes/article/AuthorDAO.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
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
				LEFT JOIN articles a
					ON (aa.submission_id = a.article_id)
				LEFT JOIN author_settings asl
					ON (asl.author_id = aa.author_id AND asl.setting_name = ?)
			WHERE	aa.first_name = ?
				AND a.status = ' . STATUS_PUBLISHED . '
				AND (aa.middle_name = ?'
					. (empty($middleName) ? ' OR aa.middle_name IS NULL' : '')
				. ')
				AND aa.last_name = ?
				AND (asl.setting_value = ?'
					. (empty($affiliation) ? ' OR asl.setting_value IS NULL' : '')
				. ')
				AND (aa.country = ?'
					. (empty($country) ? ' OR aa.country IS NULL' : '')
				. ') '
				. ( $journalId !== null ? (' AND a.journal_id = ?') : ''),
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
	 * @param $rangeInfo Range information
	 * @param $includeEmail Whether or not to include the email in the select distinct
	 * @return array Authors ordered by sequence
	 */
	function &getAuthorsAlphabetizedByJournal($journalId = null, $initial = null, $rangeInfo = null, $includeEmail = false) {
		$authors = array();
		$params = array(
			'affiliation', AppLocale::getPrimaryLocale(),
			'affiliation', AppLocale::getLocale(),
			'firstName',   AppLocale::getPrimaryLocale(),
			'firstName',   AppLocale::getLocale(),
			'middleName',  AppLocale::getPrimaryLocale(),
			'middleName',  AppLocale::getLocale(),
			'lastName',    AppLocale::getPrimaryLocale(),
			'lastName',    AppLocale::getLocale()
		);

		if (isset($journalId)) $params[] = $journalId;
		if (isset($initial)) {
			$params[] = String::strtolower($initial) . '%';
			$initialSql = 'HAVING LOWER(last_name_l) LIKE LOWER(?)';
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
				asl.locale,
				aspl.locale AS primary_locale,
				asfnl.locale fn_locale,
				asfnpl.locale AS fn_primary_locale,
				asmnl.locale mn_locale,
				asmnpl.locale AS mn_primary_locale,
				aslnl.locale ln_locale,
				aslnpl.locale AS ln_primary_locale,
				CASE WHEN asl.setting_value = \'\'
					THEN NULL
					ELSE SUBSTRING(asl.setting_value FROM 1 FOR 255)
					END AS affiliation_l,
				CASE WHEN aspl.setting_value = \'\'
					THEN NULL
					ELSE SUBSTRING(aspl.setting_value FROM 1 FOR 255)
					END AS affiliation_pl,
				CASE WHEN asfnl.setting_value = \'\'
					THEN NULL
					ELSE SUBSTRING(asfnl.setting_value FROM 1 FOR 255)
					END AS first_name_l,
				CASE WHEN asfnpl.setting_value = \'\'
					THEN NULL
					ELSE SUBSTRING(asfnpl.setting_value FROM 1 FOR 255)
					END AS first_name_pl,
				CASE WHEN asmnl.setting_value = \'\'
					THEN NULL
					ELSE SUBSTRING(asmnl.setting_value FROM 1 FOR 255)
					END AS middle_name_l,
				CASE WHEN asmnpl.setting_value = \'\'
					THEN NULL
					ELSE SUBSTRING(asmnpl.setting_value FROM 1 FOR 255)
					END AS middle_name_pl,
				CASE WHEN aslnl.setting_value = \'\'
					THEN NULL
					ELSE SUBSTRING(aslnl.setting_value FROM 1 FOR 255)
					END AS last_name_l,
				CASE WHEN aslnpl.setting_value = \'\'
					THEN NULL
					ELSE SUBSTRING(aslnpl.setting_value FROM 1 FOR 255)
					END AS last_name_pl,
				CASE WHEN aa.country = \'\'
					THEN NULL
					ELSE aa.country
					END AS country
			FROM	authors aa
				LEFT JOIN author_settings aspl
					ON (aa.author_id = aspl.author_id
						AND aspl.setting_name = ?
						AND aspl.locale = ?)
				LEFT JOIN author_settings asl
					ON (aa.author_id = asl.author_id
						AND asl.setting_name = ?
						AND asl.locale = ?)
				LEFT JOIN author_settings asfnpl
					ON (aa.author_id = asfnpl.author_id
						AND asfnpl.setting_name = ?
						AND asfnpl.locale = ?)
				LEFT JOIN author_settings asfnl
					ON (aa.author_id = asfnl.author_id
						 AND asfnl.setting_name = ?
						 AND asfnl.locale = ?)
				LEFT JOIN author_settings asmnpl
					ON (aa.author_id = asmnpl.author_id
						AND asmnpl.setting_name = ?
						AND asmnpl.locale = ?)
				LEFT JOIN author_settings asmnl
					ON (aa.author_id = asmnl.author_id
						 AND asmnl.setting_name = ?
						 AND asmnl.locale = ?)
				LEFT JOIN author_settings aslnpl
					ON (aa.author_id = aslnpl.author_id
						AND aslnpl.setting_name = ?
						AND aslnpl.locale = ?)
				LEFT JOIN author_settings aslnl
					ON (aa.author_id = aslnl.author_id
						 AND aslnl.setting_name = ?
						 AND aslnl.locale = ?)
				JOIN articles a
					ON (a.article_id = aa.submission_id
						AND a.status = ' . STATUS_PUBLISHED . ')
				JOIN published_articles pa
					ON (pa.article_id = a.article_id)
				JOIN issues i
					ON (pa.issue_id = i.issue_id AND i.published = 1)
			WHERE ' . (isset($journalId)?'a.journal_id = ? AND ':'') . '
				(aa.last_name IS NOT NULL AND aa.last_name <> \'\')' .
			$initialSql . '
			ORDER BY last_name_l, last_name_pl, first_name_l, first_name_pl',
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
				(submission_id,
					country, email, url, primary_contact, seq)
				VALUES
				(?, ?, ?, ?, ?, ?)',
			array(
				$author->getSubmissionId(),
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
			SET
				country = ?,
				email = ?,
				url = ?,
				primary_contact = ?,
				seq = ?
			WHERE	author_id = ?',
			array(
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
}

?>
