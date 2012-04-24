<?php

/**
 * @file plugins/reports/articles/ArticleReportDAO.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleReportDAO
 * @ingroup plugins_reports_article
 *
 * @brief Article report DAO
 */

// $Id$


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
			WHERE	a.journal_id = ?
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
			'SELECT	MAX(ed.date_decided) AS date,
				ed.article_id AS article_id
			FROM	edit_decisions ed,
				articles a
			WHERE	a.journal_id = ? AND
				a.article_id = ed.article_id
			GROUP BY ed.article_id',
			array($journalId)
		);
		$decisionDatesIterator = new DBRowIterator($result);
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
			$decisionsReturner[] = new DBRowIterator($result);
			unset($result);
		}

		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$articles =& $articleDao->getArticlesByJournalId($journalId);
		$authorsReturner = array();
		$index = 1;
		while ($article =& $articles->next()) {
			$result =& $this->retrieve(
				'SELECT	aa.email AS email,
					aa.country AS country,
					aa.url AS url,
					COALESCE(aasl.setting_value, aas.setting_value) AS biography,
					COALESCE(aaasl.setting_value, aaas.setting_value) AS affiliation,
					COALESCE(aaasfnl.setting_value, aaasfn.setting_value) AS fname,
					COALESCE(aaasmnl.setting_value, aaasmn.setting_value) AS mname,
					COALESCE(aaaslnl.setting_value, aaasln.setting_value) AS lname
				FROM	authors aa
					LEFT JOIN articles a ON (aa.submission_id = a.article_id)
					LEFT JOIN author_settings aas ON (aa.author_id = aas.author_id AND aas.setting_name = ? AND aas.locale = ?)
					LEFT JOIN author_settings aasl ON (aa.author_id = aasl.author_id AND aasl.setting_name = ? AND aasl.locale = ?)
					LEFT JOIN author_settings aaas ON (aa.author_id = aaas.author_id AND aaas.setting_name = ? AND aaas.locale = ?)
					LEFT JOIN author_settings aaasl ON (aa.author_id = aaasl.author_id AND aaasl.setting_name = ? AND aaasl.locale = ?)
					LEFT JOIN author_settings aaasfn ON (aa.author_id = aaasfn.author_id AND aaasfn.setting_name = ? AND aaasfn.locale = ?)
					LEFT JOIN author_settings aaasfnl ON (aa.author_id = aaasfnl.author_id AND aaasfnl.setting_name = ? AND aaasfnl.locale = ?)
					LEFT JOIN author_settings aaasmn ON (aa.author_id = aaasmn.author_id AND aaasmn.setting_name = ? AND aaasmn.locale = ?)
					LEFT JOIN author_settings aaasmnl ON (aa.author_id = aaasmnl.author_id AND aaasmnl.setting_name = ? AND aaasmnl.locale = ?)
					LEFT JOIN author_settings aaasln ON (aa.author_id = aaasln.author_id AND aaasln.setting_name = ? AND aaasln.locale = ?)
					LEFT JOIN author_settings aaaslnl ON (aa.author_id = aaaslnl.author_id AND aaaslnl.setting_name = ? AND aaaslnl.locale = ?)
					WHERE
					a.journal_id = ? AND
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
					'firstName',
					$primaryLocale,
					'firstName',
					$locale,
					'middleName',
					$primaryLocale,
					'middleName',
					$locale,
					'lastName',
					$primaryLocale,
					'lastName',
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
