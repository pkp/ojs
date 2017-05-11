<?php

/**
 * @file classes/notification/Notification.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Notification
 * @ingroup notification
 * @see NotificationDAO
 * @brief Class for Notification.
 */

import('lib.pkp.classes.notification.NotificationDAO');

define('UNSUBSCRIBED_USER_NOTIFICATION',			0);

/** Notification levels.  Determines notification behavior **/
define('NOTIFICATION_LEVEL_TRIVIAL',				0x0000001);
define('NOTIFICATION_LEVEL_NORMAL',				0x0000002);
define('NOTIFICATION_LEVEL_TASK',				0x0000003);

/** Notification types.  Determines what text and URL to display for notification */
define('NOTIFICATION_TYPE_SUCCESS',				0x0000001);
define('NOTIFICATION_TYPE_WARNING',				0x0000002);
define('NOTIFICATION_TYPE_ERROR',				0x0000003);
define('NOTIFICATION_TYPE_FORBIDDEN',				0x0000004);
define('NOTIFICATION_TYPE_INFORMATION',				0x0000005);
define('NOTIFICATION_TYPE_HELP',				0x0000006);
define('NOTIFICATION_TYPE_FORM_ERROR',				0x0000007);
define('NOTIFICATION_TYPE_NEW_ANNOUNCEMENT', 		0x0000008);

define('NOTIFICATION_TYPE_LOCALE_INSTALLED',			0x4000001);

define('NOTIFICATION_TYPE_PLUGIN_ENABLED',			0x5000001);
define('NOTIFICATION_TYPE_PLUGIN_DISABLED',			0x5000002);

define('NOTIFICATION_TYPE_PLUGIN_BASE',				0x6000001);

// Workflow-level notifications
define('NOTIFICATION_TYPE_SUBMISSION_SUBMITTED',		0x1000001);
define('NOTIFICATION_TYPE_METADATA_MODIFIED',			0x1000002);

define('NOTIFICATION_TYPE_REVIEWER_COMMENT',			0x1000003);
define('NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_SUBMISSION',	0x1000004);
define('NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_INTERNAL_REVIEW',	0x1000005);
define('NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EXTERNAL_REVIEW',	0x1000006);
define('NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EDITING',		0x1000007);
define('NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_PRODUCTION',	0x1000008);
// define('NOTIFICATION_TYPE_AUDITOR_REQUEST',			0x1000009); // DEPRECATED; DO NOT USE
define('NOTIFICATION_TYPE_REVIEW_ASSIGNMENT',			0x100000B);
define('NOTIFICATION_TYPE_EDITOR_DECISION_INTERNAL_REVIEW',	0x100000D);
define('NOTIFICATION_TYPE_EDITOR_DECISION_ACCEPT',		0x100000E);
define('NOTIFICATION_TYPE_EDITOR_DECISION_EXTERNAL_REVIEW',	0x100000F);
define('NOTIFICATION_TYPE_EDITOR_DECISION_PENDING_REVISIONS',	0x1000010);
define('NOTIFICATION_TYPE_EDITOR_DECISION_RESUBMIT',		0x1000011);
define('NOTIFICATION_TYPE_EDITOR_DECISION_DECLINE',		0x1000012);
define('NOTIFICATION_TYPE_EDITOR_DECISION_SEND_TO_PRODUCTION',	0x1000013);
define('NOTIFICATION_TYPE_REVIEW_ROUND_STATUS',			0x1000014);
define('NOTIFICATION_TYPE_PENDING_INTERNAL_REVISIONS',		0x1000015);
define('NOTIFICATION_TYPE_PENDING_EXTERNAL_REVISIONS',		0x1000016);
define('NOTIFICATION_TYPE_COPYEDIT_ASSIGNMENT',			0x1000017);
define('NOTIFICATION_TYPE_ALL_REVIEWS_IN',			0x1000018);
define('NOTIFICATION_TYPE_LAYOUT_ASSIGNMENT',			0x1000019);
define('NOTIFICATION_TYPE_INDEX_ASSIGNMENT',			0x100001A);
define('NOTIFICATION_TYPE_APPROVE_SUBMISSION',			0x100001B);
define('NOTIFICATION_TYPE_CONFIGURE_PAYMENT_METHOD',		0x100001C);
define('NOTIFICATION_TYPE_FORMAT_NEEDS_APPROVED_SUBMISSION',	0x100001D);
define('NOTIFICATION_TYPE_VISIT_CATALOG',			0x100001E);
define('NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_REQUIRED',		0x100001F);
define('NOTIFICATION_TYPE_ALL_REVISIONS_IN',			0x1000020);
define('NOTIFICATION_TYPE_NEW_QUERY',				0x1000021);
define('NOTIFICATION_TYPE_QUERY_ACTIVITY',			0x1000022);

