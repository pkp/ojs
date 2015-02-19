<?php
/**
 * @defgroup notification_form
 */

/**
 * @file classes/notification/form/NotificationSettingsForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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
			NOTIFICATION_TYPE_ARTICLE_SUBMITTED => array('settingName' => 'notificationArticleSubmitted',
				'emailSettingName' => 'emailNotificationArticleSubmitted',
				'settingKey' => 'notification.type.articleSubmitted'),
			NOTIFICATION_TYPE_METADATA_MODIFIED => array('settingName' => 'notificationMetadataModified',
				'emailSettingName' => 'emailNotificationMetadataModified',
				'settingKey' => 'notification.type.metadataModified'),
			NOTIFICATION_TYPE_SUPP_FILE_MODIFIED => array('settingName' => 'notificationSuppFileModified',
				'emailSettingName' => 'emailNotificationSuppFileModified',
				'settingKey' => 'notification.type.suppFileModified'),
			NOTIFICATION_TYPE_GALLEY_MODIFIED => array('settingName' => 'notificationGalleyModified',
				'emailSettingName' => 'emailNotificationGalleyModified',
				'settingKey' => 'notification.type.galleyModified'),
			NOTIFICATION_TYPE_SUBMISSION_COMMENT => array('settingName' => 'notificationSubmissionComment',
				'emailSettingName' => 'emailNotificationSubmissionComment',
				'settingKey' => 'notification.type.submissionComment'),
			NOTIFICATION_TYPE_LAYOUT_COMMENT => array('settingName' => 'notificationLayoutComment',
				'emailSettingName' => 'emailNotificationLayoutComment',
				'settingKey' => 'notification.type.layoutComment'),
			NOTIFICATION_TYPE_COPYEDIT_COMMENT => array('settingName' => 'notificationCopyeditComment',
				'emailSettingName' => 'emailNotificationCopyeditComment',
				'settingKey' => 'notification.type.copyeditComment'),
			NOTIFICATION_TYPE_PROOFREAD_COMMENT => array('settingName' => 'notificationProofreadComment',
				'emailSettingName' => 'emailNotificationProofreadComment',
				'settingKey' => 'notification.type.proofreadComment'),
			NOTIFICATION_TYPE_REVIEWER_COMMENT => array('settingName' => 'notificationReviewerComment',
				'emailSettingName' => 'emailNotificationReviewerComment',
				'settingKey' => 'notification.type.reviewerComment'),
			NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT => array('settingName' => 'notificationReviewerFormComment',
				'emailSettingName' => 'emailNotificationReviewerFormComment',
				'settingKey' => 'notification.type.reviewerFormComment'),
			NOTIFICATION_TYPE_EDITOR_DECISION_COMMENT => array('settingName' => 'notificationEditorDecisionComment',
				'emailSettingName' => 'emailNotificationEditorDecisionComment',
				'settingKey' => 'notification.type.editorDecisionComment'),
			NOTIFICATION_TYPE_USER_COMMENT => array('settingName' => 'notificationUserComment',
				'emailSettingName' => 'emailNotificationUserComment',
				'settingKey' => 'notification.type.userComment'),
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
				'settings' => array(NOTIFICATION_TYPE_ARTICLE_SUBMITTED, NOTIFICATION_TYPE_METADATA_MODIFIED, NOTIFICATION_TYPE_SUPP_FILE_MODIFIED)),
			'reviewing' => array('categoryKey' => 'notification.type.reviewing',
				'settings' => array(NOTIFICATION_TYPE_REVIEWER_COMMENT, NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT, NOTIFICATION_TYPE_EDITOR_DECISION_COMMENT)),
			'editing' => array('categoryKey' => 'notification.type.editing',
				'settings' => array(NOTIFICATION_TYPE_GALLEY_MODIFIED, NOTIFICATION_TYPE_SUBMISSION_COMMENT, NOTIFICATION_TYPE_LAYOUT_COMMENT, NOTIFICATION_TYPE_COPYEDIT_COMMENT, NOTIFICATION_TYPE_PROOFREAD_COMMENT)),
			'site' => array('categoryKey' => 'notification.type.site',
				'settings' => array(NOTIFICATION_TYPE_USER_COMMENT, NOTIFICATION_TYPE_PUBLISHED_ISSUE, NOTIFICATION_TYPE_NEW_ANNOUNCEMENT)),
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

		$templateMgr =& TemplateManager::getManager();

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
	function execute(&$request) {
		$user = $request->getUser();
		$userId = $user->getId();
		$journal =& $request->getJournal();

		$blockedNotifications = array();
		$emailSettings = array();
		foreach($this->_getNotificationSettingsMap() as $settingId => $notificationSetting) {
			// Get notifications that the user wants blocked
			if(!$this->getData($notificationSetting['settingName'])) $blockedNotifications[] = $settingId;
			// Get notifications that the user wants to be notified of by email
			if($this->getData($notificationSetting['emailSettingName'])) $emailSettings[] = $settingId;
		}

		$notificationSubscriptionSettingsDao =& DAORegistry::getDAO('NotificationSubscriptionSettingsDAO');
		$notificationSubscriptionSettingsDao->updateNotificationSubscriptionSettings('blocked_notification', $blockedNotifications, $userId, $journal->getId());
		$notificationSubscriptionSettingsDao->updateNotificationSubscriptionSettings('emailed_notification', $emailSettings, $userId, $journal->getId());

		return true;
	}


}

?>
