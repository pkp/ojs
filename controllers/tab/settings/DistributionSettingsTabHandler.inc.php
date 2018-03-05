<?php

/**
 * @file controllers/tab/settings/DistributionSettingsTabHandler.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
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
	function __construct() {
		parent::__construct();
		$this->setPageTabs(array_merge(
			$this->getPageTabs(),
			array(
				'access' => 'controllers.tab.settings.access.form.AccessForm',
				'permissions' => 'controllers.tab.settings.permissions.form.OJSPermissionSettingsForm',
			)
		));
	}

	/**
	 * @copydoc SettingsTabHandler::saveFormData()
	 */
	function saveFormData($args, $request) {
		$response = parent::saveFormData($args, $request);
		if (in_array($this->getCurrentTab(), array('access', 'paymentMethod')) && $response->getStatus()) {
			// Cause the sidebar menu to be reloaded to display/hide the Subscriptions item
			$dispatcher = $request->getDispatcher();
			return $request->redirectUrlJson($dispatcher->url($request, ROUTE_PAGE, null, 'management', 'settings', 'distribution', array('_' => uniqid()), $this->getCurrentTab()));
		}
		return $response;
	}
}

?>
