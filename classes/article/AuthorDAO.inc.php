<?php

/**
 * @file classes/article/AuthorDAO.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
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
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$params = array(
			'affiliation', 
			IDENTITY_SETTING_FIRSTNAME, AppLocale::getLocale(),
			IDENTITY_SETTING_LASTNAME, AppLocale::getLocale(),
			IDENTITY_SETTING_MIDDLENAME,AppLocale::getLocale(),
			$firstName, $middleName, $lastName,
			$affiliation, $country
		);
		if ($journalId !== null) $params[] = (int) $journalId;

		$result = $this->retrieve(
			'SELECT DISTINCT
				aa.submission_id
			FROM	authors aa
				LEFT JOIN submissions a ON (aa.submission_id = a.submission_id)
				LEFT JOIN author_settings asa ON (asa.author_id = aa.author_id AND asa.setting_name = ?)
				LEFT JOIN author_settings asf ON (asf.author_id = aa.author_id AND asf.setting_name = ? AND asf.locale = ?)
				LEFT JOIN author_settings asl ON (asl.author_id = aa.author_id AND asl.setting_name = ? AND asl.locale = ?)
				LEFT JOIN author_settings asm ON (asm.author_id = aa.author_id AND asm.setting_name = ? AND asm.locale = ?)
			WHERE	asf.setting_value = ?
				AND a.status = ' . STATUS_PUBLISHED . '
				AND (asm.setting_value = ?' . (empty($middleName)?' OR asm.setting_value IS NULL':'') . ')
				AND asl.setting_value = ?
				AND (asa.setting_value = ?' . (empty($affiliation)?' OR asa.setting_value IS NULL':'') . ')
				AND (aa.country = ?' . (empty($country)?' OR aa.country IS NULL':'') . ') ' .
				($journalId!==null?(' AND a.context_id = ?'):''),
			$params
		);

		while (!$result->EOF) {
			$row = $result->getRowAssoc(false);
			$publishedArticle = $publishedArticleDao->getByArticleId($row['submission_id']);
			if ($publishedArticle) {
				$publishedArticles[] = $publishedArticle;
			}
			$result->MoveNext();
		}

		$result->Close();
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
	 * @return DAOResultFactory Authors ordered by sequence
	 */
	function getAuthorsAlphabetizedByJournal($journalId = null, $initial = null, $rangeInfo = null, $includeEmail = false) {
		$params = array(
			'affiliation', AppLocale::getPrimaryLocale(),
			'affiliation', AppLocale::getLocale(),
			IDENTITY_SETTING_FIRSTNAME, AppLocale::getLocale(),
			IDENTITY_SETTING_LASTNAME ,AppLocale::getLocale(),
			IDENTITY_SETTING_MIDDLENAME,AppLocale::getLocale()
		);

		if (isset($journalId)) $params[] = $journalId;
		if (isset($initial)) {
			$params[] = PKPString::strtolower($initial) . '%';
			$initialSql = ' AND LOWER(asl.setting_value) LIKE LOWER(?)';
		} else {
			$initialSql = '';
		}

		$result = $this->retrieveRange(
			'SELECT DISTINCT
				CAST(\'\' AS CHAR) AS url,
				0 AS author_id,
				0 AS submission_id,
				' . ($includeEmail?'aa.email AS email,':'CAST(\'\' AS CHAR) AS email,') . '
				0 AS primary_contact,
				0 AS seq,
				asf.setting_value,
				asm.setting_value,
				asl.setting_value,
				SUBSTRING(asa.setting_value FROM 1 FOR 255) AS affiliation_l,
				asa.locale,
				SUBSTRING(aspl.setting_value FROM 1 FOR 255) AS affiliation_pl,
				aspl.locale AS primary_locale,
				aa.country
			FROM	authors aa
				LEFT JOIN author_settings aspl ON (aa.author_id = aspl.author_id AND aspl.setting_name = ? AND aspl.locale = ?)
				LEFT JOIN author_settings asa ON (aa.author_id = asa.author_id AND asa.setting_name = ? AND asa.locale = ?)
				LEFT JOIN author_settings asf ON (asf.author_id = aa.author_id AND asf.setting_name = ? AND asf.locale = ?)
				LEFT JOIN author_settings asl ON (asl.author_id = aa.author_id AND asl.setting_name = ? AND asl.locale = ?)
				LEFT JOIN author_settings asm ON (asm.author_id = aa.author_id AND asm.setting_name = ? AND asm.locale = ?)
				JOIN submissions a ON (a.submission_id = aa.submission_id AND a.status = ' . STATUS_PUBLISHED . ')
				JOIN journals j ON (a.context_id = j.journal_id)
				JOIN published_submissions pa ON (pa.submission_id = a.submission_id)
				JOIN issues i ON (pa.issue_id = i.issue_id AND i.published = 1)
			WHERE ' . (isset($journalId)?'j.journal_id = ?':'j.enabled = 1') . '
				AND (asl.setting_value IS NOT NULL AND asl.setting_value <> \'\')' .
				$initialSql . '
			ORDER BY asl.setting_value, asf.setting_value',
			$params,
			$rangeInfo
		);

		return new DAOResultFactory($result, $this, '_returnSimpleAuthorFromRow');
	}

	/**
	 * Get a new data object
	 * @return DataObject
	 */
	function newDataObject() {
		return new Author();
	}
}

?>
