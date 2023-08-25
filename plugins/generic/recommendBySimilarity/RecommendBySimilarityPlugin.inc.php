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

import('lib.pkp.classes.plugins.GenericPlugin');

class RecommendBySimilarityPlugin extends GenericPlugin {
	private const DEFAULT_RECOMMENDATION_COUNT = 10;

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
			HookRegistry::register('Templates::Article::Footer::PageFooter', function (string $hookName, array $params): bool {
				$output = & $params[2];
				$output .= $this->buildTemplate();
				return false;
			});
		}
		return $success;
	}

	/**
	 * Builds the template with the recommended submissions or null if the linked submission has no keywords
	 * @see templates/article/footer.tpl
	 */
	private function buildTemplate(): ?string
	{
		$templateManager = TemplateManager::getManager();
		$submissionId = $templateManager->getTemplateVars('article')->getId();

		// If there's no keywords, quit
		if (empty($keywords = $this->_getKeywords($submissionId))) {
			return null;
		}

		$request = Application::get()->getRequest();
		$router = $request->getRouter();
		$context = $router->getContext($request);

		$rangeInfo = Handler::getRangeInfo($request, 'articlesBySimilarity');
		$rangeInfo->setCount(static::DEFAULT_RECOMMENDATION_COUNT);
		$queryBuilder = $this->_getQueryBuilder($context, $submissionId, $keywords);

		/** @var SubmissionDAO */
		$submissionDao = DAORegistry::getDAO('SubmissionDAO');
		$result = $submissionDao->retrieveRange($sql = $queryBuilder->toSql(), $params = $queryBuilder->getBindings(), $rangeInfo);
		$resultSet = new DAOResultFactory($result, $submissionDao, '_fromRow', [], $sql, $params, $rangeInfo);

		return $templateManager
			->assign('articlesBySimilarity', (object) [
				'submissions' => $resultSet,
				'query' => implode(' ', $keywords),
				'plugin' => $this
			])
			->fetch($this->getTemplateResource('articleFooter.tpl'));
	}

	/**
	 * Builds a query builder to select similar articles using the given submission and keywords
	 */
	private function _getQueryBuilder(Context $context, int $submissionId, array $keywords): Builder
	{
		return (new SubmissionQueryBuilder())
			->filterByContext($context->getId())
			->filterByStatus(STATUS_PUBLISHED)
			->getQuery()
			->where(function (Builder $q) use ($keywords) {
				foreach ($keywords as $keyword) {
					$q->orWhereExists(function (Builder $query) use ($keyword) {
						$query
							->from('submission_search_objects', 'sso')
							->join('submission_search_object_keywords AS ssok', 'sso.object_id', '=', 'ssok.object_id')
							->join('submission_search_keyword_list AS sskl', 'sskl.keyword_id', '=', 'ssok.keyword_id')
							->where('sskl.keyword_text', '=', Capsule::raw('LOWER(?)'))->addBinding($keyword)
							->whereColumn('s.submission_id', '=', 'sso.submission_id');
					});
				}
			})
			// Skips itself
			->where('s.submission_id', '<>', $submissionId)
			// Clears any previous ORDER BY from the query builder
			->reorder()
			// Order by the number of matches for all keywords
			->orderBy(
				$orderByMatchCount = Capsule::table('submission_search_objects', 'sso')
					->join('submission_search_object_keywords AS ssok', 'ssok.object_id', '=', 'sso.object_id')
					->join('submission_search_keyword_list AS sskl', 'sskl.keyword_id', '=', 'ssok.keyword_id')
					->where(function (Builder $q) use ($keywords) {
						foreach ($keywords as $keyword) {
							$q->orWhere('sskl.keyword_text', '=', Capsule::raw('LOWER(?)'))->addBinding($keyword);
						}
					})
					->whereColumn('s.submission_id', '=', 'sso.submission_id')
					->selectRaw('COUNT(0)'),
				'DESC'
			)
			// Order by the number of distinct matched keywords
			->orderBy(
				(clone $orderByMatchCount)->select(Capsule::raw('COUNT(DISTINCT sskl.keyword_id)')),
				'DESC'
			);
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
	 * @copydoc Plugin::getDisplayName()
	 */
	public function getDisplayName(): string
	{
		return __('plugins.generic.recommendBySimilarity.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	public function getDescription(): string
	{
		return __('plugins.generic.recommendBySimilarity.description');
	}
}
