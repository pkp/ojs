<?php

/**
 * @file classes/notification/NotificationSettingsDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NotificationSettingsDAO
 * @ingroup notification
 * @see Notification
 *
 * @brief Operations for retrieving and modifying Notification metadata.
 */


import('classes.notification.Notification');

class NotificationSettingsDAO extends DAO {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Update a notification's metadata
	 * @param $notificationId int
	 * @return $params array
	 */
	function getNotificationSettings($notificationId) {
		$result = $this->retrieve(
			'SELECT * FROM notification_settings WHERE notification_id = ?',
			(int) $notificationId
		);

		$params = array();
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$name = $row['setting_name'];
			$value = $this->convertFromDB($row['setting_value'], $row['setting_type']);
			$locale = $row['locale'];

			if ($locale == '') $params[$name] = $value;
			else $params[$name][$locale] = $value;
			$result->MoveNext();
		}

		$result->Close();
		return $params;
	}

	/**
	 * Store a notification's metadata
	 * @param $notificationId int
	 * @param $name string
	 * @param $value string
	 * @param $isLocalized boolean optional
	 * @param $type string optional
	 * @param $params array
	 */
	function updateNotificationSetting($notificationId, $name, $value, $isLocalized = false, $type = null) {
		$keyFields = array('setting_name', 'locale', 'notification_id');
		if (!$isLocalized) {
			$value = $this->convertToDB($value, $type);
			$this->replace('notification_settings',
				array(
					'notification_id' => (int) $notificationId,
					'setting_name' => $name,
					'setting_value' => $value,
					'setting_type' => $type,
					'locale' => ''
				),
				$keyFields
			);
		} else {
			if (is_array($value)) foreach ($value as $locale => $localeValue) {
				$this->update('DELETE FROM notification_settings WHERE notification_id = ? AND setting_name = ? AND locale = ?', array($notificationId, $name, $locale));
				if (empty($localeValue)) continue;
				$type = null;
				$this->update('INSERT INTO notification_settings
					(notification_id, setting_name, setting_value, setting_type, locale)
					VALUES (?, ?, ?, ?, ?)',
					array(
						(int) $notificationId,
						$name, $this->convertToDB($localeValue, $type),
						$type,
						$locale
					)
				);
			}
		}
	}

	/**
	 * Delete all settings for a notification
	 * @param $notificationId
	 */
	function deleteSettingsByNotificationId($notificationId) {
		return $this->update('DELETE FROM notification_settings WHERE notification_id = ?', (int) $notificationId);
	}
}

?>
