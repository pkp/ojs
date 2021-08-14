<?php

/**
 * @file plugins/generic/webFeed/WebFeedGatewayPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class WebFeedGatewayPlugin
 * @ingroup plugins_generic_webFeed
 *
 * @brief Gateway component of web feed plugin
 *
 */

use APP\facades\Repo;
use APP\template\TemplateManager;
use PKP\submission\PKPSubmission;

class WebFeedGatewayPlugin extends \PKP\plugins\GatewayPlugin
{
    /** @var WebFeedPlugin Parent plugin */
    protected $_parentPlugin;

    /**
     * @param WebFeedPlugin $parentPlugin
     */
    public function __construct($parentPlugin)
    {
        parent::__construct();
        $this->_parentPlugin = $parentPlugin;
    }

    /**
     * Hide this plugin from the management interface (it's subsidiary)
     */
    public function getHideManagement()
    {
        return true;
    }

    /**
     * Get the name of this plugin. The name must be unique within
     * its category.
     *
     * @return string name of plugin
     */
    public function getName()
    {
        return 'WebFeedGatewayPlugin';
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.generic.webfeed.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.generic.webfeed.description');
    }

    /**
     * Override the builtin to get the correct plugin path.
     *
     * @return string
     */
    public function getPluginPath()
    {
        return $this->_parentPlugin->getPluginPath();
    }

    /**
     * Get whether or not this plugin is enabled. (Should always return true, as the
     * parent plugin will take care of loading this one when needed)
     *
     * @param int $contextId Context ID (optional)
     *
     * @return bool
     */
    public function getEnabled($contextId = null)
    {
        return $this->_parentPlugin->getEnabled($contextId);
    }

    /**
     * Handle fetch requests for this plugin.
     *
     * @param array $args Arguments.
     * @param PKPRequest $request Request object.
     */
    public function fetch($args, $request)
    {
        // Make sure we're within a Journal context
        $request = Application::get()->getRequest();
        $journal = $request->getJournal();
        if (!$journal) {
            return false;
        }

        // Make sure there's a current issue for this journal
        $issue = Repo::issue()->getCurrent($journal->getId(), true);
        if (!$issue) {
            return false;
        }

        if (!$this->_parentPlugin->getEnabled($journal->getId())) {
            return false;
        }

        // Make sure the feed type is specified and valid
        $type = array_shift($args);
        $typeMap = [
            'rss' => 'rss.tpl',
            'rss2' => 'rss2.tpl',
            'atom' => 'atom.tpl'
        ];
        $mimeTypeMap = [
            'rss' => 'application/rdf+xml',
            'rss2' => 'application/rss+xml',
            'atom' => 'application/atom+xml'
        ];
        if (!isset($typeMap[$type])) {
            return false;
        }

        // Get limit setting from web feeds plugin
        $displayItems = $this->_parentPlugin->getSetting($journal->getId(), 'displayItems');
        $recentItems = (int) $this->_parentPlugin->getSetting($journal->getId(), 'recentItems');

        if ($displayItems == 'recent' && $recentItems > 0) {
            $submissionsIterator = Repo::submission()->getMany(['contextId' => $journal->getId(), 'status' => PKPSubmission::STATUS_PUBLISHED, 'count' => $recentItems]);
            $submissionsInSections = [];
            foreach ($submissionsIterator as $submission) {
                $submissionsInSections[]['articles'][] = $submission;
            }
        } else {
            $submissionsInSections = Repo::submission()->getInSections($issue->getId(), $journal->getId());
        }

        $versionDao = DAORegistry::getDAO('VersionDAO'); /** @var VersionDAO $versionDao */
        $version = $versionDao->getCurrentVersion();

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'ojsVersion' => $version->getVersionString(),
            'publishedSubmissions' => $submissionsInSections,
            'journal' => $journal,
            'issue' => $issue,
            'showToc' => true,
        ]);

        $templateMgr->display($this->_parentPlugin->getTemplateResource($typeMap[$type]), $mimeTypeMap[$type]);
        return true;
    }
}
