<?php

/**
 * @file plugins/generic/recommendBySimilarity/RecommendBySimilarityPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RecommendBySimilarityPlugin
 * @ingroup plugins_generic_recommendBySimilarity
 *
 * @brief Plugin to recommend similar articles.
 */

use APP\Services\IssueService;
use APP\Services\QueryBuilders\SubmissionQueryBuilder;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;

import('lib.pkp.classes.plugins.GenericPlugin');

class RecommendBySimilarityPlugin extends GenericPlugin {
	private const RECOMMEND_BY_SIMILARITY_PLUGIN_COUNT = 10;

	/**
	 * @copydoc Plugin::register()
	 */
	public function register($category, $path, $mainContextId = null): bool
	{
		$success = parent::register($category, $path, $mainContextId);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) {
			return $success;
		}

		if ($success && $this->getEnabled($mainContextId)) {
			HookRegistry::register('Templates::Article::Footer::PageFooter', array($this, 'callbackTemplateArticlePageFooter'));
		}
		return $success;
	}

	/**
	 * @see templates/article/footer.tpl
	 * @todo Revert back to using the ArticleSearch::retrieveResults() once its performance is addressed
	 */
	public function callbackTemplateArticlePageFooter(string $hookName, $params): bool
	{
		/** @var Smarty */
		$smarty = $params[1];
		$output =& $params[2];

		$displayedArticle = $smarty->getTemplateVars('article');
		$submissionId = $displayedArticle->getId();

		// If there's no keywords, quit
		if (empty($keywords = $this->_getKeywords($submissionId))) {
			return false;
		}

		$request = Application::get()->getRequest();
		$router = $request->getRouter();
		$context = $router->getContext($request);

		/** @var SubmissionDAO */
		$submissionDao = DAORegistry::getDAO('SubmissionDAO');
		$rangeInfo = Handler::getRangeInfo($request, 'articlesBySimilarity');
		$rangeInfo->setCount(static::RECOMMEND_BY_SIMILARITY_PLUGIN_COUNT);
		$queryBuilder = $this->_getQueryBuilder($context, $submissionId, $keywords);
		$result = $submissionDao->retrieveRange($sql = $queryBuilder->toSql(), $params = $queryBuilder->getBindings(), $rangeInfo);
		$resultSet = new DAOResultFactory($result, $submissionDao, '_fromRow', [], $sql, $params, $rangeInfo);

		$smarty->assign([
			'articlesBySimilarity' => $resultSet,
			'articlesBySimilarityQuery' => implode(' ', $keywords),
			'journal' => $context,
			'plugin' => $this
		]);

		$output .= $smarty->fetch($this->getTemplateResource('articleFooter.tpl'));
		return false;
	}

	/**
	 * Builds a query builder to select similar articles using the given submission and keywords
	 */
	private function _getQueryBuilder(Context $context, int $submissionId, array $keywords): Builder
	{
		/** @var Builder */
		$queryBuilder = (new SubmissionQueryBuilder())
			->filterByContext($context->getId())
			->filterByStatus(STATUS_PUBLISHED)
			->getQuery();

		$keywordIdFields = $orderByMatches = $orderByMatchCount = [];
		foreach ($keywords as $i => $term) {
			// Adds one join for each keyword
			$queryBuilder->leftJoin("submission_search_keyword_list AS k{$i}", function (JoinClause $join) use ($term, $i) {
				$join->where("k{$i}.keyword_text", '=', $term);
			});
			// Base ORDER BY clause: retrieves the number of matches for the keyword
			$orderBy = '(' . Capsule::table('submission_search_objects', 'o')
				->join("submission_search_object_keywords AS o{$i}", "o{$i}.object_id", '=', 'o.object_id')
				->whereColumn("k{$i}.keyword_id", '=', "o{$i}.keyword_id")
				->whereColumn('s.submission_id', '=', 'o.submission_id')
				->selectRaw('COUNT(0)')
				->toSql() . ')';
			// List of primary ORDER BY fields
			$orderByMatches[] = "CASE WHEN {$orderBy} = 0 THEN 1 ELSE 0 END";
			// List of secondary ORDER BY fields
			$orderByMatchCount[] = $orderBy;
			// Keeps track of all keyword IDs
			$keywordIdFields[] = "k{$i}.keyword_id";
		}

		return $queryBuilder
			// Skips itself
			->where('s.submission_id', '<>', $submissionId)
			// Clears any previous ORDER BY from the query builder
			->reorder()
			// First order by the number of keywords found (rows that have all keywords will be placed higher)
			->orderBy(Capsule::raw(implode('+', $orderByMatches)))
			// Then order by the total number of matches
			->orderByDesc(Capsule::raw(implode('+', $orderByMatchCount)))
			// Only brings rows that have at least one matching keyword
			->whereExists(function (Builder $query) use ($keywordIdFields) {
				$query->from('submission_search_objects', 'o')
					->join('submission_search_object_keywords AS ok', 'o.object_id', '=', 'ok.object_id')
					->whereIn('ok.keyword_id', array_map([Manager::class, 'raw'], $keywordIdFields))
					->whereColumn('s.submission_id', '=', 'o.submission_id');
			});
	}

	/**
	 * Retrieves the search keywords from the submission
	 * @return string[]
	 */
	private function _getKeywords(int $submissionId): array
	{
		import('classes.search.ArticleSearch');
		import('classes.search.ArticleSearchIndex');
		// Retrieve the list of subjects for the submission
		$keywords = (new ArticleSearchIndex())->filterKeywords((new ArticleSearch())->getSimilarityTerms($submissionId));
		return array_filter(array_unique($keywords), 'strlen');
	}

	/**
	 * Retrieves an issue and keeps a small local cache
	 * This method is used internally by the template file
	 */
	public function getIssue(int $issueId): ?Issue
	{
		static $cache = [];
		/** @var IssueService */
		$issueService = Services::get('issue');
		return $cache[$issueId] ?? $cache[$issueId] = $issueService->get($issueId);
	}

	/**
	 * @see Plugin::getDisplayName()
	 */
	public function getDisplayName(): string
	{
		return __('plugins.generic.recommendBySimilarity.displayName');
	}

	/**
	 * @see Plugin::getDescription()
	 */
	public function getDescription(): string
	{
		return __('plugins.generic.recommendBySimilarity.description');
	}
}
