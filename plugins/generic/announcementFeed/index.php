<?php

/**
 * @defgroup plugins_generic_announcementFeed
 */
 
/**
 * @file plugins/generic/announcementFeed/index.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_announcementFeed
 * @brief Wrapper for Announcement Feed plugin. Based on Web Feed Plugin.
 *
 */

// $Id$


require_once('AnnouncementFeedPlugin.inc.php');

return new AnnouncementFeedPlugin(); 

?> 
