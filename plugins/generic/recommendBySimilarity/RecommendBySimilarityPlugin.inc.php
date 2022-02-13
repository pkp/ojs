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

use APP\core\Application;
use PKP\plugins\GenericPlugin;
use APP\search\ArticleSearch;

define('RECOMMEND_BY_SIMILARITY_PLUGIN_COUNT', 10);

class RecommendBySimilarityPlugin extends GenericPlugin
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
        return __('plugins.generic.recommendBySimilarity.displayName');
    }

    /**
     * @see Plugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.generic.recommendBySimilarity.description');
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

        // Identify similarity terms for the given article.
        $displayedArticle = $smarty->getTemplateVars('article');
        $articleId = $displayedArticle->getId();
        $articleSearch = new ArticleSearch();
        $searchTerms = $articleSearch->getSimilarityTerms($articleId);
        if (empty($searchTerms)) {
            return false;
        }

        // If we got similarity terms then execute a search with...
        // ... request, journal and error messages, ...
        $request = Application::get()->getRequest();
        $router = $request->getRouter();
        $journal = $router->getContext($request);
        $error = null;
        // ... search keywords ...
        $query = implode(' ', $searchTerms);
        $keywords = [null => $query];
        // ... and pagination.
        $rangeInfo = Handler::getRangeInfo($request, 'articlesBySimilarity');
        $rangeInfo->setCount(RECOMMEND_BY_SIMILARITY_PLUGIN_COUNT);
        $smarty->assign([
            'articlesBySimilarity' => $articleSearch->retrieveResults(
                $request,
                $journal,
                $keywords,
                $error,
                null,
                null,
                $rangeInfo,
                [$articleId]
            ),
            'articlesBySimilarityQuery' => $query,
        ]);
        $output .= $smarty->fetch($this->getTemplateResource('articleFooter.tpl'));
        return false;
    }
}
