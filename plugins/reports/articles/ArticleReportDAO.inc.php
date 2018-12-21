<?php

/**
 * @file plugins/reports/articles/ArticleReportDAO.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
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
		$authorDao = DAORegistry::getDAO('AuthorDAO');
		$articles = $articleDao->getByContextId($journalId);
		$params = array_merge(
			$authorDao->getFetchParameters(),
			array(
				'biography',
				'biography',
				$locale,
				'affiliation',
				'affiliation',
				$locale,
				(int) $journalId,
			)
		);
		$authorsReturner = array();
		$index = 1;
		while ($article = $articles->next()) {
			$result = $this->retrieve(
				'SELECT	' . $authorDao->getFetchColumns() .',
					a.email AS email,
					a.country AS country,
					a.url AS url,
					COALESCE(aasl.setting_value, aas.setting_value) AS biography,
					COALESCE(aaasl.setting_value, aaas.setting_value) AS affiliation
				FROM	authors a
					JOIN submissions s ON (a.submission_id = s.submission_id)
					' . $authorDao->getFetchJoins() .'
					LEFT JOIN author_settings aas ON (a.author_id = aas.author_id AND aas.setting_name = ? AND aas.locale = s.locale)
					LEFT JOIN author_settings aasl ON (a.author_id = aasl.author_id AND aasl.setting_name = ? AND aasl.locale = ?)
					LEFT JOIN author_settings aaas ON (a.author_id = aaas.author_id AND aaas.setting_name = ? AND aaas.locale = s.locale)
					LEFT JOIN author_settings aaasl ON (a.author_id = aaasl.author_id AND aaasl.setting_name = ? AND aaasl.locale = ?)
				WHERE
					s.context_id = ? AND
					s.submission_progress = 0 AND
					a.submission_id = ?',
				array_merge($params, array((int) $article->getId()))
			);
			$authorIterator = new DBRowIterator($result);
			$authorsReturner[$article->getId()] = $authorIterator;
			unset($result);
			$index++;
		}

		return array($articlesReturner, $authorsReturner, $decisionsReturner);
	}
}