define('NOTIFICATION_TYPE_ASSIGN_COPYEDITOR',			0x1000023);
define('NOTIFICATION_TYPE_AWAITING_COPYEDITS',			0x1000024);
define('NOTIFICATION_TYPE_AWAITING_REPRESENTATIONS', 	0x1000025);
define('NOTIFICATION_TYPE_ASSIGN_PRODUCTIONUSER',		0x1000026);


class PKPNotification extends DataObject {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * get user id associated with this notification
	 * @return int
	 */
	function getUserId() {
		return $this->getData('userId');
	}

	/**
	 * set user id associated with this notification
	 * @param $userId int
	 */
	function setUserId($userId) {
		$this->setData('userId', $userId);
	}

	/**
	 * Get the level (NOTIFICATION_LEVEL_...) for this notification
	 * @return int
	 */
	function getLevel() {
		return $this->getData('level');
	}

	/**
	 * Set the level (NOTIFICATION_LEVEL_...) for this notification
	 * @param $level int
	 */
	function setLevel($level) {
		$this->setData('level', $level);
	}

	/**
	 * get date notification was created
	 * @return date (YYYY-MM-DD HH:MM:SS)
	 */
	function getDateCreated() {
		return $this->getData('dateCreated');
	}

	/**
	 * set date notification was created
	 * @param $dateCreated date (YYYY-MM-DD HH:MM:SS)
	 */
	function setDateCreated($dateCreated) {
		$this->setData('dateCreated', $dateCreated);
	}

	/**
	 * get date notification is read by user
	 * @return date (YYYY-MM-DD HH:MM:SS)
	 */
	function getDateRead() {
		return $this->getData('dateRead');
	}

	/**
	 * set date notification is read by user
	 * @param $dateRead date (YYYY-MM-DD HH:MM:SS)
	 */
	function setDateRead($dateRead) {
		$this->setData('dateRead', $dateRead);
	}

	/**
	 * get notification type
	 * @return int
	 */
	function getType() {
		return $this->getData('type');
	}

	/**
	 * set notification type
	 * @param $type int
	 */
	function setType($type) {
		$this->setData('type', $type);
	}

	/**
	 * get notification type
	 * @return int
	 */
	function getAssocType() {
		return $this->getData('assocType');
	}

	/**
	 * set notification type
	 * @param $assocType int
	 */
	function setAssocType($assocType) {
		$this->setData('assocType', $assocType);
	}

	/**
	 * get notification assoc id
	 * @return int
	 */
	function getAssocId() {
		return $this->getData('assocId');
	}

	/**
	 * set notification assoc id
	 * @param $assocId int
	 */
	function setAssocId($assocId) {
		$this->setData('assocId', $assocId);
	}

	/**
	 * get context id
	 * @return int
	 */
	function getContextId() {
		return $this->getData('context_id');
	}

	/**
	 * set context id
	 * @param $context int
	 */
	function setContextId($contextId) {
		$this->setData('context_id', $contextId);
	}
}

?>
