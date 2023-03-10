<?php

/**
 * @file plugins/generic/webFeed/WebFeedBlockPlugin.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class WebFeedBlockPlugin
 * @brief Class for block component of web feed plugin
 */

namespace APP\plugins\generic\webFeed;

use APP\facades\Repo;

class WebFeedBlockPlugin extends \PKP\plugins\BlockPlugin
{
    /**
     * Constructor
     */
    public function __construct(protected WebFeedPlugin $parentPlugin)
    {
        parent::__construct();
    }

    /**
     * Get the name of this plugin. The name must be unique within its category.
     */
    public function getName(): string
    {
        return substr(static::class, strlen(__NAMESPACE__) + 1);
    }

    /**
     * Hide this plugin from the management interface (it's subsidiary)
     */
    public function getHideManagement(): bool
    {
        return true;
    }

    /**
     * Get the display name of this plugin.
     *
     */
    public function getDisplayName(): string
    {
        return __('plugins.generic.webfeed.displayName');
    }

    /**
     * Get a description of the plugin.
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
     * @copydoc PKPPlugin::getTemplatePath
     */
    public function getTemplatePath($inCore = false): string
    {
        return $this->parentPlugin->getTemplatePath($inCore) . '/templates';
    }

    /**
     * Get the HTML contents for this block.
     *
     * @param object $templateMgr
     * @param PKPRequest $request
     */
    public function getContents($templateMgr, $request = null): string
    {
        $journal = $request->getJournal();
        if (Repo::issue()->getCurrent($journal->getId(), true)) {
            return parent::getContents($templateMgr, $request);
        }
        return '';
    }
}
