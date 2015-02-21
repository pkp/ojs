<?php

/**
 * @file classes/announcement/AnnouncementTypeDAO.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementTypeDAO
 * @ingroup announcement
 * @see AnnouncementType
 *
 * @brief Operations for retrieving and modifying AnnouncementType objects.
 */

import('classes.announcement.AnnouncementType');
import('lib.pkp.classes.announcement.PKPAnnouncementTypeDAO');

class AnnouncementTypeDAO extends PKPAnnouncementTypeDAO {
	/**
	 * Constructor
	 */
	function AnnouncementTypeDAO() {
		parent::PKPAnnouncementTypeDAO();
	}

	/**
	 * @see PKPAnnouncementTypeDAO::newDataObject
	 */
	function newDataObject() {
		return new AnnouncementType();
	}
}

?>
