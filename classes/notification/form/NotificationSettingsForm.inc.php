<?php

/**
 * @file classes/notification/form/NotificationSettingsForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NotificationSettingsForm
 * @ingroup notification_form
 *
 * @brief Form to edit notification settings.
 */


import('lib.pkp.classes.notification.form.PKPNotificationSettingsForm');

class NotificationSettingsForm extends PKPNotificationSettingsForm {

	/**
	 * @copydoc PKPNotificationSettingsForm::getNotificationSettingsMap()
	 */
	protected function getNotificationSettingsMap() {
		$settingsMap = parent::getNotificationSettingsMap();
		$settingsMap[NOTIFICATION_TYPE_PUBLISHED_ISSUE] = array(
			'settingName' => 'notificationPublishedIssue',
			'emailSettingName' => 'emailNotificationPublishedIssue',
			'settingKey' => 'notification.type.issuePublished',
		);
		return $settingsMap;
	}

	/**
	 * @copydoc PKPNotificationSettingsForm::getNotificationSettingsCategories()
	 */
	public function getNotificationSettingCategories() {
		$categories = parent::getNotificationSettingCategories();
		for ($i = 0; $i < count($categories); $i++) {
			if ($categories[$i]['categoryKey'] === 'notification.type.public') {
				$categories[$i]['settings'][] = NOTIFICATION_TYPE_PUBLISHED_ISSUE;
				break;
			}
		}
		return $categories;
	}
}


