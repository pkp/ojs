<?php

/**
 * @file plugins/generic/recommendByAuthor/RecommendByAuthorPlugin.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RecommendByAuthorPlugin
 *
 * @brief Plugin to recommend articles from the same author.
 */

namespace APP\plugins\generic\recommendByAuthor;

use APP\author\Author;
use APP\core\Application;
use APP\core\Services;
use APP\facades\Repo;
use APP\handler\Handler;
use APP\search\ArticleSearch;
use APP\statistics\StatisticsHelper;
use PKP\core\VirtualArrayIterator;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\submission\PKPSubmission;

class RecommendByAuthorPlugin extends GenericPlugin
{
    public const RECOMMEND_BY_AUTHOR_PLUGIN_COUNT = 10;

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
            Hook::add('Templates::Article::Footer::PageFooter', [$this, 'callbackTemplateArticlePageFooter']);
        }
        return $success;
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.generic.recommendByAuthor.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.generic.recommendByAuthor.description');
    }


    //
    // View level hook implementations.
    //
    /**
     * Add content to the article footer.
     */
    public function callbackTemplateArticlePageFooter($hookName, $params)
    {
        $smarty = & $params[1];
        $output = & $params[2];

        // Find articles of the same author(s).
        $displayedArticle = $smarty->getTemplateVars('article');
        $displayedPublication = $smarty->getTemplateVars('publication');
        $authors = $displayedPublication->getData('authors');
        $foundArticles = [];
        foreach ($authors as $author) { /** @var Author $author */
            // The following article search is by name only as authors are
            // not normalized in OJS. This is rather crude and may produce
            // false positives or miss some entries. But there's no other way
            // until OJS allows users to consistently normalize authors (via name,
            // email, ORCID, whatever).
            $authorsIterator = Repo::author()
                ->getCollector()
                ->filterByContextIds([$displayedArticle->getData('contextId')])
                ->filterByName($author->getLocalizedGivenName(), $author->getLocalizedFamilyName())
                ->getMany();

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
        $filters = [
            'contextIds' => [$displayedArticle->getData('contextId')],
            'submissionIds' => $results,
            'assocTypes' => [Application::ASSOC_TYPE_SUBMISSION, Application::ASSOC_TYPE_SUBMISSION_FILE]
        ];

        $orderedResults = [];
        if ($results) {
            // pkp/pkp-lib#9512: Check $results above, as an empty list of submissionIds is treated as no filter at all.
            $statsReport = Services::get('publicationStats')->getTotals($filters);
            foreach ($statsReport as $reportRow) {
                $orderedResults[] = $reportRow->{StatisticsHelper::STATISTICS_DIMENSION_SUBMISSION_ID};
            }
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
        $itemsPerPage = self::RECOMMEND_BY_AUTHOR_PLUGIN_COUNT;
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
