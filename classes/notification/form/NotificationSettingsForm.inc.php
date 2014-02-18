<?php

/**
 * @file classes/notification/form/NotificationSettingsForm.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NotificationSettingsForm
 * @ingroup notification
 *
 * @brief Form to edit notification settings.
 */

import('lib.pkp.classes.notification.form.PKPNotificationSettingsForm');

class NotificationSettingsForm extends PKPNotificationSettingsForm {
	/**
	 * Constructor.
	 */
	function NotificationSettingsForm() {
		parent::PKPNotificationSettingsForm();
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$userVars = array();
		foreach($this->_getNotificationSettingsMap() as $notificationSetting) {
			$userVars[] = $notificationSetting['settingName'];
			$userVars[] = $notificationSetting['emailSettingName'];
		}

		$this->readUserVars($userVars);
	}

	/**
	 * Get all notification settings form names and their setting type values
	 * @return array
	 */
	function _getNotificationSettingsMap() {
		return array(
			NOTIFICATION_TYPE_SUBMISSION_SUBMITTED => array('settingName' => 'notificationArticleSubmitted',
				'emailSettingName' => 'emailNotificationArticleSubmitted',
				'settingKey' => 'notification.type.articleSubmitted'),
			NOTIFICATION_TYPE_METADATA_MODIFIED => array('settingName' => 'notificationMetadataModified',
				'emailSettingName' => 'emailNotificationMetadataModified',
				'settingKey' => 'notification.type.metadataModified'),
			NOTIFICATION_TYPE_PUBLISHED_ISSUE => array('settingName' => 'notificationPublishedIssue',
				'emailSettingName' => 'emailNotificationPublishedIssue',
				'settingKey' => 'notification.type.issuePublished'),
			NOTIFICATION_TYPE_NEW_ANNOUNCEMENT => array('settingName' => 'notificationNewAnnouncement',
				'emailSettingName' => 'emailNotificationNewAnnouncement',
				'settingKey' => 'notification.type.newAnnouncement'),
		);
	}

	/**
	 * Get a list of notification category names (to display as headers)
	 *  and the notification types under each category
	 * @return array
	 */
	function _getNotificationSettingCategories() {
		return array(
			'submissions' => array('categoryKey' => 'notification.type.submissions',
				'settings' => array(NOTIFICATION_TYPE_SUBMISSION_SUBMITTED, NOTIFICATION_TYPE_METADATA_MODIFIED)),
			'site' => array('categoryKey' => 'notification.type.site',
				'settings' => array(NOTIFICATION_TYPE_PUBLISHED_ISSUE, NOTIFICATION_TYPE_NEW_ANNOUNCEMENT)),
		);
	}

	/**
	 * Display the form.
	 * @param $request Request
	 */
	function display($request) {
		$canOnlyRead = true;
		$canOnlyReview = false;

		if (Validation::isReviewer()) {
			$canOnlyRead = false;
			$canOnlyReview = true;
		}
		if (Validation::isSiteAdmin() || Validation::isJournalManager() || Validation::isEditor() || Validation::isSectionEditor()) {
			$canOnlyRead = false;
			$canOnlyReview = false;
		}

		$templateMgr = TemplateManager::getManager();

		// Remove the notification setting categories that the user will not be receiving (to simplify the form)
		$notificationSettingCategories = $this->_getNotificationSettingCategories();
		if($canOnlyRead || $canOnlyReview) {
			unset($notificationSettingCategories['submissions']);
		}
		if($canOnlyRead) {
			unset($notificationSettingCategories['reviewing']);
		}

		$templateMgr->assign('notificationSettingCategories', $notificationSettingCategories);
		$templateMgr->assign('notificationSettings',  $this->_getNotificationSettingsMap());

		$templateMgr->assign('titleVar', __('common.title'));
		return parent::display($request);
	}

	/**
	 * Save site settings.
	 * @param $request Request
	 */
	function execute($request) {
		$user = $request->getUser();
		$userId = $user->getId();
		$journal = $request->getJournal();

		$blockedNotifications = array();
		$emailSettings = array();
		foreach($this->_getNotificationSettingsMap() as $settingId => $notificationSetting) {
			// Get notifications that the user wants blocked
			if(!$this->getData($notificationSetting['settingName'])) $blockedNotifications[] = $settingId;
			// Get notifications that the user wants to be notified of by email
			if($this->getData($notificationSetting['emailSettingName'])) $emailSettings[] = $settingId;
		}

		$notificationSubscriptionSettingsDao = DAORegistry::getDAO('NotificationSubscriptionSettingsDAO');
		$notificationSubscriptionSettingsDao->updateNotificationSubscriptionSettings('blocked_notification', $blockedNotifications, $userId, $journal->getId());
		$notificationSubscriptionSettingsDao->updateNotificationSubscriptionSettings('emailed_notification', $emailSettings, $userId, $journal->getId());

		return true;
	}


}

?>
