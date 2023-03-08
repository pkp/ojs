<?php

/**
 * @file plugins/generic/webFeed/WebFeedGatewayPlugin.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class WebFeedGatewayPlugin
 * @brief Gateway component of web feed plugin
 *
 */

namespace APP\plugins\generic\webFeed;

use APP\facades\Repo;
use APP\submission\Collector;
use APP\template\TemplateManager;
use Exception;
use PKP\db\DAORegistry;
use PKP\plugins\GatewayPlugin;
use PKP\submission\PKPSubmission;

class WebFeedGatewayPlugin extends GatewayPlugin
{
    public const DEFAULT_RECENT_ITEMS = 30;

    /**
     * Constructor
     */
    public function __construct(protected WebFeedPlugin $parentPlugin)
    {
        parent::__construct();
    }

    /**
     * Hide this plugin from the management interface (it's subsidiary)
     */
    public function getHideManagement(): bool
    {
        return true;
    }

    /**
     * Get the name of this plugin. The name must be unique within its category.
     */
    public function getName(): string
    {
        return substr(static::class, strlen(__NAMESPACE__) + 1);
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName(): string
    {
        return __('plugins.generic.webfeed.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription(): string
    {
        return __('plugins.generic.webfeed.description');
    }

    /**
     * Override the builtin to get the correct plugin path.
     */
    public function getPluginPath(): string
    {
        return $this->parentPlugin->getPluginPath();
    }

    /**
     * Get whether or not this plugin is enabled. (Should always return true, as the
     * parent plugin will take care of loading this one when needed)
     *
     * @param int $contextId Context ID (optional)
     */
    public function getEnabled($contextId = null): bool
    {
        return $this->parentPlugin->getEnabled($contextId);
    }

    /**
     * Handle fetch requests for this plugin.
     *
     * @param array $args Arguments.
     * @param Request $request Request object.
     */
    public function fetch($args, $request)
    {
        $journal = $request->getJournal();
        if (!$journal) {
            return false;
        }

        // Make sure there's a current issue for this journal
        $issue = Repo::issue()->getCurrent($journal->getId(), true);
        if (!$issue) {
            return false;
        }

        if (!$this->parentPlugin->getEnabled($journal->getId())) {
            return false;
        }

        // Make sure the feed type is specified and valid
        $type = array_shift($args);
        $templateConfig = match ($type) {
            'rss' => ['template' => 'rss.tpl', 'mimeType' => 'application/rdf+xml'],
            'rss2' => ['template' => 'rss2.tpl', 'mimeType' => 'application/rss+xml'],
            'atom' => ['template' => 'atom.tpl', 'mimeType' => 'application/atom+xml'],
            default => throw new Exception('Invalid feed format')
        };

        // Get limit setting from web feeds plugin
        $displayItems = $this->parentPlugin->getSetting($journal->getId(), 'displayItems');
        $recentItems = (int) $this->parentPlugin->getSetting($journal->getId(), 'recentItems');
        if ($recentItems < 1) {
            $recentItems = self::DEFAULT_RECENT_ITEMS;
        }

        $submissions = [];
        $sections = [];
        $latestDate = null;
        if ($displayItems == 'recent' && $recentItems > 0) {
            /** @var iterable<PKPSubmission> */
            $submissionsIterator = Repo::submission()->getCollector()
                ->filterByContextIds([$journal->getId()])
                ->filterByStatus([PKPSubmission::STATUS_PUBLISHED])
                ->limit($recentItems)
                ->orderBy(Collector::ORDERBY_DATE_PUBLISHED)
                ->getMany();
            foreach ($submissionsIterator as $submission) {
                $latestDate ??= $submission->getData('lastModified');
                $identifiers = [];
                $section = ($sectionId = $submission->getSectionId())
                    ? $sections[$sectionId] ??= Repo::section()->get($sectionId)
                    : null;
                if ($section) {
                    $identifiers[] = ['type' => 'section', 'value' => $section->getLocalizedTitle()];
                }

                $publication = $submission->getCurrentPublication();
                $categoriesIterator = Repo::category()->getCollector()
                    ->filterByPublicationIds([$publication->getId()])
                    ->getMany();
                /** @var Category */
                foreach ($categoriesIterator as $category) {
                    $identifiers[] = ['type' => 'category', 'value' => $category->getLocalizedTitle()];
                }

                foreach (['keywords', 'subjects', 'disciplines'] as $type) {
                    $values = $publication->getLocalizedData($type) ?? [];
                    foreach ($values as $value) {
                        $identifiers[] = ['type' => $type, 'value' => $value];
                    }
                }

                $submissions[] = [
                    'submission' => $submission,
                    'identifiers' => $identifiers
                ];
            }
        } else {
            $submissions = Repo::submission()->getInSections($issue->getId(), $journal->getId());
        }

        /** @var VersionDAO */
        $versionDao = DAORegistry::getDAO('VersionDAO');
        $version = $versionDao->getCurrentVersion();

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'systemVersion' => $version->getVersionString(),
            'submissions' => $submissions,
            'journal' => $journal,
            'issue' => $issue,
            'latestDate' => $latestDate,
            'feedUrl' => $request->getRequestUrl()
        ]);
        $templateMgr->display($this->parentPlugin->getTemplateResource($templateConfig['template']), $templateConfig['mimeType']);
        return true;
    }
}
