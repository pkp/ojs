<?php

/**
 * @file plugins/reports/articles/ArticleReportPlugin.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleReportPlugin
 * @ingroup plugins_reports_article
 *
 * @brief Article report plugin
 */

import('lib.pkp.classes.plugins.ReportPlugin');

class ArticleReportPlugin extends ReportPlugin {
	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path, $mainContextId);
		if ($success && Config::getVar('general', 'installed')) {
			$this->import('ArticleReportDAO');
			$articleReportDAO = new ArticleReportDAO();
			DAORegistry::registerDAO('ArticleReportDAO', $articleReportDAO);
		}
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'ArticleReportPlugin';
	}

	function getDisplayName() {
		return __('plugins.reports.articles.displayName');
	}

	function getDescription() {
		return __('plugins.reports.articles.description');
	}

	/**
	 * @copydoc ReportPlugin::display()
	 */
	function display($args, $request) {
		$journal = $request->getJournal();

		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=articles-' . date('Ymd') . '.csv');

		$articleReportDao = DAORegistry::getDAO('ArticleReportDAO');
		list($articlesIterator, $authorsIterator, $decisionsIteratorsArray) = $articleReportDao->getArticleReport($journal->getId());

		$maxAuthors = $this->getMaxAuthorCount($authorsIterator);

		$decisions = array();
		foreach ($decisionsIteratorsArray as $decisionsIterator) {
			while ($row = $decisionsIterator->next()) {
				$decisions[$row['submission_id']] = $row['decision'];
			}
		}

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR, LOCALE_COMPONENT_PKP_SUBMISSION);

		import('classes.article.Article');
		import('classes.workflow.EditorDecisionActionsManager');
		$decisionMessages = array(
			SUBMISSION_EDITOR_DECISION_ACCEPT => __('editor.submission.decision.accept'),
			SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS => __('editor.submission.decision.requestRevisions'),
			SUBMISSION_EDITOR_DECISION_RESUBMIT => __('editor.submission.decision.resubmit'),
			SUBMISSION_EDITOR_DECISION_DECLINE => __('editor.submission.decision.decline'),
			SUBMISSION_EDITOR_DECISION_SEND_TO_PRODUCTION => __('editor.submission.decision.sendToProduction'),
			SUBMISSION_EDITOR_DECISION_EXTERNAL_REVIEW => __('editor.submission.decision.sendExternalReview'),
			SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE => __('editor.submission.decision.decline'),
			SUBMISSION_EDITOR_RECOMMEND_ACCEPT => __('editor.submission.recommendation.display', array('recommendation' => __('editor.submission.decision.accept'))),
			SUBMISSION_EDITOR_RECOMMEND_DECLINE => __('editor.submission.recommendation.display', array('recommendation' => __('editor.submission.decision.decline'))),
			SUBMISSION_EDITOR_RECOMMEND_PENDING_REVISIONS => __('editor.submission.recommendation.display', array('recommendation' => __('editor.submission.decision.requestRevisions'))),
			SUBMISSION_EDITOR_RECOMMEND_RESUBMIT => __('editor.submission.recommendation.display', array('recommendation' => __('editor.submission.decision.resubmit'))),
			null => __('plugins.reports.articles.nodecision')
		);

		$columns = array(
			'submission_id' => __('article.submissionId'),
			'title' => __('article.title'),
			'abstract' => __('article.abstract')
		);

		for ($a = 1; $a <= $maxAuthors; $a++) {
			$columns = array_merge($columns, array(
				'author_given' . $a => __('user.givenName') . " (" . __('user.role.author') . " $a)",
				'author_family' . $a => __('user.familyName') . " (" . __('user.role.author') . " $a)",
				'country' . $a => __('common.country') . " (" . __('user.role.author') . " $a)",
				'affiliation' . $a => __('user.affiliation') . " (" . __('user.role.author') . " $a)",
				'email' . $a => __('user.email') . " (" . __('user.role.author') . " $a)",
				'url' . $a => __('user.url') . " (" . __('user.role.author') . " $a)",
				'biography' . $a => __('user.biography') . " (" . __('user.role.author') . " $a)"
			));
		}

		$columns = array_merge($columns, array(
			'section_title' => __('section.title'),
			'language' => __('common.language'),
			'editor_decision' => __('submission.editorDecision'),
			'status' => __('common.status')
		));

		$fp = fopen('php://output', 'wt');
		fputcsv($fp, array_values($columns));

		import('classes.article.Article'); // Bring in getStatusMap function
		$statusMap =& Article::getStatusMap();

		$authorIndex = 0;
		while ($row = $articlesIterator->next()) {
			$authors = $this->mergeAuthors($authorsIterator[$row['submission_id']]->toArray());

			foreach ($columns as $index => $junk) {
				if ($index == 'editor_decision') {
					if (isset($decisions[$row['submission_id']])) {
						$columns[$index] = $decisionMessages[$decisions[$row['submission_id']]];
					} else {
						$columns[$index] = $decisionMessages[null];
					}
				} elseif ($index == 'status') {
					$columns[$index] = __($statusMap[$row[$index]]);
				} elseif ($index == 'abstract') {
					$columns[$index] = html_entity_decode(strip_tags($row[$index]));
				} elseif (strstr($index, 'biography') !== false) {
					// "Convert" HTML to text for export
					$columns[$index] = isset($authors[$index])?html_entity_decode(strip_tags($authors[$index])):'';
				} else {
					if (isset($row[$index])) {
						$columns[$index] = $row[$index];
					} else if (isset($authors[$index])) {
						$columns[$index] = $authors[$index];
					} else $columns[$index] = '';
				}
			}
			fputcsv($fp, $columns);
			$authorIndex++;
		}

		fclose($fp);
	}

	/**
	 * Get the highest author count for any article (to determine how many columns to set)
	 * @param $authorsIterator DBRowIterator
	 * @return int
	 */
	function getMaxAuthorCount($authorsIterator) {
		$maxAuthors = 0;
		foreach ($authorsIterator as $authorIterator) {
			$maxAuthors = $authorIterator->getCount() > $maxAuthors ? $authorIterator->getCount() : $maxAuthors;
		}
		return $maxAuthors;
	}

	/**
	 * Flatten an array of author information into one array and append author sequence to each key
	 * @param $authors array
	 * @return array
	 */
	function mergeAuthors($authors) {
		$returner = array();
		$seq = 0;
		foreach($authors as $author) {
			$seq++;

			$returner['author_given' . $seq] = isset($author['author_given']) ? $author['author_given'] : '';
			$returner['author_family' . $seq] = isset($author['author_family']) ? $author['author_family'] : '';
			$returner['email' . $seq] = isset($author['email']) ? $author['email'] : '';
			$returner['affiliation' . $seq] = isset($author['affiliation']) ? $author['affiliation'] : '';
			$returner['country' . $seq] = isset($author['country']) ? $author['country'] : '';
			$returner['url' . $seq] = isset($author['url']) ? $author['url'] : '';
			$returner['biography' . $seq] = isset($author['biography']) ? $author['biography'] : '';
		}
		return $returner;
	}

}


