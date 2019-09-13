<?php

/**
 * @file controllers/modals/publish/PPSPublishHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PPSPublishHandler
 * @ingroup controllers_modals_publish
 *
 * @brief A handler to load final publishing confirmation checks
 */

// Import the base Handler.
import('lib.pkp.controllers.modals.publish.PublishHandler');

class PPSPublishHandler extends PublishHandler {

	/**
	 * Constructor.
	 */
	function __construct() {
		$this->addRoleAssignment(
			[ROLE_ID_AUTHOR],
			['publish']
		);
		parent::__construct();
	}
}

