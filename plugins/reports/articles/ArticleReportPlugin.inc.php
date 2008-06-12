<?php

/**
 * @file ArticleReportPlugin.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @package plugins.reports.article
 * @class ArticleReportPlugin
 *
 * Article report plugin
 *
 * $Id$
 */

import('classes.plugins.ReportPlugin');

class ArticleReportPlugin extends ReportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param @category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success) {
			$this->import('ArticleReportDAO');
			$articleReportDAO =& new ArticleReportDAO();
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
		return Locale::translate('plugins.reports.articles.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.reports.articles.description');
	}

	function display(&$args) {
		$journal =& Request::getJournal();

		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=report.csv');

		$articleReportDao =& DAORegistry::getDAO('ArticleReportDAO');
		list($articlesIterator, $decisionsIteratorsArray) = $articleReportDao->getArticleReport($journal->getJournalId());

		$decisions = array();
		foreach ($decisionsIteratorsArray as $decisionsIterator){
			while ($row =& $decisionsIterator->next()) {
				$decisions[$row['article_id']] = $row['decision'];
			}
		}

		import('classes.article.Article');
		$decisionMessages = array(
			SUBMISSION_EDITOR_DECISION_ACCEPT => Locale::translate('editor.article.decision.accept'),
			SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS => Locale::translate('editor.article.decision.pendingRevisions'),
			SUBMISSION_EDITOR_DECISION_RESUBMIT => Locale::translate('editor.article.decision.resubmit'),
			SUBMISSION_EDITOR_DECISION_DECLINE => Locale::translate('editor.article.decision.decline'),
			null => Locale::translate('plugins.reports.articles.nodecision')
		);

		$columns = array(
			'article_id' => Locale::translate('article.submissionId'),
			'title' => Locale::translate('article.title'),
			'abstract' => Locale::translate('article.abstract'),
			'fname' => Locale::translate('user.firstName'),
			'mname' => Locale::translate('user.middleName'),
			'lname' => Locale::translate('user.lastName'),
			'phone' => Locale::translate('user.phone'),
			'fax' => Locale::translate('user.fax'),
			'address' => Locale::translate('common.mailingAddress'),
			'country' => Locale::translate('common.country'),
			'affiliation' => Locale::translate('user.affiliation'),
			'email' => Locale::translate('user.email'),
			'url' => Locale::translate('user.url'),
			'biography' => Locale::translate('user.biography'),
			'section_title' => Locale::translate('section.title'),
			'language' => Locale::translate('common.language'),
			'status' => Locale::translate('common.status')
		);

		$fp = fopen('php://output', 'wt');
		String::fputcsv($fp, array_values($columns));

		while ($row =& $articlesIterator->next()) {
			foreach ($columns as $index => $junk) {
				if ( $index == "article_id" ){
					$columns[$index] = $row[$index];
					if (isset($decisions[$row[$index]])) {
						$columns['status'] = $decisionMessages[$decisions[$row[$index]]];
					} else {
						$columns['status'] = $decisionMessages[NULL];
					}
				} else if ($index == "status") {
					continue;
				} else {
					$columns[$index] = $row[$index];
				}
			}
			String::fputcsv($fp, $columns);
			unset($row);
		}
		
		fclose($fp);
	}
}

?>
