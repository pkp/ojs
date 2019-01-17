<?php

/**
 * @file plugins/reports/articles/ArticleReportDAO.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleReportDAO
 * @ingroup plugins_reports_article
 *
 * @brief Article report DAO
 */

import('lib.pkp.classes.db.DBRowIterator');

class ArticleReportDAO extends DAO {
	/**
	 * Get the article report data.
	 * @param $journalId int
	 * @return array
	 */
	function getArticleReport($journalId) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();

		$articleDao = DAORegistry::getDAO('ArticleDAO');
		$articlesReturner = $articleDao->getByContextId($journalId);

		$result = $this->retrieve(
			'SELECT	MAX(d.date_decided) AS date_decided,
				d.submission_id AS submission_id
			FROM	edit_decisions d,
				submissions a
			WHERE	a.context_id = ? AND
				a.submission_progress = 0 AND
				a.submission_id = d.submission_id
			GROUP BY d.submission_id',
			array((int) $journalId)
		);
		$decisionDatesIterator = new DBRowIterator($result);
		$decisionsReturner = array();
		while ($row = $decisionDatesIterator->next()) {
			$result = $this->retrieve(
				'SELECT	d.decision AS decision,
					d.submission_id AS submission_id
				FROM	edit_decisions d,
					submissions a
				WHERE	d.date_decided = ? AND
					d.submission_id = a.submission_id AND
					a.submission_progress = 0 AND
					d.submission_id = ?',
				array(
					$row['date_decided'],
					$row['submission_id']
				)
			);
			$decisionsReturner[] = new DBRowIterator($result);
			unset($result);
		}

		$articleDao = DAORegistry::getDAO('ArticleDAO');
		$articles = $articleDao->getByContextId($journalId);
		$authorsReturner = array();
		$index = 1;
		while ($article = $articles->next()) {
			$result = $this->retrieve(
				'SELECT	aa.first_name AS fname,
					aa.middle_name AS mname,
					aa.last_name AS lname,
					aa.email AS email,
					aa.country AS country,
					aa.url AS url,
					COALESCE(aasl.setting_value, aas.setting_value) AS biography,
					COALESCE(aaasl.setting_value, aaas.setting_value) AS affiliation
				FROM	authors aa
					JOIN submissions a ON (aa.submission_id = a.submission_id)
					LEFT JOIN author_settings aas ON (aa.author_id = aas.author_id AND aas.setting_name = ? AND aas.locale = ?)
					LEFT JOIN author_settings aasl ON (aa.author_id = aasl.author_id AND aasl.setting_name = ? AND aasl.locale = ?)
					LEFT JOIN author_settings aaas ON (aa.author_id = aaas.author_id AND aaas.setting_name = ? AND aaas.locale = ?)
					LEFT JOIN author_settings aaasl ON (aa.author_id = aaasl.author_id AND aaasl.setting_name = ? AND aaasl.locale = ?)
				WHERE
					a.context_id = ? AND
					a.submission_progress = 0 AND
					aa.submission_id = ?',
				array(
					'biography',
					$primaryLocale,
					'biography',
					$locale,
					'affiliation',
					$primaryLocale,
					'affiliation',
					$locale,
					(int) $journalId,
					$article->getId()
				)
			);
			$authorIterator = new DBRowIterator($result);
			$authorsReturner[$article->getId()] = $authorIterator;
			unset($result);
			$index++;
		}

		return array($articlesReturner, $authorsReturner, $decisionsReturner);
	}
}
