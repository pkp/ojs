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
	 * @param $givenName string
	 * @param $familyName string
	 * @param $affiliation string
	 * @param $country string
	 */
	function &getPublishedArticlesForAuthor($journalId, $givenName, $familyName, $affiliation, $country) {
		$publishedArticles = array();
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$params = array(
			IDENTITY_SETTING_GIVENNAME, IDENTITY_SETTING_FAMILYNAME, 'affiliation',
			$givenName, $familyName, $affiliation, $country
		);
		if ($journalId !== null) $params[] = (int) $journalId;
		$result = $this->retrieve(
			'SELECT DISTINCT
				aa.submission_id
			FROM	authors aa
				LEFT JOIN submissions a ON (aa.submission_id = a.submission_id)
				LEFT JOIN author_settings asgs ON (asgs.author_id = aa.author_id AND asgs.setting_name = ?)
				LEFT JOIN author_settings asfs ON (asfs.author_id = aa.author_id AND asfs.setting_name = ?)
				LEFT JOIN author_settings asas ON (asas.author_id = aa.author_id AND asas.setting_name = ?)
				WHERE a.status = ' . STATUS_PUBLISHED . '
				AND (asgs.setting_value = ?)
				AND (asfs.setting_value = ?' . (empty($familyName)?' OR asfs.setting_value IS NULL':'') . ')
				AND (asas.setting_value = ?' . (empty($affiliation)?' OR asas.setting_value IS NULL':'') . ')
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
		$locale = AppLocale::getLocale();
		$params = array(
			IDENTITY_SETTING_GIVENNAME, $locale,
			IDENTITY_SETTING_GIVENNAME,
			IDENTITY_SETTING_FAMILYNAME, $locale,
			IDENTITY_SETTING_FAMILYNAME,
			'affiliation', $locale,
			'affiliation',
		);
		$params = array_merge($params, $this->getFetchParameters());
		if (isset($journalId)) $params[] = $journalId;
		if (isset($initial)) {
			$params[] = PKPString::strtolower($initial) . '%';
			$initialSql = ' AND LOWER(author_given) LIKE LOWER(?)';
		} else {
			$initialSql = '';
		}
		$result = $this->retrieveRange(
			'SELECT DISTINCT
				CAST(\'\' AS CHAR) AS url,
				0 AS author_id,
				0 AS submission_id,
				' . ($includeEmail?'a.email AS email,':'CAST(\'\' AS CHAR) AS email,') . '
				0 AS primary_contact,
				0 AS seq,
				asggl.setting_value AS author_given_l,
				asffl.setting_value AS author_family_l,
				SUBSTRING(asl.setting_value FROM 1 FOR 255) AS affiliation_l,
				asl.locale,
				asggpl.setting_value AS author_given_pl,
				asffpl.setting_value AS author_family_pl,
				SUBSTRING(aspl.setting_value FROM 1 FOR 255) AS affiliation_pl,
				aspl.locale AS primary_locale,
				a.country,
				0 AS user_group_id,
				0 AS include_in_browse,
				' . $this->getFetchColumns() . '
			FROM	authors a
				JOIN submissions s ON (s.submission_id = a.submission_id AND s.status = ' . STATUS_PUBLISHED . ')
				JOIN journals j ON (s.context_id = j.journal_id)
				JOIN published_submissions ps ON (ps.submission_id = s.submission_id)
				JOIN issues i ON (ps.issue_id = i.issue_id AND i.published = 1)
				LEFT JOIN author_settings asggl ON (a.author_id = asggl.author_id AND asggl.setting_name = ? AND asggl.locale = ?)
				LEFT JOIN author_settings asggpl ON (a.author_id = asggpl.author_id AND asggpl.setting_name = ? AND asggpl.locale = s.locale)
				LEFT JOIN author_settings asffl ON (a.author_id = asffl.author_id AND asffl.setting_name = ? AND asffl.locale = ?)
				LEFT JOIN author_settings asffpl ON (a.author_id = asffpl.author_id AND asffpl.setting_name = ? AND asffpl.locale = s.locale)
				LEFT JOIN author_settings asl ON (a.author_id = asl.author_id AND asl.setting_name = ? AND asl.locale = ?)
				LEFT JOIN author_settings aspl ON (a.author_id = aspl.author_id AND aspl.setting_name = ? AND aspl.locale = s.locale)
				' . $this->getFetchJoins() . '
			WHERE ' . (isset($journalId)?'j.journal_id = ?':'j.enabled = 1') .
				$initialSql . '
			' . $this->getOrderBy(),
			$params,
			$rangeInfo
		);
		return new DAOResultFactory($result, $this, '_fromRow');
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
