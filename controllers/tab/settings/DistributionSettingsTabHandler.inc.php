<?php

/**
 * @file controllers/tab/settings/DistributionSettingsTabHandler.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DistributionSettingsTabHandler
 * @ingroup controllers_tab_settings
 *
 * @brief Handle AJAX operations for tabs on Distribution Process page.
 */

// Import the base Handler.
import('lib.pkp.controllers.tab.settings.PKPDistributionSettingsTabHandler');

class DistributionSettingsTabHandler extends PKPDistributionSettingsTabHandler {
	/**
	 * Constructor
	 */
	function DistributionSettingsTabHandler() {
		parent::PKPDistributionSettingsTabHandler();
		$this->setPageTabs(array_merge(
			$this->getPageTabs(),
			array(
				'access' => 'controllers.tab.settings.access.form.AccessForm',
				'permissions' => 'controllers.tab.settings.permissions.form.OJSPermissionSettingsForm',
			)
		));
	}
}

?>
