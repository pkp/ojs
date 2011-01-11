<?php

/**
 * @file classes/notification/Notification.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OJSNotification
 * @ingroup notification
 * @see NotificationDAO
 * @brief OJS subclass for Notifications (defines OJS-specific types and icons).
 */

// $Id$


/** Notification associative types. */
define('NOTIFICATION_TYPE_ARTICLE_SUBMITTED', 		0x1000001);
define('NOTIFICATION_TYPE_METADATA_MODIFIED', 		0x1000002);
define('NOTIFICATION_TYPE_SUPP_FILE_MODIFIED', 		0x1000004);
define('NOTIFICATION_TYPE_GALLEY_MODIFIED', 		0x1000006);
define('NOTIFICATION_TYPE_SUBMISSION_COMMENT', 		0x1000007);
define('NOTIFICATION_TYPE_LAYOUT_COMMENT', 		0x1000008);
define('NOTIFICATION_TYPE_COPYEDIT_COMMENT', 		0x1000009);
define('NOTIFICATION_TYPE_PROOFREAD_COMMENT', 		0x1000010);
define('NOTIFICATION_TYPE_REVIEWER_COMMENT', 		0x1000011);
define('NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT', 	0x1000012);
define('NOTIFICATION_TYPE_EDITOR_DECISION_COMMENT', 	0x1000013);
define('NOTIFICATION_TYPE_USER_COMMENT', 		0x10000014);
define('NOTIFICATION_TYPE_PUBLISHED_ISSUE', 		0x10000015);
define('NOTIFICATION_TYPE_NEW_ANNOUNCEMENT', 		0x10000016);

import('lib.pkp.classes.notification.PKPNotification');
import('lib.pkp.classes.notification.NotificationDAO');

class Notification extends PKPNotification {

	/**
	 * Constructor.
	 */
	function Notification() {
		parent::PKPNotification();
	}

	/**
	 * return the path to the icon for this type
	 * @return string
	 */
	function getIconLocation() {
		$baseUrl = Request::getBaseUrl() . '/lib/pkp/templates/images/icons/';
		switch ($this->getAssocType()) {
			case NOTIFICATION_TYPE_ARTICLE_SUBMITTED:
				return $baseUrl . 'page_new.gif';
				break;
			case NOTIFICATION_TYPE_SUPP_FILE_MODIFIED:
				return $baseUrl . 'page_attachment.gif';
				break;

			case NOTIFICATION_TYPE_METADATA_MODIFIED:
			case NOTIFICATION_TYPE_GALLEY_MODIFIED:
				return $baseUrl . 'edit.gif';
				break;
			case NOTIFICATION_TYPE_SUBMISSION_COMMENT:
			case NOTIFICATION_TYPE_LAYOUT_COMMENT:
			case NOTIFICATION_TYPE_COPYEDIT_COMMENT:
			case NOTIFICATION_TYPE_PROOFREAD_COMMENT:
			case NOTIFICATION_TYPE_REVIEWER_COMMENT:
			case NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT:
			case NOTIFICATION_TYPE_EDITOR_DECISION_COMMENT:
			case NOTIFICATION_TYPE_USER_COMMENT:
				return $baseUrl . 'comment_new.gif';
				break;
			case NOTIFICATION_TYPE_PUBLISHED_ISSUE:
				return $baseUrl . 'list_world.gif';
				break;
			case NOTIFICATION_TYPE_NEW_ANNOUNCEMENT:
				return $baseUrl . 'note_new.gif';
				break;
			default:
				return $baseUrl . 'page_alert.gif';
		}
	}

	/**
	 * Static function to send an email to a mailing list user regarding signup or a lost password
	 * @param $email string
	 * @param $password string the user's password
	 * @param $template string The mail template to use
	 */
	function sendMailingListEmail($email, $password, $template) {
		import('classes.mail.MailTemplate');
		$journal = Request::getJournal();
		$site = Request::getSite();

		$params = array(
			'password' => $password,
			'siteTitle' => $journal->getLocalizedTitle(),
			'unsubscribeLink' => Request::url(null, 'notification', 'unsubscribeMailList')
		);

		if ($template == 'NOTIFICATION_MAILLIST_WELCOME') {
			$keyHash = md5($password);
			$confirmLink = Request::url(null, 'notification', 'confirmMailListSubscription', array($keyHash, $email));
			$params["confirmLink"] = $confirmLink;
		}

		$mail = new MailTemplate($template);
		$mail->setFrom($site->getLocalizedContactEmail(), $site->getLocalizedContactName());
		$mail->assignParams($params);
		$mail->addRecipient($email);
		$mail->send();
	}

	/**
	 * Returns an array of information on the journal's subscription settings
	 * @return array
	 */
	function getSubscriptionSettings() {
		$journal = Request::getJournal();
		if (!$journal) return array();

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager =& OJSPaymentManager::getManager();

		$settings = array('subscriptionsEnabled' => $paymentManager->acceptSubscriptionPayments(),
				'allowRegReviewer' => $journal->getSetting('allowRegReviewer'),
				'allowRegAuthor' => $journal->getSetting('allowRegAuthor'));

		return $settings;
	}
}

?>
