<?php

/**
 * @file plugins/reports/articles/ArticleReportPlugin.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
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
		list($articlesIterator, $authorsIterator, $editorsIterator, $decisionsIterator) = $articleReportDao->getArticleReport($journal->getId());

		$maxAuthors = $this->getMaxCount($authorsIterator);
		$maxEditors = $this->getMaxCount($editorsIterator);
		$maxDecisions = 0;
		foreach ($decisionsIterator as $decisionsIteratorForArticle) {
			$maxDecisionsForArticle = $this->getMaxCount($decisionsIteratorForArticle);
			if ($maxDecisionsForArticle > $maxDecisions) $maxDecisions = $maxDecisionsForArticle;
		}

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR, LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_PKP_READER);

		import('classes.article.Submission');

		$columns = array(
			'submission_id' => __('article.submissionId'),
			'title' => __('article.title'),
			'abstract' => __('article.abstract')
		);

		for ($a = 1; $a <= $maxAuthors; $a++) {
			$columns = array_merge($columns, array(
				'author_given' . $a => __('user.givenName') . " (" . __('user.role.author') . " $a)",
				'author_family' . $a => __('user.familyName') . " (" . __('user.role.author') . " $a)",
				'orcid' . $a => __('user.orcid') . " (" . __('user.role.author') . " $a)",
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
			'status' => __('common.status'),
			'url' => __('common.url'),
			'doi' => __('metadata.property.displayName.doi'),
			'date_submitted' => __('common.dateSubmitted'),
			'last_modified' => __('submission.lastModified'),
		));

		for ($e = 1; $e <= $maxEditors; $e++) {
			$columns = array_merge($columns, array(
					'editor_given' . $e => __('user.givenName') . " (" . __('user.role.editor') . " $e)",
					'editor_family' . $e => __('user.familyName') . " (" . __('user.role.editor') . " $e)",
					'editor_orcid' . $e => __('user.orcid') . " (" . __('user.role.editor') . " $e)",
					'editor_email' . $e => __('user.email') . " (" . __('user.role.editor') . " $e)",
					//'editor_country' . $e => __('common.country') . " (" . __('user.role.editor') . " $e)",
					//'editor_affiliation' . $e => __('user.affiliation') . " (" . __('user.role.editor') . " $e)",
					//'editor_url' . $e => __('user.url') . " (" . __('user.role.editor') . " $e)",
					//'editor_biography' . $e => __('user.biography') . " (" . __('user.role.editor') . " $e)"
			));
			for ($d = 1; $d <= $maxDecisions; $d++) {
				$columns = array_merge($columns, array(
						'editor_decision' . $e . $d => __('submission.editorDecision') . " $d " . " (" . __('user.role.editor') . " $e)",
						'editor_decision_date' . $e . $d => __('common.dateDecided') . " $d " . " (" . __('user.role.editor') . " $e)"
				));
			}
		}

		$fp = fopen('php://output', 'wt');
		//Add BOM (byte order mark) to fix UTF-8 in Excel
		fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
		fputcsv($fp, array_values($columns));

		import('classes.article.Submission'); // Bring in getStatusMap function
		$statusMap = Article::getStatusMap();

		$submissionKeywordDao = DAORegistry::getDAO('SubmissionKeywordDAO');
		$submissionSubjectDao = DAORegistry::getDAO('SubmissionSubjectDAO');
		$submissionDisciplineDao = DAORegistry::getDAO('SubmissionDisciplineDAO');
		$submissionAgencyDao = DAORegistry::getDAO('SubmissionAgencyDAO');

		$authorIndex = 0;
		while ($article = $articlesIterator->next()) {
			if ($article->getSubmissionProgress()) continue; // Incomplete submission
			$authors = $this->mergeAuthors($authorsIterator[$article->getId()]->toArray());
			$editors = array();
			if (array_key_exists($article->getId(), $editorsIterator)) {
				$decisionsIteratorForArticle = null;
				if (array_key_exists($article->getId(), $decisionsIterator)) $decisionsIteratorForArticle = $decisionsIterator[$article->getId()];
				$editors = $this->mergeEditors($editorsIterator[$article->getId()]->toArray(), $decisionsIteratorForArticle);
			}

			foreach ($columns as $index => $junk) switch(true) {
				case $index == 'status':
					if ($article->getStatus() == STATUS_QUEUED) {
						$columns[$index] = $this->getStageLabel($article->getStageId());
					} else {
						$columns[$index] = __($statusMap[$article->getStatus()]);
					}
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
				case $index == 'date_submitted': $columns[$index] = $article->getDateSubmitted(); break;
				case $index == 'last_modified': $columns[$index] = $article->getLastModified(); break;
				case isset($authors[$index]): $columns[$index] = $authors[$index]; break;
				case isset($editors[$index]): $columns[$index] = $editors[$index]; break;
				default: $columns[$index] = '';
			}
			fputcsv($fp, $columns);
			$authorIndex++;
		}

		fclose($fp);
	}

	/**
	 * Get the highest authors and editors count for any article (to determine how many columns to set)
	 * @param $iterator DBRowIterator
	 * @return int
	 */
	function getMaxCount($iterator) {
		$maxCount = 0;
		foreach ($iterator as $iteratorItem) {
			$maxCount = $iteratorItem->getCount() > $maxCount ? $iteratorItem->getCount() : $maxCount;
		}
		return $maxCount;
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
			$returner['orcid' . $seq] = isset($author['orcid']) ? $author['orcid'] : '';
		}
		return $returner;
	}

	/**
	 * Flatten an array of editor information into one array and append author sequence to each key
	 * @param $editors array
	 * @param $decisionsIterator array (editor ID => decisions iterator)
	 * @return array
	 */
	function mergeEditors($editors, $decisionsIterator = null) {
		$returner = array();
		$eSeq = $dSeq = 0;
		foreach($editors as $editor) {
			$eSeq++;
			$returner['editor_given' . $eSeq] = isset($editor['user_given']) ? $editor['user_given'] : '';
			$returner['editor_family' . $eSeq] = isset($editor['user_family']) ? $editor['user_family'] : '';
			$returner['editor_orcid' . $eSeq] = isset($editor['orcid']) ? $editor['orcid'] : '';
			$returner['editor_email' . $eSeq] = isset($editor['email']) ? $editor['email'] : '';
			//$returner['editor_affiliation' . $eSeq] = isset($editor['affiliation']) ? $editor['affiliation'] : '';
			//$returner['editor_country' . $eSeq] = isset($editor['country']) ? $editor['country'] : '';
			//$returner['editor_url' . $eSeq] = isset($editor['url']) ? $editor['url'] : '';
			//$returner['editor_biography' . $eSeq] = isset($editor['biography']) ? $editor['biography'] : '';
			if ($decisionsIterator && array_key_exists($editor['editor_id'], $decisionsIterator)) {
				$decisions = $decisionsIterator[$editor['editor_id']]->toArray();
				foreach ($decisions as $decision) {
					$dSeq++;
					$returner['editor_decision' . $eSeq . $dSeq] = isset($decision['decision']) ? $this->getDecisionMessage($decision['decision']) : '';
					$returner['editor_decision_date' . $eSeq . $dSeq] = isset($decision['date_decided']) ? $decision['date_decided'] : '';
				}
			}
		}
		return $returner;
	}

	/**
	 * Get stage label
	 * @param $stageId int
	 * @return string
	 */
	function getStageLabel($stageId) {
		switch ($stageId) {
			case WORKFLOW_STAGE_ID_SUBMISSION:
				return __('submission.submission');
			case WORKFLOW_STAGE_ID_EXTERNAL_REVIEW:
				return __('submission.review');
			case WORKFLOW_STAGE_ID_EDITING:
				return __('submission.copyediting');
			case WORKFLOW_STAGE_ID_PRODUCTION:
				return __('submission.production');
		}
		return '';
	}

	/**
	 * Get decision message
	 * @param $decision int
	 * @return string
	 */
	function getDecisionMessage($decision) {
		import('classes.workflow.EditorDecisionActionsManager'); // SUBMISSION_EDITOR_...
		switch ($decision) {
			case SUBMISSION_EDITOR_DECISION_ACCEPT:
				return __('editor.submission.decision.accept');
			case SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS:
				return __('editor.submission.decision.requestRevisions');
			case SUBMISSION_EDITOR_DECISION_RESUBMIT:
				return __('editor.submission.decision.resubmit');
			case SUBMISSION_EDITOR_DECISION_DECLINE:
				return __('editor.submission.decision.decline');
			case SUBMISSION_EDITOR_DECISION_SEND_TO_PRODUCTION:
				return __('editor.submission.decision.sendToProduction');
			case SUBMISSION_EDITOR_DECISION_EXTERNAL_REVIEW:
				return __('editor.submission.decision.sendExternalReview');
			case SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE:
				return __('editor.submission.decision.decline');
			case SUBMISSION_EDITOR_RECOMMEND_ACCEPT:
				return __('editor.submission.recommendation.display', array('recommendation' => __('editor.submission.decision.accept')));
			case SUBMISSION_EDITOR_RECOMMEND_DECLINE:
				return __('editor.submission.recommendation.display', array('recommendation' => __('editor.submission.decision.decline')));
			case SUBMISSION_EDITOR_RECOMMEND_PENDING_REVISIONS:
				return __('editor.submission.recommendation.display', array('recommendation' => __('editor.submission.decision.requestRevisions')));
			case SUBMISSION_EDITOR_RECOMMEND_RESUBMIT:
				return __('editor.submission.recommendation.display', array('recommendation' => __('editor.submission.decision.resubmit')));
			default:
				return '';
		}
	}

}

