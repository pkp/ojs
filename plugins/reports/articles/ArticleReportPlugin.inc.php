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

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.reports.articles.displayName');
	}

	/**
	 * @copydoc Plugin::getDescriptionName()
	 */
	function getDescription() {
		return __('plugins.reports.articles.description');
	}

	/**
	 * @copydoc ReportPlugin::display()
	 */
	function display($args, $request) {
		$journal = $request->getJournal();
		$acronym = PKPString::regexp_replace("/[^A-Za-z0-9 ]/", '', $journal->getLocalizedAcronym());

		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=articles-' . $acronym . '-' . date('Ymd') . '.csv');

		$articleReportDao = DAORegistry::getDAO('ArticleReportDAO');
		list($articlesIterator, $authorsIterator, $decisionsIteratorsArray) = $articleReportDao->getArticleReport($journal->getId());

		$maxAuthors = $this->getMaxAuthorCount($authorsIterator);

		$decisions = array();
		foreach ($decisionsIteratorsArray as $decisionsIterator) {
			while ($row = $decisionsIterator->next()) {
				$decisions[$row['submission_id']] = $row['decision'];
			}
		}

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR, LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_PKP_READER);

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
			'coverage' => __('rt.metadata.dublinCore.coverage'),
			'rights' => __('rt.metadata.dublinCore.rights'),
			'source' => __('rt.metadata.dublinCore.source'),
			'subjects' => __('rt.metadata.dublinCore.subject'),
			'type' => __('rt.metadata.dublinCore.type'),
			'disciplines' => __('rt.metadata.pkp.discipline'),
			'keywords' => __('rt.metadata.pkp.subject'),
			'agencies' => __('submission.supportingAgencies'),
			'editor_decision' => __('submission.editorDecision'),
			'status' => __('common.status'),
			'url' => __('common.url'),
			'doi' => __('metadata.property.displayName.doi'),
		));

		$fp = fopen('php://output', 'wt');
		//Add BOM (byte order mark) to fix UTF-8 in Excel
		fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
		fputcsv($fp, array_values($columns));

		import('classes.article.Article'); // Bring in getStatusMap function
		$statusMap = Article::getStatusMap();

		$submissionKeywordDao = DAORegistry::getDAO('SubmissionKeywordDAO');
		$submissionSubjectDao = DAORegistry::getDAO('SubmissionSubjectDAO');
		$submissionDisciplineDao = DAORegistry::getDAO('SubmissionDisciplineDAO');
		$submissionAgencyDao = DAORegistry::getDAO('SubmissionAgencyDAO');

		$authorIndex = 0;
		while ($article = $articlesIterator->next()) {
			if ($article->getSubmissionProgress()) continue; // Incomplete submission
			$authors = $this->mergeAuthors($authorsIterator[$article->getId()]->toArray());

			foreach ($columns as $index => $junk) switch(true) {
				case $index == 'editor_decision':
					if (isset($decisions[$article->getId()])) {
						$columns[$index] = $decisionMessages[$decisions[$article->getId()]];
					} else {
						$columns[$index] = $decisionMessages[null];
					}
					break;
				case $index == 'status':
					$columns[$index] = __($statusMap[$article->getStatus()]);
					break;
				case $index == 'abstract':
					$columns[$index] = html_entity_decode(strip_tags($article->getLocalizedAbstract()));
					break;
				case strstr($index, 'biography') !== false:
					// "Convert" HTML to text for export
					$columns[$index] = isset($authors[$index])?html_entity_decode(strip_tags($authors[$index])):'';
					break;
				case $index == 'url':
					$columns[$index] = $request->url(null, 'workflow', 'access', $article->getId());
					break;
				case $index == 'doi':
					$columns[$index] = $article->getStoredPubId('doi');
					break;
				case $index == 'submission_id': $columns[$index] = $article->getId(); break;
				case $index == 'title': $columns[$index] = $article->getLocalizedTitle(); break;
				case $index == 'section_title': $columns[$index] = $article->getSectionTitle(); break;
				case $index == 'language': $columns[$index] = $article->getLanguage(); break;
				case $index == 'coverage': $columns[$index] = $article->getLocalizedCoverage(); break;
				case $index == 'rights': $columns[$index] = $article->getRights($article->getLocale()); break;
				case $index == 'source': $columns[$index] = $article->getSource($article->getLocale()); break;
				case $index == 'type': $columns[$index] = $article->getLocalizedType(); break;
				case $index == 'subjects':
					$subjects = $submissionSubjectDao->getSubjects($article->getId(), array($article->getLocale()));
					$columns[$index] = join(', ', $subjects[$article->getLocale()]);
					break;
				case $index == 'disciplines':
					$disciplines = $submissionDisciplineDao->getDisciplines($article->getId(), array($article->getLocale()));
					$columns[$index] = join(', ', $disciplines[$article->getLocale()]);
					break;
				case $index == 'keywords':
					$keywords = $submissionKeywordDao->getKeywords($article->getId(), array($article->getLocale()));
					$columns[$index] = join(', ', $keywords[$article->getLocale()]);
					break;
				case $index == 'agencies':
					$agencies = $submissionAgencyDao->getAgencies($article->getId(), array($article->getLocale()));
					$columns[$index] = join(', ', $agencies[$article->getLocale()]);
					break;
				case isset($authors[$index]): $columns[$index] = $authors[$index]; break;
				default: $columns[$index] = '';
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

