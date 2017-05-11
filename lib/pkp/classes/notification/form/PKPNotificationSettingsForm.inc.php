<?php
/**
 * @defgroup notification_form Notification Form
 */

/**
 * @file classes/notification/form/NotificationSettingsForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPNotificationSettingsForm
 * @ingroup notification_form
 *
 * @brief Form to edit notification settings.
 */


import('lib.pkp.classes.form.Form');

class PKPNotificationSettingsForm extends Form {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct('user/notificationSettingsForm.tpl');

		// Validation checks for this form
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$userVars = array();
		foreach($this->getNotificationSettingsMap() as $notificationSetting) {
			$userVars[] = $notificationSetting['settingName'];
			$userVars[] = $notificationSetting['emailSettingName'];
		}

		$this->readUserVars($userVars);
	}

	/**
	 * Get all notification settings form names and their setting type values
	 * @return array
	 */
	protected function getNotificationSettingsMap() {
		return array(
			NOTIFICATION_TYPE_SUBMISSION_SUBMITTED => array('settingName' => 'notificationSubmissionSubmitted',
				'emailSettingName' => 'emailNotificationSubmissionSubmitted',
				'settingKey' => 'notification.type.submissionSubmitted'),
			NOTIFICATION_TYPE_METADATA_MODIFIED => array('settingName' => 'notificationMetadataModified',
				'emailSettingName' => 'emailNotificationMetadataModified',
				'settingKey' => 'notification.type.metadataModified'),
			NOTIFICATION_TYPE_REVIEWER_COMMENT => array('settingName' => 'notificationReviewerComment',
				'emailSettingName' => 'emailNotificationReviewerComment',
				'settingKey' => 'notification.type.reviewerComment'),
			NOTIFICATION_TYPE_NEW_QUERY => array('settingName' => 'notificationNewQuery',
				'emailSettingName' => 'emailNotificationNewQuery',
				'settingKey' => 'notification.type.queryAdded'),
			NOTIFICATION_TYPE_QUERY_ACTIVITY => array('settingName' => 'notificationQueryActivity',
				'emailSettingName' => 'emailNotificationQueryActivity',
				'settingKey' => 'notification.type.queryActivity'),
			NOTIFICATION_TYPE_ALL_REVISIONS_IN => array('settingName' => 'notificationAllRevisionsIn',
				'emailSettingName' => 'emailNotificationAllRevisionsIn',
				'settingKey' => 'notification.type.allRevisionsIn'),
		);
	}

	/**
	 * Get a list of notification category names (to display as headers)
	 *  and the notification types under each category
	 * @return array
	 */
	protected function getNotificationSettingCategories() {
		return array(
			array('categoryKey' => 'notification.type.submissions',
				'settings' => array(
					NOTIFICATION_TYPE_SUBMISSION_SUBMITTED,
					NOTIFICATION_TYPE_METADATA_MODIFIED,
					NOTIFICATION_TYPE_NEW_QUERY,
					NOTIFICATION_TYPE_QUERY_ACTIVITY,
				)
			),
			array('categoryKey' => 'notification.type.reviewing',
				'settings' => array(
					NOTIFICATION_TYPE_REVIEWER_COMMENT,
					NOTIFICATION_TYPE_ALL_REVISIONS_IN,
				)
			),
		);
	}

	/**
	 * @copydoc
	 */
	function fetch($request) {
		$context = $request->getContext();
		$userId = $request->getUser()->getId();
		$notificationSubscriptionSettingsDao = DAORegistry::getDAO('NotificationSubscriptionSettingsDAO');
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'blockedNotifications' => $notificationSubscriptionSettingsDao->getNotificationSubscriptionSettings('blocked_notification', $userId, $context->getId()),
			'emailSettings' => $notificationSubscriptionSettingsDao->getNotificationSubscriptionSettings('blocked_emailed_notification', $userId, $context->getId()),
			'notificationSettingCategories' => $this->getNotificationSettingCategories(),
			'notificationSettings' => $this->getNotificationSettingsMap(),
		));
		return parent::fetch($request);
	}

	/**
	 * @copydoc
	 */
	function execute($request) {
		$user = $request->getUser();
		$userId = $user->getId();
		$context = $request->getContext();

		$blockedNotifications = array();
		$emailSettings = array();
		foreach($this->getNotificationSettingsMap() as $settingId => $notificationSetting) {
			// Get notifications that the user wants blocked
			if(!$this->getData($notificationSetting['settingName'])) $blockedNotifications[] = $settingId;
			// Get notifications that the user wants to be notified of by email
			if($this->getData($notificationSetting['emailSettingName'])) $emailSettings[] = $settingId;
		}

		$notificationSubscriptionSettingsDao = DAORegistry::getDAO('NotificationSubscriptionSettingsDAO');
		$notificationSubscriptionSettingsDao->updateNotificationSubscriptionSettings('blocked_notification', $blockedNotifications, $userId, $context->getId());
		$notificationSubscriptionSettingsDao->updateNotificationSubscriptionSettings('blocked_emailed_notification', $emailSettings, $userId, $context->getId());

		return true;
	}
}

?>
