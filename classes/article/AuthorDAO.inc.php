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
import('classes.submission.Submission');
import('lib.pkp.classes.submission.PKPAuthorDAO');

class AuthorDAO extends PKPAuthorDAO {

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
		$params[] = 'issueId';
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
				JOIN publications p ON (p.publication_id = a.publication_id)
				JOIN submissions s ON (s.submission_id = p.submission_id AND s.current_publication_id = p.publication_id)
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
					FROM authors aa
					JOIN publications pp ON (pp.publication_id = aa.publication_id)
					LEFT JOIN publication_settings ppss ON (ppss.publication_id = pp.publication_id)
					JOIN submissions ss ON (ss.submission_id = pp.submission_id AND ss.current_publication_id = pp.current_publication_id AND ss.status = ' . STATUS_PUBLISHED . ')
					JOIN journals j ON (ss.context_id = j.journal_id)
					JOIN issues i ON (ppss.setting_name = ? AND ppss.setting_value = i.issue_id AND i.published = 1)
					' . $sqlJoinAuthorSettings . '
					WHERE j.enabled = 1 AND
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
}


