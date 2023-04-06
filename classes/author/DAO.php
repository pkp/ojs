<?php

/**
 * @file classes/author/DAO.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DAO
 *
 * @ingroup author
 *
 * @see Author
 *
 * @brief Operations for retrieving and modifying Author objects.
 */

namespace APP\author;

use APP\core\Application;
use APP\journal\Journal;
use APP\journal\JournalDAO;
use PKP\core\PKPString;
use PKP\db\DAORegistry;
use PKP\db\DAOResultFactory;
use PKP\db\DBResultRange;
use PKP\facades\Locale;
use PKP\identity\Identity;
use PKP\submission\PKPSubmission;

class DAO extends \PKP\author\DAO
{
    /**
     * Retrieve all published authors for a journal by the first letter of the family name.
     * Authors will be sorted by (family, given). Note that if journalId is null,
     * alphabetized authors for all enabled journals are returned.
     * If authors have the same given names, first names and affiliations in all journal locales,
     * as well as country and email (optional), they are considered to be the same.
     *
     * @param int $journalId Optional journal ID to restrict results to
     * @param string $initial An initial a family name must begin with, "-" for authors with no family names
     * @param ?DBResultRange $rangeInfo Range information
     * @param bool $includeEmail Whether or not to include the email in the select distinct
     *
     * @return DAOResultFactory<Author> Authors ordered by last name, given name
     *
     * @deprecated 3.4.0.0
     *
     */
    public function getAuthorsAlphabetizedByJournal($journalId = null, $initial = null, $rangeInfo = null, $includeEmail = false)
    {
        $locale = Locale::getLocale();
        $params = [
            Identity::IDENTITY_SETTING_GIVENNAME, $locale,
            Identity::IDENTITY_SETTING_GIVENNAME,
            Identity::IDENTITY_SETTING_FAMILYNAME, $locale,
            Identity::IDENTITY_SETTING_FAMILYNAME,
            'issueId',
        ];
        if (isset($journalId)) {
            $params[] = $journalId;
        }

        $supportedLocales = [];
        if ($journalId !== null) {
            /** @var JournalDAO */
            $journalDao = DAORegistry::getDAO('JournalDAO');
            /** @var Journal */
            $journal = $journalDao->getById($journalId);
            $supportedLocales = $journal->getSupportedLocales();
        } else {
            $site = Application::get()->getRequest()->getSite();
            $supportedLocales = $site->getSupportedLocales();
        }
        $supportedLocalesCount = count($supportedLocales);
        $sqlJoinAuthorSettings = $sqlColumnsAuthorSettings = $initialSql = '';
        if (isset($initial)) {
            $initialSql = ' AND (';
        }
        $index = 0;
        foreach ($supportedLocales as $locale) {
            $localeStr = str_replace('@', '_', $locale);
            $sqlColumnsAuthorSettings .= ",
                COALESCE(asg{$index}.setting_value, ''), ' ',
                COALESCE(asf{$index}.setting_value, ''), ' ',
                COALESCE(SUBSTRING(asa{$index}.setting_value FROM 1 FOR 255), ''), ' '
            ";
            $sqlJoinAuthorSettings .= "
                LEFT JOIN author_settings asg{$index} ON (asg{$index}.author_id  = aa.author_id AND asg{$index}.setting_name = '" . Identity::IDENTITY_SETTING_GIVENNAME . "' AND asg{$index}.locale = '{$locale}')
                LEFT JOIN author_settings asf{$index} ON (asf{$index}.author_id  = aa.author_id AND asf{$index}.setting_name = '" . Identity::IDENTITY_SETTING_FAMILYNAME . "' AND asf{$index}.locale = '{$locale}')
                LEFT JOIN author_settings asa{$index} ON (asa{$index}.author_id  = aa.author_id AND asa{$index}.setting_name = 'affiliation' AND asa{$index}.locale = '{$locale}')
            ";
            if (isset($initial)) {
                if ($initial == '-') {
                    $initialSql .= "(asf{$index}.setting_value IS NULL OR asf{$index}.setting_value = '')";
                    if ($index < $supportedLocalesCount - 1) {
                        $initialSql .= ' AND ';
                    }
                } else {
                    $params[] = PKPString::strtolower($initial) . '%';
                    $initialSql .= "LOWER(asf{$index}.setting_value) LIKE LOWER(?)";
                    if ($index < $supportedLocalesCount - 1) {
                        $initialSql .= ' OR ';
                    }
                }
            }
            $index++;
        }
        if (isset($initial)) {
            $initialSql .= ')';
        }

        $baseSql = '
            FROM authors a
            JOIN user_groups ug ON (a.user_group_id = ug.user_group_id)
            JOIN publications p ON (p.publication_id = a.publication_id)
            JOIN submissions s ON (s.current_publication_id = p.publication_id)
            LEFT JOIN author_settings agl ON (a.author_id = agl.author_id AND agl.setting_name = ? AND agl.locale = ?)
            LEFT JOIN author_settings agpl ON (a.author_id = agpl.author_id AND agpl.setting_name = ? AND agpl.locale = s.locale)
            LEFT JOIN author_settings afl ON (a.author_id = afl.author_id AND afl.setting_name = ? AND afl.locale = ?)
            LEFT JOIN author_settings afpl ON (a.author_id = afpl.author_id AND afpl.setting_name = ? AND afpl.locale = s.locale)
            JOIN (
                SELECT
                    MIN(aa.author_id) as author_id,
                    CONCAT(
                        ' . ($includeEmail ? "aa.email, ' ', " : '') . "
                        ac.setting_value,
                        ' '
                        {$sqlColumnsAuthorSettings}
                    ) as names
                FROM authors aa
                JOIN publications pp ON (pp.publication_id = aa.publication_id)
                LEFT JOIN publication_settings ppss ON (ppss.publication_id = pp.publication_id)
                JOIN submissions ss ON (ss.submission_id = pp.submission_id AND ss.current_publication_id = pp.publication_id AND ss.status = " . PKPSubmission::STATUS_PUBLISHED . ")
                JOIN journals j ON (ss.context_id = j.journal_id)
                JOIN issues i ON (ppss.setting_name = ? AND ppss.setting_value = CAST(i.issue_id AS CHAR(20)) AND i.published = 1)
                LEFT JOIN author_settings ac ON (ac.author_id = aa.author_id AND ac.setting_name = 'country')
                {$sqlJoinAuthorSettings}
                WHERE j.enabled = 1
                " . (isset($journalId) ? ' AND j.journal_id = ?' : '') . "
                {$initialSql}
                GROUP BY names
            ) as t1 ON (t1.author_id = a.author_id)";

        $result = $this->deprecatedDao->retrieveRange(
            "SELECT a.*, ug.show_title, s.locale,
            COALESCE(agl.setting_value, agpl.setting_value) AS author_given,
            CASE WHEN agl.setting_value <> '' THEN afl.setting_value ELSE afpl.setting_value END AS author_family
            {$baseSql}
            ORDER BY author_family, author_given",
            $params,
            $rangeInfo
        );

        return new DAOResultFactory($result, $this, 'fromRow', [], "SELECT 0 {$baseSql}", $params, $rangeInfo);
    }
}
