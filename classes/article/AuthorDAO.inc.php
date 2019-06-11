<?php

/**
 * @file classes/article/AuthorDAO.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
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
	 * the given name, family name, affiliation, and country.
	 * Authors are considered to be the same if they have the same given name and family name in one locale,
	 * as well as affiliation (optional) and country (optional)
	 * @param $journalId int (null if no restriction desired)
	 * @param $givenName string
	 * @param $familyName string
	 * @param $affiliation string (optional)
	 * @param $country string (optional)
	 */
	function &getPublishedSubmissionsForAuthor($journalId, $givenName, $familyName, $affiliation = null, $country = null) {
		$params = array();

		$supportedLocales = array();
		if ($journalId !== null) {
			$journalDao = DAORegistry::getDAO('JournalDAO');
			$journal = $journalDao->getById($journalId);
			$supportedLocales = $journal->getSupportedLocales();
		} else {
			$site = Application::get()->getRequest()->getSite();
			$supportedLocales = $site->getSupportedLocales();;
		}
		$supportedLocalesCount = count($supportedLocales);
		$sqlJoinAuthorSettings = $sqlWhereAffiliation = $sqlWhereCountry = '';
		$sqlWhereAuthorSettings = '(';
		foreach ($supportedLocales as $index => $locale) {
			$sqlJoinAuthorSettings .= "
				LEFT JOIN author_settings asg$index ON (asg$index.author_id  = a.author_id AND asg$index.setting_name = '" . IDENTITY_SETTING_GIVENNAME . "' AND asg$index.locale = '$locale')
				LEFT JOIN author_settings asf$index ON (asf$index.author_id  = a.author_id AND asf$index.setting_name = '" . IDENTITY_SETTING_FAMILYNAME . "' AND asf$index.locale = '$locale')
			";
			$params[] = $givenName;
			if (empty($familyName)) {
				$sqlWhereFamilyName = "(asf$index.setting_value is NULL OR asf$index.setting_value = '')";
			} else {
				$sqlWhereFamilyName = "asf$index.setting_value = ?";
				$params[] = $familyName;
			}
			if ($affiliation !== null) {
				$sqlJoinAuthorSettings .= "
					LEFT JOIN author_settings asa$index ON (asa$index.author_id  = a.author_id AND asa$index.setting_name = 'affiliation' AND asa$index.locale = '$locale')
				";
				if (empty($affiliation)) {
					$sqlWhereAffiliation = " AND (asa$index.setting_value is NULL OR asa$index.setting_value = '')";
				} else {
					$sqlWhereAffiliation = " AND asa$index.setting_value = ?";
					$params[] = $affiliation;
				}
			}
			$sqlWhereAuthorSettings .= "(asg$index.setting_value = ? AND " . $sqlWhereFamilyName . $sqlWhereAffiliation . ")";
			if ($index < $supportedLocalesCount - 1) {
				$sqlWhereAuthorSettings .= ' OR ';
			}

		}
		$sqlWhereAuthorSettings .= ')';

		if ($country !== null) {
			if (empty($country)) {
				$sqlWhereCountry = " AND (a.country IS NULL OR a.country = '')";
			} else {
				$sqlWhereCountry = " AND a.country = ?";
				$params[] = $country;
			}
		}
		if ($journalId !== null) $params[] = (int) $journalId;

		$result = $this->retrieve(
			'SELECT DISTINCT
				a.submission_id
			FROM	authors a
				LEFT JOIN submissions s ON (s.submission_id = a.submission_id)
				' .$sqlJoinAuthorSettings .'
				WHERE s.status = ' . STATUS_PUBLISHED . ' AND
				' .$sqlWhereAuthorSettings
				. $sqlWhereCountry
				. (($journalId !== null) ? ' AND s.context_id = ?' : ''),
			$params
		);

		$publishedSubmissions = array();
		$publishedSubmissionDao = DAORegistry::getDAO('PublishedSubmissionDAO');
		while (!$result->EOF) {
			$row = $result->getRowAssoc(false);
			$publishedSubmission = $publishedSubmissionDao->getBySubmissionId($row['submission_id']);
			if ($publishedSubmission) {
				$publishedSubmissions[] = $publishedSubmission;
			}
			$result->MoveNext();
		}
		$result->Close();
		return $publishedSubmissions;
	}

	/**
	 * Retrieve all published authors for a journal by the first letter of the family name.
	 * Authors will be sorted by (family, given). Note that if journalId is null,
	 * alphabetized authors for all enabled journals are returned.
	 * If authors have the same given names, first names and affiliations in all journal locales,
	 * as well as country and email (otional), they are considered to be the same.
	 * @param $journalId int Optional journal ID to restrict results to
	 * @param $initial An initial a family name must begin with, "-" for authors with no family names
	 * @param $rangeInfo Range information
	 * @param $includeEmail Whether or not to include the email in the select distinct
	 * @return DAOResultFactory Authors ordered by last name, given name
	 */
	function getAuthorsAlphabetizedByJournal($journalId = null, $initial = null, $rangeInfo = null, $includeEmail = false) {
		$params = $this->getFetchParameters();
		if (isset($journalId)) $params[] = $journalId;

		$supportedLocales = array();
		if ($journalId !== null) {
			$journalDao = DAORegistry::getDAO('JournalDAO');
			$journal = $journalDao->getById($journalId);
			$supportedLocales = $journal->getSupportedLocales();
		} else {
			$site = Application::get()->getRequest()->getSite();
			$supportedLocales = $site->getSupportedLocales();;
		}
		$supportedLocalesCount = count($supportedLocales);
		$sqlJoinAuthorSettings = $sqlColumnsAuthorSettings = $initialSql = '';
		if (isset($initial)) {
			$initialSql = ' AND (';
		}
		foreach ($supportedLocales as $index => $locale) {
			$localeStr = str_replace('@', '_', $locale);
			$sqlColumnsAuthorSettings .= ",
				COALESCE(asg$index.setting_value, ''), ' ',
				COALESCE(asf$index.setting_value, ''), ' ',
				COALESCE(SUBSTRING(asa$index.setting_value FROM 1 FOR 255), ''), ' '
			";
			$sqlJoinAuthorSettings .= "
				LEFT JOIN author_settings asg$index ON (asg$index.author_id  = aa.author_id AND asg$index.setting_name = '" . IDENTITY_SETTING_GIVENNAME . "' AND asg$index.locale = '$locale')
				LEFT JOIN author_settings asf$index ON (asf$index.author_id  = aa.author_id AND asf$index.setting_name = '" . IDENTITY_SETTING_FAMILYNAME . "' AND asf$index.locale = '$locale')
				LEFT JOIN author_settings asa$index ON (asa$index.author_id  = aa.author_id AND asa$index.setting_name = 'affiliation' AND asa$index.locale = '$locale')
			";
			if (isset($initial)) {
				if ($initial == '-') {
					$initialSql .= "(asf$index.setting_value IS NULL OR asf$index.setting_value = '')";
					if ($index < $supportedLocalesCount - 1) {
						$initialSql .= ' AND ';
					}
				} else {
					$params[] = PKPString::strtolower($initial) . '%';
					$initialSql .= "LOWER(asf$index.setting_value) LIKE LOWER(?)";
					if ($index < $supportedLocalesCount - 1) {
						$initialSql .= ' OR ';
					}
				}
			}
		}
		if (isset($initial)) {
			$initialSql .= ')';
		}

		$result = $this->retrieveRange(
			'SELECT a.*, ug.show_title, s.locale,
				' . $this->getFetchColumns() . '
			FROM	authors a
				JOIN user_groups ug ON (a.user_group_id = ug.user_group_id)
				JOIN submissions s ON (s.submission_id = a.submission_id)
				' . $this->getFetchJoins() . '
				JOIN (
					SELECT
					MIN(aa.author_id) as author_id,
					CONCAT(
					' . ($includeEmail ? 'aa.email,' : 'CAST(\'\' AS CHAR),') . '
					\' \',
					aa.country,
					\' \'
					' . $sqlColumnsAuthorSettings . '
					) as names
					FROM	authors aa
					JOIN submissions ss ON (ss.submission_id = aa.submission_id AND ss.status = ' . STATUS_PUBLISHED . ')
					JOIN journals j ON (ss.context_id = j.journal_id)
					JOIN published_submissions ps ON (ps.submission_id = ss.submission_id)
					JOIN issues i ON (ps.issue_id = i.issue_id AND i.published = 1)
					' . $sqlJoinAuthorSettings . '
					WHERE ps.is_current_submission_version = 1 AND aa.is_current_submission_version = 1 AND j.enabled = 1 AND
					' . (isset($journalId) ? 'j.journal_id = ?' : '')
					. $initialSql .'
					GROUP BY names
				) as t1 ON (t1.author_id = a.author_id)
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


