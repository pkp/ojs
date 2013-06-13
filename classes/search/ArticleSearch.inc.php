<?php

/**
 * @file classes/search/ArticleSearch.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleSearch
 * @ingroup search
 * @see ArticleSearchDAO
 *
 * @brief Class for retrieving article search results.
 */

import('lib.pkp.classes.search.SubmissionSearch');

class ArticleSearch extends SubmissionSearch {
	/**
	 * Constructor
	 */
	function ArticleSearch() {
		parent::SubmissionSearch();
	}

	/**
	 * Retrieve the search filters from the
	 * request.
	 * @param $request Request
	 * @return array All search filters (empty and active)
	 */
	function getSearchFilters($request) {
		$searchFilters = array(
			'query' => $request->getUserVar('query'),
			'searchJournal' => $request->getUserVar('searchJournal'),
			'abstract' => $request->getUserVar('abstract'),
			'authors' => $request->getUserVar('authors'),
			'title' => $request->getUserVar('title'),
			'galleyFullText' => $request->getUserVar('galleyFullText'),
			'suppFiles' => $request->getUserVar('suppFiles'),
			'discipline' => $request->getUserVar('discipline'),
			'subject' => $request->getUserVar('subject'),
			'type' => $request->getUserVar('type'),
			'coverage' => $request->getUserVar('coverage'),
			'indexTerms' => $request->getUserVar('indexTerms')
		);

		// Is this a simplified query from the navigation
		// block plugin?
		$simpleQuery = $request->getUserVar('simpleQuery');
		if (!empty($simpleQuery)) {
			// In the case of a simplified query we get the
			// filter type from a drop-down.
			$searchType = $request->getUserVar('searchField');
			if (array_key_exists($searchType, $searchFilters)) {
				$searchFilters[$searchType] = $simpleQuery;
			}
		}

		// Publishing dates.
		$fromDate = $request->getUserDateVar('dateFrom', 1, 1);
		$searchFilters['fromDate'] = (is_null($fromDate) ? null : date('Y-m-d H:i:s', $fromDate));
		$toDate = $request->getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
		$searchFilters['toDate'] = (is_null($toDate) ? null : date('Y-m-d H:i:s', $toDate));

		// Instantiate the journal.
		$journal = $request->getJournal();
		$siteSearch = !((boolean)$journal);
		if ($siteSearch) {
			$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
			if (!empty($searchFilters['searchJournal'])) {
				$journal = $journalDao->getById($searchFilters['searchJournal']);
			} elseif (array_key_exists('journalTitle', $request->getUserVars())) {
				$journals = $journalDao->getTitles(false);
				while ($journal = $journals->next()) {
					if (in_array(
						$request->getUserVar('journalTitle'),
						(array) $journal->getTitle(null)
					)) break;
				}
			}
		}
		$searchFilters['searchJournal'] = $journal;
		$searchFilters['siteSearch'] = $siteSearch;

		return $searchFilters;
	}

	/**
	 * See implementation of retrieveResults for a description of this
	 * function.
	 *
	 * Note that this function is also called externally to fetch
	 * results for the title index, and possibly elsewhere.
	 *
	 * @return array An array with the articles, published articles,
	 *  issue, journal, section and the issue availability.
	 */
	static function formatResults(&$results) {
		$articleDao = DAORegistry::getDAO('ArticleDAO');
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$sectionDao = DAORegistry::getDAO('SectionDAO');

		$publishedArticleCache = array();
		$articleCache = array();
		$issueCache = array();
		$issueAvailabilityCache = array();
		$journalCache = array();
		$sectionCache = array();

		$returner = array();
		foreach ($results as $articleId) {
			// Get the article, storing in cache if necessary.
			if (!isset($articleCache[$articleId])) {
				$publishedArticleCache[$articleId] = $publishedArticleDao->getPublishedArticleByArticleId($articleId);
				$articleCache[$articleId] = $articleDao->getById($articleId);
			}
			$article = $articleCache[$articleId];
			$publishedArticle = $publishedArticleCache[$articleId];

			if ($publishedArticle && $article) {
				$sectionId = $article->getSectionId();
				if (!isset($sectionCache[$sectionId])) {
					$sectionCache[$sectionId] = $sectionDao->getById($sectionId);
				}

				// Get the journal, storing in cache if necessary.
				$journalId = $article->getJournalId();
				if (!isset($journalCache[$journalId])) {
					$journalCache[$journalId] = $journalDao->getById($journalId);
				}

				// Get the issue, storing in cache if necessary.
				$issueId = $publishedArticle->getIssueId();
				if (!isset($issueCache[$issueId])) {
					$issue = $issueDao->getById($issueId);
					$issueCache[$issueId] = $issue;
					import('classes.issue.IssueAction');
					$issueAction = new IssueAction();
					$issueAvailabilityCache[$issueId] = !$issueAction->subscriptionRequired($issue) || $issueAction->subscribedUser($journalCache[$journalId], $issueId, $articleId) || $issueAction->subscribedDomain($journalCache[$journalId], $issueId, $articleId);
				}

				// Only display articles from published issues.
				if (!$issueCache[$issueId]->getPublished()) continue;

				// Store the retrieved objects in the result array.
				$returner[] = array(
					'article' => $article,
					'publishedArticle' => $publishedArticleCache[$articleId],
					'issue' => $issueCache[$issueId],
					'journal' => $journalCache[$journalId],
					'issueAvailable' => $issueAvailabilityCache[$issueId],
					'section' => $sectionCache[$sectionId]
				);
			}
		}
		return $returner;
	}

	/**
	 * Return the search DAO
	 * @return DAO
	 */
	protected function getSearchDao() {
		return DAORegistry::getDAO('ArticleSearchDAO');
	}
}

?>
