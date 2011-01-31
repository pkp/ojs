<?php
/**
 * @defgroup notification_form
 */

/**
 * @file classes/notification/form/NotificationSettingsForm.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NotificationSettingsForm
 * @ingroup notification_form
 *
 * @brief Form to edit notification settings.
 */

// $Id$


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
		$this->readUserVars(
			array('notificationArticleSubmitted',
				'notificationMetadataModified',
				'notificationSuppFileModified',
				'notificationGalleyModified',
				'notificationSubmissionComment',
				'notificationLayoutComment',
				'notificationCopyeditComment',
				'notificationProofreadComment',
				'notificationReviewerComment',
				'notificationReviewerFormComment',
				'notificationEditorDecisionComment',
				'notificationPublishedIssue',
				'notificationUserComment',
				'notificationNewAnnouncement',
				'emailNotificationArticleSubmitted',
				'emailNotificationMetadataModified',
				'emailNotificationSuppFileModified',
				'emailNotificationGalleyModified',
				'emailNotificationSubmissionComment',
				'emailNotificationLayoutComment',
				'emailNotificationCopyeditComment',
				'emailNotificationProofreadComment',
				'emailNotificationReviewerComment',
				'emailNotificationReviewerFormComment',
				'emailNotificationEditorDecisionComment',
				'emailNotificationPublishedIssue',
				'emailNotificationUserComment',
				'emailNotificationNewAnnouncement')
		);
	}

	/**
	 * Display the form.
	 */
	function display() {
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
		$templateMgr->assign('canOnlyRead', $canOnlyRead);
		$templateMgr->assign('canOnlyReview', $canOnlyReview);
		return parent::display();
	}

	/**
	 * Save site settings.
	 */
	function execute() {
		$user = Request::getUser();
		$userId = $user->getId();

		// Notification settings
		$settings = array();
		if(!$this->getData('notificationArticleSubmitted')) $settings[] = NOTIFICATION_TYPE_ARTICLE_SUBMITTED;
		if(!$this->getData('notificationMetadataModified')) $settings[] = NOTIFICATION_TYPE_METADATA_MODIFIED;
		if(!$this->getData('notificationSuppFileModified')) $settings[] = NOTIFICATION_TYPE_SUPP_FILE_MODIFIED;
		if(!$this->getData('notificationGalleyModified')) $settings[] = NOTIFICATION_TYPE_GALLEY_MODIFIED;
		if(!$this->getData('notificationSubmissionComment')) $settings[] = NOTIFICATION_TYPE_SUBMISSION_COMMENT;
		if(!$this->getData('notificationLayoutComment')) $settings[] = NOTIFICATION_TYPE_LAYOUT_COMMENT;
		if(!$this->getData('notificationCopyeditComment')) $settings[] = NOTIFICATION_TYPE_COPYEDIT_COMMENT;
		if(!$this->getData('notificationProofreadComment')) $settings[] = NOTIFICATION_TYPE_PROOFREAD_COMMENT;
		if(!$this->getData('notificationReviewerComment')) $settings[] = NOTIFICATION_TYPE_REVIEWER_COMMENT;
		if(!$this->getData('notificationReviewerFormComment')) $settings[] = NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT;
		if(!$this->getData('notificationEditorDecisionComment')) $settings[] = NOTIFICATION_TYPE_EDITOR_DECISION_COMMENT;
		if(!$this->getData('notificationPublishedIssue')) $settings[] = NOTIFICATION_TYPE_PUBLISHED_ISSUE;
		if(!$this->getData('notificationUserComment')) $settings[] = NOTIFICATION_TYPE_USER_COMMENT;
		if(!$this->getData('notificationNewAnnouncement')) $settings[] = NOTIFICATION_TYPE_NEW_ANNOUNCEMENT;

		// Email settings
		$emailSettings = array();
		if($this->getData('emailNotificationArticleSubmitted')) $emailSettings[] = NOTIFICATION_TYPE_ARTICLE_SUBMITTED;
		if($this->getData('emailNotificationMetadataModified')) $emailSettings[] = NOTIFICATION_TYPE_METADATA_MODIFIED;
		if($this->getData('emailNotificationSuppFileModified')) $emailSettings[] = NOTIFICATION_TYPE_SUPP_FILE_MODIFIED;
		if($this->getData('emailNotificationGalleyModified')) $emailSettings[] = NOTIFICATION_TYPE_GALLEY_MODIFIED;
		if($this->getData('emailNotificationSubmissionComment')) $emailSettings[] = NOTIFICATION_TYPE_SUBMISSION_COMMENT;
		if($this->getData('emailNotificationLayoutComment')) $emailSettings[] = NOTIFICATION_TYPE_LAYOUT_COMMENT;
		if($this->getData('emailNotificationCopyeditComment')) $emailSettings[] = NOTIFICATION_TYPE_COPYEDIT_COMMENT;
		if($this->getData('emailNotificationProofreadComment')) $emailSettings[] = NOTIFICATION_TYPE_PROOFREAD_COMMENT;
		if($this->getData('emailNotificationReviewerComment')) $emailSettings[] = NOTIFICATION_TYPE_REVIEWER_COMMENT;
		if($this->getData('emailNotificationReviewerFormComment')) $emailSettings[] = NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT;
		if($this->getData('emailNotificationEditorDecisionComment')) $emailSettings[] = NOTIFICATION_TYPE_EDITOR_DECISION_COMMENT;
		if($this->getData('emailNotificationPublishedIssue')) $emailSettings[] = NOTIFICATION_TYPE_PUBLISHED_ISSUE;
		if($this->getData('emailNotificationUserComment')) $emailSettings[] = NOTIFICATION_TYPE_USER_COMMENT;
		if($this->getData('emailNotificationNewAnnouncement')) $emailSettings[] = NOTIFICATION_TYPE_NEW_ANNOUNCEMENT;

		$notificationSettingsDao =& DAORegistry::getDAO('NotificationSettingsDAO');
		$notificationSettingsDao->updateNotificationSettings($settings, $userId);
		$notificationSettingsDao->updateNotificationEmailSettings($emailSettings, $userId);

		return true;
	}


}

?>
