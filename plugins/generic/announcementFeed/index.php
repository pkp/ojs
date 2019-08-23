<?php

/**
 * @defgroup plugins_generic_announcementFeed Announcement Feed Plugin
 */
 
/**
 * @file plugins/generic/announcementFeed/index.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_announcementFeed
 * @brief Wrapper for Announcement Feed plugin. Based on Web Feed Plugin.
 *
 */

require_once('AnnouncementFeedPlugin.inc.php');

return new AnnouncementFeedPlugin(); 
