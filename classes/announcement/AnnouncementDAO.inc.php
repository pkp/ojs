<?php

/**
 * @file classes/announcement/AnnouncementDAO.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementDAO
 * @ingroup announcement
 * @see Announcement
 *
 * @brief Operations for retrieving and modifying Announcement objects.
 */


import('classes.announcement.Announcement');
import('lib.pkp.classes.announcement.PKPAnnouncementDAO');

class AnnouncementDAO extends PKPAnnouncementDAO {
	/**
	 * Constructor
	 */
	function AnnouncementDAO() {
		parent::PKPAnnouncementDAO();
	}

	/**
	 * @see PKPAnnouncementDAO::newDataObject
	 */
	function newDataObject() {
		return new Announcement();
	}
}

?>
