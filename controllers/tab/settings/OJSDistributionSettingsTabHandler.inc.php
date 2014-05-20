<?php

/**
 * @file controllers/tab/settings/OJSDistributionSettingsTabHandler.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OJSDistributionSettingsTabHandler
 * @ingroup controllers_tab_settings
 *
 * @brief Handle AJAX operations for tabs on Distribution Process page.
 */

// Import the base Handler.
import('lib.pkp.controllers.tab.settings.DistributionSettingsTabHandler');

class OJSDistributionSettingsTabHandler extends DistributionSettingsTabHandler {
	/**
	 * Constructor
	 */
	function OJSDistributionSettingsTabHandler() {
		parent::DistributionSettingsTabHandler();
		$this->setPageTabs(array_merge(
			$this->getPageTabs(),
			array(
				'access' => 'controllers.tab.settings.access.form.AccessForm',
				'identifiers' => 'controllers.tab.settings.identifiers.form.IdentifiersForm',
			)
		));
	}
}

?>
