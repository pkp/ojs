<?php

/**
 * @file plugins/generic/recommendByAuthor/RecommendByAuthorPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RecommendByAuthorPlugin
 * @ingroup plugins_generic_recommendByAuthor
 *
 * @brief Plugin to recommend articles from the same author.
 */

use APP\core\Application;
use APP\facades\Repo;
use PKP\plugins\GenericPlugin;
use PKP\statistics\PKPStatisticsHelper;
use PKP\submission\PKPSubmission;
use APP\search\ArticleSearch;
use PKP\core\VirtualArrayIterator;

define('RECOMMEND_BY_AUTHOR_PLUGIN_COUNT', 10);

class RecommendByAuthorPlugin extends GenericPlugin
{
    //
    // Implement template methods from Plugin.
    //
    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        if (Application::isUnderMaintenance()) {
            return $success;
        }

        if ($success && $this->getEnabled($mainContextId)) {
            HookRegistry::register('Templates::Article::Footer::PageFooter', [$this, 'callbackTemplateArticlePageFooter']);
        }
        return $success;
    }

    /**
     * @see Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.generic.recommendByAuthor.displayName');
    }

    /**
     * @see Plugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.generic.recommendByAuthor.description');
    }


    //
    // View level hook implementations.
    //
    /**
     * @see templates/article/footer.tpl
     */
    public function callbackTemplateArticlePageFooter($hookName, $params)
    {
        $smarty = & $params[1];
        $output = & $params[2];

        // Find articles of the same author(s).
        $displayedArticle = $smarty->getTemplateVars('article');
        $authors = Repo::author()->getSubmissionAuthors($displayedArticle);
        $foundArticles = [];
        foreach ($authors as $author) { /** @var Author $author */
            // The following article search is by name only as authors are
            // not normalized in OJS. This is rather crude and may produce
            // false positives or miss some entries. But there's no other way
            // until OJS allows users to consistently normalize authors (via name,
            // email, ORCID, whatever).
            $authorsIterator = Repo::author()->getMany(
                Repo::author()
                    ->getCollector()
                    ->filterByContextIds([$displayedArticle->getData('contextId')])
                    ->filterByName($author->getLocalizedGivenName(), $author->getLocalizedFamilyName())
            );

            $publicationIds = [];
            foreach ($authorsIterator as $thisAuthor) {
                $publicationIds[] = $thisAuthor->getData('publicationId');
            }
            $submissionIds = array_map(function ($publicationId) {
                $publication = Repo::publication()->get($publicationId);
                return $publication->getData('status') == PKPSubmission::STATUS_PUBLISHED ? $publication->getData('submissionId') : null;
            }, array_unique($publicationIds));
            $foundArticles = array_unique(array_merge($foundArticles, $submissionIds));
        }

        $results = array_filter($foundArticles, function ($value) use ($displayedArticle) {
            if ($value !== $displayedArticle->getId()) {
                return $value;
            }
            return null;
        });

        // Order results by metric.
        $application = Application::get();
        $metricType = $application->getDefaultMetricType();
        if (empty($metricType)) {
            $smarty->assign('noMetricSelected', true);
        }
        $column = PKPStatisticsHelper::STATISTICS_DIMENSION_SUBMISSION_ID;
        $filter = [
            PKPStatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE => [ASSOC_TYPE_GALLEY, ASSOC_TYPE_SUBMISSION],
            PKPStatisticsHelper::STATISTICS_DIMENSION_SUBMISSION_ID => [$results]
        ];
        $orderBy = [PKPStatisticsHelper::STATISTICS_METRIC => PKPStatisticsHelper::STATISTICS_ORDER_DESC];
        $statsReport = $application->getMetrics($metricType, $column, $filter, $orderBy);
        $orderedResults = [];
        foreach ((array) $statsReport as $reportRow) {
            $orderedResults[] = $reportRow['submission_id'];
        }
        // Make sure we even get results that have no statistics (yet) and that
        // we get them in some consistent order for paging.
        $remainingResults = array_diff($results, $orderedResults);
        sort($remainingResults);
        $orderedResults = array_merge($orderedResults, $remainingResults);

        // Pagination.
        $request = Application::get()->getRequest();
        $rangeInfo = Handler::getRangeInfo($request, 'articlesBySameAuthor');
        if ($rangeInfo && $rangeInfo->isValid()) {
            $page = $rangeInfo->getPage();
        } else {
            $page = 1;
        }
        $totalResults = count($orderedResults);
        $itemsPerPage = RECOMMEND_BY_AUTHOR_PLUGIN_COUNT;
        $offset = $itemsPerPage * ($page - 1);
        $length = max($totalResults - $offset, 0);
        $length = min($itemsPerPage, $length);
        if ($length == 0) {
            $pagedResults = [];
        } else {
            $pagedResults = array_slice(
                $orderedResults,
                $offset,
                $length
            );
        }

        // Visualization.
        $articleSearch = new ArticleSearch();
        $pagedResults = $articleSearch->formatResults($pagedResults);
        $returner = new VirtualArrayIterator($pagedResults, $totalResults, $page, $itemsPerPage);
        $smarty->assign('articlesBySameAuthor', $returner);
        $output .= $smarty->fetch($this->getTemplateResource('articleFooter.tpl'));
        return false;
    }
}
