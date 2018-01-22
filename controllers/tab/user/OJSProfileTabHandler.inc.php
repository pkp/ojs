<?php

/**
 * @file controllers/tab/user/OJSProfileTabHandler.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OJSProfileTabHandler
 * @ingroup controllers_tab_user
 *
 * @brief Handle OJS-specific requests for profile tab operations.
 */


import('lib.pkp.controllers.tab.user.ProfileTabHandler');

class OJSProfileTabHandler extends ProfileTabHandler {

	/**
	 * Display form to edit user's notification settings.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function notifications($args, $request) {
		$this->setupTemplate($request);
		import('classes.user.form.NotificationSettingsForm');
		$notificationsForm = new NotificationSettingsForm($request->getUser());
		$notificationsForm->initData($request);
		return new JSONMessage(true, $notificationsForm->fetch($request));
	}

	/**
	 * Validate and save changes to user's notifications info.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function saveNotifications($args, $request) {
		$this->setupTemplate($request);

		import('classes.user.form.NotificationSettingsForm');
		$notificationsForm = new NotificationSettingsForm($request->getUser());
		$notificationsForm->readInputData();
		if ($notificationsForm->validate()) {
			$notificationsForm->execute($request);
			return new JSONMessage(true);
		}
		return new JSONMessage(false, $notificationsForm->fetch($request));
	}
}

?>
