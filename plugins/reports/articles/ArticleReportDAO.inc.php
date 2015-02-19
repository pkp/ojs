<?php

/**
 * @file plugins/reports/articles/ArticleReportDAO.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleReportDAO
 * @ingroup plugins_reports_article
 *
 * @brief Article report DAO
 */

import('classes.submission.common.Action');
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

		$result =& $this->retrieve(
			'SELECT	a.article_id AS article_id,
				COALESCE(asl1.setting_value, aspl1.setting_value) AS title,
				COALESCE(asl2.setting_value, aspl2.setting_value) AS abstract,
				COALESCE(sl.setting_value, spl.setting_value) AS section_title,
				a.status AS status,
				a.language AS language
			FROM	articles a
				LEFT JOIN article_settings aspl1 ON (aspl1.article_id=a.article_id AND aspl1.setting_name = ? AND aspl1.locale = a.locale)
				LEFT JOIN article_settings asl1 ON (asl1.article_id=a.article_id AND asl1.setting_name = ? AND asl1.locale = ?)
				LEFT JOIN article_settings aspl2 ON (aspl2.article_id=a.article_id AND aspl2.setting_name = ? AND aspl2.locale = a.locale)
				LEFT JOIN article_settings asl2 ON (asl2.article_id=a.article_id AND asl2.setting_name = ? AND asl2.locale = ?)
				LEFT JOIN section_settings spl ON (spl.section_id=a.section_id AND spl.setting_name = ? AND spl.locale = ?)
				LEFT JOIN section_settings sl ON (sl.section_id=a.section_id AND sl.setting_name = ? AND sl.locale = ?)
			WHERE	a.journal_id = ? AND
				a.submission_progress = 0
			ORDER BY a.article_id',
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
				$journalId
			)
		);
		$articlesReturner = new DBRowIterator($result);

		$result =& $this->retrieve(
			'SELECT	MAX(d.date_decided) AS date_decided,
				d.article_id AS article_id
			FROM	edit_decisions d,
				articles a
			WHERE	a.journal_id = ? AND
				a.submission_progress = 0 AND
				a.article_id = d.article_id
			GROUP BY d.article_id',
			array((int) $journalId)
		);
		$decisionDatesIterator = new DBRowIterator($result);
		$decisionsReturner = array();
		while ($row =& $decisionDatesIterator->next()) {
			$result =& $this->retrieve(
				'SELECT	d.decision AS decision,
					d.article_id AS article_id
				FROM	edit_decisions d,
					articles a
				WHERE	d.date_decided = ? AND
					d.article_id = a.article_id AND
					a.submission_progress = 0 AND
					d.article_id = ?',
				array(
					$row['date_decided'],
					$row['article_id']
				)
			);
			$decisionsReturner[] = new DBRowIterator($result);
			unset($result);
		}

		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$articles =& $articleDao->getArticlesByJournalId($journalId);
		$authorsReturner = array();
		$index = 1;
		while ($article =& $articles->next()) {
			$result =& $this->retrieve(
				'SELECT	aa.first_name AS fname,
					aa.middle_name AS mname,
					aa.last_name AS lname,
					aa.email AS email,
					aa.country AS country,
					aa.url AS url,
					COALESCE(aasl.setting_value, aas.setting_value) AS biography,
					COALESCE(aaasl.setting_value, aaas.setting_value) AS affiliation
				FROM	authors aa
					JOIN articles a ON (aa.submission_id = a.article_id)
					LEFT JOIN author_settings aas ON (aa.author_id = aas.author_id AND aas.setting_name = ? AND aas.locale = ?)
					LEFT JOIN author_settings aasl ON (aa.author_id = aasl.author_id AND aasl.setting_name = ? AND aasl.locale = ?)
					LEFT JOIN author_settings aaas ON (aa.author_id = aaas.author_id AND aaas.setting_name = ? AND aaas.locale = ?)
					LEFT JOIN author_settings aaasl ON (aa.author_id = aaasl.author_id AND aaasl.setting_name = ? AND aaasl.locale = ?)
				WHERE
					a.journal_id = ? AND
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
					$journalId,
					$article->getId()
				)
			);
			$authorIterator = new DBRowIterator($result);
			$authorsReturner[$article->getId()] =& $authorIterator;
			unset($authorIterator);
			$index++;
			unset($article);
		}

		return array($articlesReturner, $authorsReturner, $decisionsReturner);
	}
}

?>
