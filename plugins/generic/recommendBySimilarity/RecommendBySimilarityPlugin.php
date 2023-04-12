<?php

/**
 * @file plugins/generic/recommendBySimilarity/RecommendBySimilarityPlugin.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2003-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RecommendBySimilarityPlugin
 *
 * @brief Plugin to recommend similar articles.
 */

namespace APP\plugins\generic\recommendBySimilarity;

use APP\core\Application;
use APP\facades\Repo;
use APP\handler\Handler;
use APP\search\ArticleSearch;
use APP\submission\Collector;
use APP\submission\Submission;
use APP\template\TemplateManager;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class RecommendBySimilarityPlugin extends GenericPlugin
{
    private const DEFAULT_RECOMMENDATION_COUNT = 10;

    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null): bool
    {
        if (!parent::register($category, $path, $mainContextId)) {
            return false;
        }

        if (!Application::isUnderMaintenance() && $this->getEnabled($mainContextId)) {
            Hook::add('Templates::Article::Footer::PageFooter', function (string $hookName, array $params): bool {
                $output = & $params[2];
                $output .= $this->buildTemplate();
                return Hook::CONTINUE;
            });
        }
        return true;
    }

    /**
     * Hook handler which is responsible to add content to the article footer
     *
     * @see templates/article/footer.tpl
     */
    private function buildTemplate(): ?string
    {
        $templateManager = TemplateManager::getManager();
        $submissionId = $templateManager->getTemplateVars('article')->getId();

        // If there's no keywords, quit
        if (!strlen($searchPhrase = implode(' ', (new ArticleSearch())->getSimilarityTerms($submissionId)))) {
            return null;
        }

        $request = Application::get()->getRequest();
        $router = $request->getRouter();
        $context = $router->getContext($request);

        $rangeInfo = Handler::getRangeInfo($request, 'articlesBySimilarity');
        $rangeInfo->setCount(static::DEFAULT_RECOMMENDATION_COUNT);

        $collector = Repo::submission()
            ->getCollector()
            ->excludeIds([$submissionId])
            ->filterByContextIds([$context->getId()])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->searchPhrase($searchPhrase);

        $submissionCount = $collector->getCount();
        $submissions = $collector
            ->limit($rangeInfo->getCount())
            ->offset($rangeInfo->getOffset())
            ->orderBy(Collector::ORDERBY_SEARCH_RANKING)
            ->getMany();
        $issues = Repo::issue()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByIssueIds(
                $submissions->map(fn (Submission $submission) => $submission->getCurrentPublication()->getData('issueId'))
                    ->unique()
                    ->toArray()
            )
            ->getMany();

        $templateManager->assign('articlesBySimilarity', (object) [
            'submissions' => $submissions,
            'query' => $searchPhrase,
            'issues' => $issues,
            'start' => $rangeInfo->getOffset() + 1,
            'end' => $rangeInfo->getOffset() + $submissions->count(),
            'total' => $submissionCount,
            'nextUrl' => $rangeInfo->getPage() * $rangeInfo->getCount() < $submissionCount ? $request->url(params: ['articlesBySimilarity' => $rangeInfo->getPage() + 1]) : null,
            'previousUrl' => $rangeInfo->getPage() > 1 ? $request->url(params: ['articlesBySimilarity' => $rangeInfo->getPage() - 1]) : null
        ]);

        return $templateManager->fetch($this->getTemplateResource('articleFooter.tpl'));
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
