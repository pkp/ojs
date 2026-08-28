<?php

/**
 * @file classes/view/MetadataBlockRepository.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Repository
 *
 * @brief A repository to register and load metadata blocks.
 */

namespace APP\view;

use APP\core\Application;
use APP\facades\Repo;
use APP\issue\Collector as IssueCollector;
use APP\submission\Collector;
use APP\submission\Submission;
use APP\template\TemplateManager;
use Illuminate\Support\LazyCollection;
use NumberFormatter;
use PKP\category\Category;
use PKP\context\Context;
use PKP\db\DAORegistry;
use PKP\facades\Locale;
use PKP\submission\GenreDAO;
use PKP\view\HomepageBlock;

class HomepageBlocksRegistry extends \PKP\view\HomepageBlocksRegistry
{
    protected function registerDefaultBlocks(): void
    {
        parent::registerDefaultBlocks();

        $this->register(
            new HomepageBlock(
                component: 'homepage-blocks.issue-summary',
                title: __('manager.homepageBlocks.issueSummary'),
                forSite: false,
            )
        );
        $this->register(
            new HomepageBlock(
                component: 'homepage-blocks.latest-articles',
                title: __('submissions.published.latest'),
                loader: function (?Context $context) {
                    $collector = Repo::submission()
                        ->getCollector()
                        ->filterByLatestPublished(true)
                        ->limit(9);
                    if ($context) {
                        $collector->filterByContextIds([$context->getId()]);
                    } else {
                        $collector->filterByContextIds([Application::SITE_CONTEXT_ID_ALL]);
                    }
                    $latestPublications = $collector->getMany();

                    $genreDao = DAORegistry::getDAO('GenreDAO'); /** @var GenreDAO $genreDao */
                    $templateMgr = TemplateManager::getManager(Application::get()->getRequest());
                    $templateMgr->assign([
                        'latestPublications' => $latestPublications,
                        'latestPublicationsTitle' => __('submissions.published.latest'),
                        'latestPublicationsDescription' => $context
                            ? __('submissions.published.latest.description', [
                                'url' => Application::get()->getRequest()->url(null, 'issue', 'archive'),
                            ])
                            : __('submissions.published.latest.description.site', [
                                'url' => Application::get()->getRequest()->url(null, 'search'),
                            ]),
                        'primaryFileGenreIds' => $genreDao->getIdsBy(
                            contextIds: $context ? [$context->getId()] : null,
                            supplementary: false,
                            dependent: false,
                        )->toArray(),
                        'supplementaryFileGenreIds' => $genreDao->getIdsBy(
                            contextIds: $context ? [$context->getId()] : null,
                            supplementary: true,
                        )->toArray(),
                        'sections' => $context
                            ? Repo::section()
                                ->getCollector()
                                ->filterByContextIds([$context->getId()])
                                ->getMany()
                            : [],
                    ]);
                }
            )
        );
        $this->register(
            new HomepageBlock(
                component: 'homepage-blocks.latest-articles-by-category',
                title: __('submissions.published.latestByCategory'),
                forSite: false,
                loader: function (Context $context) {
                    $latestPublicationsByCategory = $this->getCategories($context)
                        ->map(function (Category $category) use ($context) {
                            return [
                                'category' => $category,
                                'submissions' => Repo::submission()
                                    ->getCollector()
                                    ->filterByCategoryIds([$category->getId()])
                                    ->filterByStatus([Submission::STATUS_PUBLISHED])
                                    ->filterByContextIds([$context->getId()])
                                    ->orderBy(Collector::ORDERBY_DATE_PUBLISHED)
                                    ->limit(3)
                                    ->getMany(),
                            ];
                        });
                    $templateMgr = TemplateManager::getManager(Application::get()->getRequest());
                    $templateMgr->assign([
                        'latestPublicationsByCategory' => $latestPublicationsByCategory,
                        'latestPublicationsByCategoryTitle' => __('submissions.published.latest'),
                        'latestPublicationsByCategoryDescription' => __('submissions.published.latest.description', [
                            'url' => Application::get()->getRequest()->url(null, 'issue', 'archive'),
                        ]),
                    ]);
                }
            )
        );
        $this->register(
            new HomepageBlock(
                component: 'homepage-blocks.search',
                title: __('common.search'),
                loader: function (?Context $context) {
                    $count = Repo::submission()
                        ->getCollector()
                        ->filterByStatus([Submission::STATUS_PUBLISHED])
                        ->filterByContextIds([$context ? $context->getId() : '*'])
                        ->getCount();

                    // Round to nearest round number based on how
                    // large the count is. For use in saying,
                    // for example, more than 1,000 articles
                    if ($count < 10) {
                        $count = $count - 1;
                    } else {
                        $power = floor(log10($count));
                        $nearest = pow(10, $power);
                        $count = floor($count / $nearest) * $nearest;
                    }

                    $numberFormatter = new NumberFormatter(Locale::getLocale(), NumberFormatter::DECIMAL);
                    $formattedCount = $numberFormatter->format($count);

                    $templateMgr = TemplateManager::getManager(Application::get()->getRequest());
                    $templateMgr->assign([
                        'searchBlockTitle' => __('submissions.search.findArticle'),
                        'searchBlockDescription' => __('submissions.search.searchArchiveCount', ['count' => $formattedCount]),
                        'searchBlockCountAllPublications' => $formattedCount,
                        'searchBlockIntegerAllPublications' => $count,
                    ]);
                }
            )
        );
        $this->register(
            new HomepageBlock(
                component: 'homepage-blocks.categories',
                title: __('submissions.browseByCategory'),
                forSite: false,
                loader: function (Context $context) {
                    $categories = $this->getCategories($context);
                    $templateMgr = TemplateManager::getManager(Application::get()->getRequest());
                    $templateMgr->assign([
                        'categories' => $categories,
                        'maxCategoriesAsBlocks' => 9,
                        'browseByCategoryTitle' => __('submissions.browseByCategory'),
                        'browseByCategoryDescription' => __('submissions.browseByCategory.description', [
                            'url' => Application::get()->getRequest()->url(null, 'issue', 'archive'),
                        ]),
                    ]);
                }
            )
        );
        $this->register(
            new HomepageBlock(
                component: 'homepage-blocks.recent-issues',
                title: __('issues.recentIssues'),
                loader: function (?Context $context) {
                    $issues = Repo::issue()
                        ->getCollector()
                        ->filterByPublished(true)
                        ->filterByContextIds([$context?->getId() ?? '*'])
                        ->orderBy(IssueCollector::ORDERBY_DATE_PUBLISHED)
                        ->limit(10)
                        ->getMany();
                    $templateMgr = TemplateManager::getManager(Application::get()->getRequest());
                    $templateMgr->assign([
                        'recentIssues' => $issues->toArray(),
                    ]);
                }
            )
        );
    }

    /**
     * @return LazyCollection<Category>
     */
    protected function getCategories(Context $context): LazyCollection
    {
        static $categories = null;

        if (is_null($categories)) {
            $categories = Repo::category()
                ->getCollector()
                ->filterByContextIds([$context->getId()])
                ->filterByParentIds([null])
                ->getMany();
        }

        return $categories;
    }
}
