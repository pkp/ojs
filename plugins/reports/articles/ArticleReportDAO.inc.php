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
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();

		$result = $this->retrieve(
			'SELECT	a.submission_id AS submission_id,
				COALESCE(asl1.setting_value, aspl1.setting_value) AS title,
				COALESCE(asl2.setting_value, aspl2.setting_value) AS abstract,
				COALESCE(sl.setting_value, spl.setting_value) AS section_title,
				a.status AS status,
				a.language AS language
			FROM	submissions a
				LEFT JOIN submission_settings aspl1 ON (aspl1.submission_id=a.submission_id AND aspl1.setting_name = ? AND aspl1.locale = a.locale)
				LEFT JOIN submission_settings asl1 ON (asl1.submission_id=a.submission_id AND asl1.setting_name = ? AND asl1.locale = ?)
				LEFT JOIN submission_settings aspl2 ON (aspl2.submission_id=a.submission_id AND aspl2.setting_name = ? AND aspl2.locale = a.locale)
				LEFT JOIN submission_settings asl2 ON (asl2.submission_id=a.submission_id AND asl2.setting_name = ? AND asl2.locale = ?)
				LEFT JOIN section_settings spl ON (spl.section_id=a.section_id AND spl.setting_name = ? AND spl.locale = ?)
				LEFT JOIN section_settings sl ON (sl.section_id=a.section_id AND sl.setting_name = ? AND sl.locale = ?)
			WHERE	a.context_id = ? AND
				a.submission_progress = 0
			ORDER BY a.submission_id',
			array(
				'title', // Article title
				'title',
				$locale,
				'abstract', // Article abstract
				'abstract',
				$locale,
				'title',
				$primaryLocale,
				'title',
				$locale,
				(int) $journalId
			)
		);
		$articlesReturner = new DBRowIterator($result);
		unset($result);

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


