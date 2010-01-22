<?php

/**
 * @file ArticleReportDAO.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
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
			'SELECT	a.status AS status,
				a.article_id AS article_id,
				COALESCE(asl1.setting_value, aspl1.setting_value) AS title,
				COALESCE(asl2.setting_value, aspl2.setting_value) AS abstract,
				COALESCE(sl.setting_value, spl.setting_value) AS section_title,
				a.language AS language
			FROM
				articles a
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
					aa.affiliation AS affiliation,
					aa.country AS country,
					aa.url AS url,
					COALESCE(aasl.setting_value, aas.setting_value) AS biography
				FROM article_authors aa
					LEFT JOIN articles a ON aa.article_id=a.article_id
					LEFT JOIN article_author_settings aas ON (aa.author_id=aas.author_id AND aas.setting_name = ? AND aas.locale = ?)
					LEFT JOIN article_author_settings aasl ON (aa.author_id=aasl.author_id AND aasl.setting_name = ? AND aasl.locale = ?)
				WHERE
					a.journal_id = ? AND
					aa.article_id = ?',
				array(
					'biography',
					$primaryLocale,
					'biography',
					$locale,
					$journalId,
					$article->getArticleId()
				)
			);
			$authorIterator =& new DBRowIterator($result);
			$authorsReturner[$article->getArticleId()] = $authorIterator;
			unset($authorIterator);
			$index++;
			unset($article);
		}

		return array($articlesReturner, $authorsReturner, $decisionsReturner);
	}
}

?>
