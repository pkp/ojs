<?php

/**
 * @file classes/announcement/AnnouncementType.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementType
 * @ingroup announcement
 * @see AnnouncementTypeDAO, AnnouncementTypeForm
 *
 * @brief Basic class describing an announcement type.
 */

import('lib.pkp.classes.announcement.PKPAnnouncementType');

class AnnouncementType extends PKPAnnouncementType {
	/**
	 * Constructor
	 */
	function AnnouncementType() {
		parent::PKPAnnouncementType();
	}
}

?>
