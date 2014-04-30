<?php

/**
 * @defgroup pages_announcement Announcement Pages
 */
 
/**
 * @file pages/announcement/index.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_announcement
 * @brief Handle requests for public announcement functions. 
 *
 */

switch ($op) {
	case 'index':
		define('HANDLER_CLASS', 'AnnouncementHandler');
		import('lib.pkp.pages.announcement.AnnouncementHandler');
		break;
}

?>
