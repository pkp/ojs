<?php

/**
 * @file pages/manager/AnnouncementHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for announcement management functions.
 */

import('lib.pkp.pages.manager.PKPAnnouncementHandler');

class AnnouncementHandler extends PKPAnnouncementHandler {
	/**
	 * Constructor
	 */
	function AnnouncementHandler() {
		parent::PKPAnnouncementHandler();
	}
}

?>
