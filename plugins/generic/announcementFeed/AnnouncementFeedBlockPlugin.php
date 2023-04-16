<?php

/**
 * @file plugins/generic/announcementFeed/AnnouncementFeedBlockPlugin.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementFeedBlockPlugin
 *
 * @brief Class for block component of announcement feed plugin
 */

namespace APP\plugins\generic\announcementFeed;

use PKP\plugins\BlockPlugin;

class AnnouncementFeedBlockPlugin extends BlockPlugin
{
    protected $_parentPlugin;

    /**
     * Constructor
     *
     * @param AnnouncementFeedPlugin $parentPlugin
     */
    public function __construct($parentPlugin)
    {
        $this->_parentPlugin = $parentPlugin;
        parent::__construct();
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
        return 'AnnouncementFeedBlockPlugin';
    }

    /**
     * Get the display name of this plugin.
     *
     * @return string
     */
    public function getDisplayName()
    {
        return __('plugins.generic.announcementfeed.displayName');
    }

    /**
     * Get a description of the plugin.
     */
    public function getDescription()
    {
        return __('plugins.generic.announcementfeed.description');
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
     * @see BlockPlugin::getContents
     *
     * @param null|mixed $request
     */
    public function getContents($templateMgr, $request = null)
    {
        $journal = $request->getJournal();
        if (!$journal) {
            return '';
        }

        if (!$journal->getData('enableAnnouncements')) {
            return '';
        }

        $displayPage = $this->_parentPlugin->getSetting($journal->getId(), 'displayPage');
        $requestedPage = $request->getRequestedPage();

        if (($displayPage == 'all') || ($displayPage == 'homepage' && (empty($requestedPage) || $requestedPage == 'index' || $requestedPage == 'announcement')) || ($displayPage == $requestedPage)) {
            return parent::getContents($templateMgr, $request);
        } else {
            return '';
        }
    }
}
