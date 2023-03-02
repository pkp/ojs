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
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;

import('lib.pkp.classes.plugins.GenericPlugin');

class RecommendBySimilarityPlugin extends GenericPlugin {
	const RECOMMEND_BY_SIMILARITY_PLUGIN_COUNT = 10;

	//
	// Implement template methods from Plugin.
	//
	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
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
	 * @see Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.recommendBySimilarity.displayName');
	}

	/**
	 * @see Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.recommendBySimilarity.description');
	}


	//
	// View level hook implementations.
	//
	/**
	 * @see templates/article/footer.tpl
	 */
	function callbackTemplateArticlePageFooter(string $hookName, $params) {
		/** @var Smarty */
		$smarty = $params[1];
		$output =& $params[2];

		// Identify similarity terms for the given article.
		$displayedArticle = $smarty->getTemplateVars('article');
		$articleId = $displayedArticle->getId();
		import('classes.search.ArticleSearch');
		import('classes.search.ArticleSearchIndex');
		$articleSearch = new ArticleSearch();
		$keywords = (new ArticleSearchIndex())->filterKeywords($articleSearch->getSimilarityTerms($articleId));
		$keywords = array_filter(array_unique($keywords), 'strlen');
		if (!count($keywords)) {
			return false;
		}

		$request = Application::get()->getRequest();
		$router = $request->getRouter();
		$journal = $router->getContext($request);
		$rangeInfo = Handler::getRangeInfo($request, 'articlesBySimilarity');
		$rangeInfo->setCount(static::RECOMMEND_BY_SIMILARITY_PLUGIN_COUNT);

		/** @var Builder */
		$queryBuilder = (new SubmissionQueryBuilder())
			->filterByContext($journal->getId())
			->filterByStatus(STATUS_PUBLISHED)
			->getQuery();

		$queryBuilder->where('s.submission_id', '<>', $articleId);

		$keywordIdFields = [];
		$orderByMatches = $orderByMatchCount = [];
		foreach ($keywords as $i => $term) {
			$alias = "k{$i}";
			$keywordIdFields[] = "{$alias}.keyword_id";
			$queryBuilder->leftJoin("submission_search_keyword_list AS {$alias}", function (JoinClause $join) use ($term, $alias) {
				$join->where("{$alias}.keyword_text", '=', $term);
			});
			$orderBy = '(' . Capsule::table('submission_search_objects', 'o')
				->join("submission_search_object_keywords AS o{$i}", "o{$i}.object_id", '=', 'o.object_id')
				->whereColumn("{$alias}.keyword_id", '=', "o{$i}.keyword_id")
				->whereColumn('s.submission_id', '=', 'o.submission_id')
				->selectRaw('COUNT(0)')
				->toSql() . ')';
			$orderByMatchCount[] = $orderBy;
			$orderByMatches[] = "CASE WHEN {$orderBy} = 0 THEN 1 ELSE 0 END";
		}
		// Clear any previous ORDER BY
		$queryBuilder->orders = [];
		$queryBuilder->orderBy(Capsule::raw(implode(' + ', $orderByMatches)))
			->orderByDesc(Capsule::raw(implode(' + ', $orderByMatchCount)))
			->whereExists(function (Builder $query) use ($keywordIdFields) {
				$query->from('submission_search_objects', 'o')
					->join('submission_search_object_keywords AS ok', 'o.object_id', '=', 'ok.object_id')
					->whereIn('ok.keyword_id', array_map('\\Illuminate\\Database\\Capsule\\Manager::raw', $keywordIdFields))
					->whereColumn('s.submission_id', '=', 'o.submission_id');
			});

		/** @var SubmissionDAO */
		$submissionDao = DAORegistry::getDAO('SubmissionDAO');
		$result = $submissionDao->retrieveRange($sql = $queryBuilder->toSql(), $params = $queryBuilder->getBindings(), $rangeInfo);
		$resultSet = new DAOResultFactory($result, $submissionDao, '_fromRow', [], $sql, $params, $rangeInfo);

		$smarty->assign([
			'articlesBySimilarity' => $resultSet,
			'articlesBySimilarityQuery' => implode(' ', $keywords),
			'journal' => $journal,
			'plugin' => $this
		]);
		$output .= $smarty->fetch($this->getTemplateResource('articleFooter.tpl'));
		return false;
	}

	public function getIssue(int $issueId): ?Issue
	{
		static $cache = [];
		/** @var IssueService */
		$issueService = Services::get('issue');
		return $cache[$issueId] ?? $cache[$issueId] = $issueService->get($issueId);
	}
}
