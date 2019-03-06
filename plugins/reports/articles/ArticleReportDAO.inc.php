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

		$articleDao = DAORegistry::getDAO('ArticleDAO');
		$authorDao = DAORegistry::getDAO('AuthorDAO');
		$articles = $articleDao->getByContextId($journalId);
		$authorParams = array_merge(
			$authorDao->getFetchParameters(),
			array(
				'biography',
				'biography',
				$locale,
				'affiliation',
				'affiliation',
				$locale,
				'orcid',
				(int) $journalId,
			)
		);
		$userDao = DAORegistry::getDAO('UserDAO');
		$site = Application::get()->getRequest()->getSite();
		$sitePrimaryLocale = $site->getPrimaryLocale();
		$authorsReturner = $editorsReturner = $decisionsReturner = array();
		$index = 1;
		while ($article = $articles->next()) {
			$result = $this->retrieve(
				'SELECT	' . $authorDao->getFetchColumns() .',
					a.email AS email,
					a.country AS country,
					a.url AS url,
					ass.setting_value AS orcid,
					COALESCE(aasl.setting_value, aas.setting_value) AS biography,
					COALESCE(aaasl.setting_value, aaas.setting_value) AS affiliation
				FROM	authors a
					JOIN submissions s ON (a.submission_id = s.submission_id)
					' . $authorDao->getFetchJoins() .'
					LEFT JOIN author_settings aas ON (a.author_id = aas.author_id AND aas.setting_name = ? AND aas.locale = s.locale)
					LEFT JOIN author_settings aasl ON (a.author_id = aasl.author_id AND aasl.setting_name = ? AND aasl.locale = ?)
					LEFT JOIN author_settings aaas ON (a.author_id = aaas.author_id AND aaas.setting_name = ? AND aaas.locale = s.locale)
					LEFT JOIN author_settings aaasl ON (a.author_id = aaasl.author_id AND aaasl.setting_name = ? AND aaasl.locale = ?)
					LEFT JOIN author_settings ass ON (a.author_id = ass.author_id AND ass.setting_name = ?)
				WHERE
					s.context_id = ? AND
					s.submission_progress = 0 AND
					a.submission_id = ?',
				array_merge($authorParams, array((int) $article->getId()))
			);
			$authorIterator = new DBRowIterator($result);
			$authorsReturner[$article->getId()] = $authorIterator;
			unset($result);

			// Get all assigned editors and sub-editors
			$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
			$managerAssignmentFactory = $stageAssignmentDao->getBySubmissionAndRoleId($article->getId(), ROLE_ID_MANAGER);
			$subEditorAssignmentFactory = $stageAssignmentDao->getBySubmissionAndRoleId($article->getId(), ROLE_ID_SUB_EDITOR);
			$editorsAssignments = array_merge($managerAssignmentFactory->toArray(), $subEditorAssignmentFactory->toArray());
			if (!empty($editorsAssignments)) {
				$editorsUserIds = array_unique(array_map(create_function('$o', 'return (int) $o->getUserId();'), $editorsAssignments));
				$editorsUserIdsString = '(' . implode(', ', $editorsUserIds) .')';

				$editorParams = array_merge(
					$userDao->getFetchParameters(),
					array(
						'biography',
						$sitePrimaryLocale,
						'biography',
						$locale,
						'affiliation',
						$sitePrimaryLocale,
						'affiliation',
						$locale,
					)
				);

				$result = $this->retrieve(
					'SELECT	' . $userDao->getFetchColumns() .',
						u.user_id AS editor_id,
						u.email AS email,
						u.country AS country,
						u.url AS url,
						us.setting_value AS orcid,
						COALESCE(usbl.setting_value, usbsl.setting_value) AS biography,
						COALESCE(usal.setting_value, usasl.setting_value) AS affiliation
					FROM	users u
						' . $userDao->getFetchJoins() .'
						LEFT JOIN user_settings usbsl ON (usbsl.user_id = u.user_id AND usbsl.setting_name = ? AND usbsl.locale = ?)
						LEFT JOIN user_settings usbl ON (usbl.user_id = u.user_id AND usbl.setting_name = ? AND usbl.locale = ?)
						LEFT JOIN user_settings usasl ON (usasl.user_id = u.user_id AND usasl.setting_name = ? AND usasl.locale = ?)
						LEFT JOIN user_settings usal ON (usal.user_id = u.user_id AND usal.setting_name = ? AND usal.locale = ?)
						LEFT JOIN user_settings us ON (us.user_id = u.user_id AND us.setting_name = \'orcid\')
					WHERE
						u.user_id IN ' . $editorsUserIdsString,
						$editorParams
						);
				$editorIterator = new DBRowIterator($result);
				$editorsReturner[$article->getId()] = $editorIterator;
				unset($result);
			}

			// get all decisions and recommendations for each editor assignment for this submisison
			foreach ($editorsAssignments as $editorsAssignment) {
				$result = $this->retrieve(
						'SELECT	d.decision AS decision,
						d.date_decided,
						d.submission_id AS submission_id
					FROM	edit_decisions d,
						submissions a
					WHERE	d.submission_id = a.submission_id AND
						a.submission_progress = 0 AND
						d.submission_id = ? AND d.editor_id = ?',
						array((int) $article->getId(), (int) $editorsAssignment->getUserId())
						);
				$decisionsReturner[$article->getId()][$editorsAssignment->getUserId()] = new DBRowIterator($result);
				unset($result);
			}

			$index++;
		}

		return array($articlesReturner, $authorsReturner, $editorsReturner, $decisionsReturner);
	}
}

