<?php

/**
 * @file ArticleReportDAO.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @class ArticleReportDAO
 * @ingroup plugins_reports_article
 *
 * @brief Article report DAO
 */

// $Id$


import('submission.common.Action');

class ArticleReportDAO extends DAO {
	/**
	 * Get the article report data.
	 * @param $journalId int
	 * @return array
	 */
	function getArticleReport($journalId) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();

		$result =& $this->retrieve(
			'SELECT
				a.article_id AS article_id,
				COALESCE(asl1.setting_value, aspl1.setting_value) AS title,
				COALESCE(asl2.setting_value, aspl2.setting_value) AS abstract,
				u.first_name AS fname,
				u.middle_name AS mname,
				u.last_name AS lname,
				u.email AS email,
				u.affiliation AS affiliation,
				u.country AS country,
				u.phone AS phone,
				u.fax AS fax,
				u.url AS url,
				u.mailing_address AS address,
				COALESCE(usl.setting_value, uspl.setting_value) AS biography,
				COALESCE(sl.setting_value, spl.setting_value) AS section_title,
				a.language AS language
			FROM
				articles a
					LEFT JOIN users u ON a.user_id=u.user_id
					LEFT JOIN user_settings uspl ON (u.user_id=uspl.user_id AND uspl.setting_name = ? AND uspl.locale = ?)
					LEFT JOIN user_settings usl ON (u.user_id=usl.user_id AND usl.setting_name = ? AND usl.locale = ?)
					LEFT JOIN article_settings aspl1 ON (aspl1.article_id=a.article_id AND aspl1.setting_name = ? AND aspl1.locale = ?)
					LEFT JOIN article_settings asl1 ON (asl1.article_id=a.article_id AND asl1.setting_name = ? AND asl1.locale = ?)
					LEFT JOIN article_settings aspl2 ON (aspl2.article_id=a.article_id AND aspl2.setting_name = ? AND aspl2.locale = ?)
					LEFT JOIN article_settings asl2 ON (asl2.article_id=a.article_id AND asl2.setting_name = ? AND asl2.locale = ?)
					LEFT JOIN section_settings spl ON (spl.section_id=a.section_id AND spl.setting_name = ? AND spl.locale = ?)
					LEFT JOIN section_settings sl ON (sl.section_id=a.section_id AND sl.setting_name = ? AND sl.locale = ?)
			WHERE
				a.journal_id = ?
			ORDER BY
				title',
			array(
				'biography',
				$primaryLocale,
				'biography',
				$locale,
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abstract',
				$primaryLocale,
				'abstract',
				$locale,
				'title',
				$primaryLocale,
				'title',
				$locale,
				$journalId
			)
		);
		$articlesReturner =& new DBRowIterator($result);

		$result =& $this->retrieve(
			'SELECT	MAX(ed.date_decided) AS date,
				ed.article_id AS article_id
			FROM	edit_decisions ed,
				articles a
			WHERE	a.journal_id = ? AND
				a.article_id = ed.article_id
			GROUP BY ed.article_id',
			array($journalId)
		);
		$decisionDatesIterator =& new DBRowIterator($result);
		$decisions = array();
		$decisionsReturner = array();
		while ($row =& $decisionDatesIterator->next()) {
			$result =& $this->retrieve(
				'SELECT	decision AS decision,
					article_id AS article_id
				FROM	edit_decisions
				WHERE	date_decided = ? AND
					article_id = ?',
				array(
					$row['date'],
					$row['article_id']
				)
			);
			$decisionsReturner[] =& new DBRowIterator($result);
			unset($result);
		}

		return array($articlesReturner, $decisionsReturner);
	}
}

?>
