<?php

/**
 * ArticleEmailLogEntry.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article.log
 *
 * Article email log entry class.
 * Describes an entry in the article email log.
 *
 * $Id$
 */

// Email associative types. All types must be defined here
define('ARTICLE_EMAIL_TYPE_DEFAULT', 		0);
define('ARTICLE_EMAIL_TYPE_AUTHOR', 		0x01);
define('ARTICLE_EMAIL_TYPE_EDITOR', 		0x02);
define('ARTICLE_EMAIL_TYPE_REVIEW', 		0x03);
define('ARTICLE_EMAIL_TYPE_COPYEDIT', 		0x04);
define('ARTICLE_EMAIL_TYPE_LAYOUT', 		0x05);
define('ARTICLE_EMAIL_TYPE_PROOFREAD', 		0x06);

// General events 				0x10000000

// Author events 				0x20000000

// Editor events 				0x30000000
define('ARTICLE_EMAIL_EDITOR_NOTIFY_AUTHOR', 		0x30000001);

// Reviewer events 				0x40000000
define('ARTICLE_EMAIL_REVIEW_NOTIFY_REVIEWER', 		0x40000001);
define('ARTICLE_EMAIL_REVIEW_THANK_REVIEWER', 		0x40000002);

// Copyeditor events 			0x50000000
define('ARTICLE_EMAIL_COPYEDIT_NOTIFY_COPYEDITOR', 		0x50000001);
define('ARTICLE_EMAIL_COPYEDIT_NOTIFY_AUTHOR', 			0x50000002);
define('ARTICLE_EMAIL_COPYEDIT_NOTIFY_FINAL', 			0x50000003);
define('ARTICLE_EMAIL_COPYEDIT_NOTIFY_COMPLETE', 		0x50000004);
define('ARTICLE_EMAIL_COPYEDIT_NOTIFY_AUTHOR_COMPLETE', 0x50000005);
define('ARTICLE_EMAIL_COPYEDIT_NOTIFY_FINAL_COMPLETE',	0x50000006);
define('ARTICLE_EMAIL_COPYEDIT_NOTIFY_ACKNOWLEDGE', 	0x50000007);
define('ARTICLE_EMAIL_COPYEDIT_NOTIFY_AUTHOR_ACKNOWLEDGE', 	0x50000008);
define('ARTICLE_EMAIL_COPYEDIT_NOTIFY_FINAL_ACKNOWLEDGE', 	0x50000009);

// Proofreader events 			0x60000000

// Layout events 				0x70000000
define('ARTICLE_EMAIL_LAYOUT_NOTIFY_EDITOR', 		0x70000001);
define('ARTICLE_EMAIL_LAYOUT_THANK_EDITOR', 		0x70000002);
define('ARTICLE_EMAIL_LAYOUT_NOTIFY_COMPLETE',		0x70000003);

class ArticleEmailLogEntry extends DataObject {

	/**
	 * Constructor.
	 */
	function ArticleEmailLogEntry() {
		parent::DataObject();
	}
	
	//
	// Get/set methods
	//
	
	/**
	 * Get ID of log entry.
	 * @return int
	 */
	function getLogId() {
		return $this->getData('logId');
	}
	
	/**
	 * Set ID of log entry.
	 * @param $logId int
	 */
	function setLogId($logId) {
		return $this->setData('logId', $logId);
	}
	
	/**
	 * Get ID of article.
	 * @return int
	 */
	function getArticleId() {
		return $this->getData('articleId');
	}
	
	/**
	 * Set ID of article.
	 * @param $articleId int
	 */
	function setArticleId($articleId) {
		return $this->setData('articleId', $articleId);
	}
		
	/**
	 * Get user ID of sender.
	 * @return int
	 */
	function getSenderId() {
		return $this->getData('senderId');
	}
	
	/**
	 * Set user ID of sender.
	 * @param $senderId int
	 */
	function setSenderId($senderId) {
		return $this->setData('senderId', $senderId);
	}
	
	/**
	 * Get date email was sent.
	 * @return datestamp
	 */
	function getDateSent() {
		return $this->getData('dateSent');
	}
	
	/**
	 * Set date email was sent.
	 * @param $dateSent datestamp
	 */
	function setDateSent($dateSent) {
		return $this->setData('dateSent', $dateSent);
	}
	
	/**
	 * Get event type.
	 * @return int
	 */
	function getEventType() {
		return $this->getData('eventType');
	}
	
	/**
	 * Set event type.
	 * @param $eventType int
	 */
	function setEventType($eventType) {
		return $this->setData('eventType', $eventType);
	}
	
	/**
	 * Get associated type.
	 * @return int
	 */
	function getAssocType() {
		return $this->getData('assocType');
	}
	
	/**
	 * Set associated type.
	 * @param $assocType int
	 */
	function setAssocType($assocType) {
		return $this->setData('assocType', $assocType);
	}
	
	/**
	 * Get associated ID.
	 * @return int
	 */
	function getAssocId() {
		return $this->getData('assocId');
	}
	
	/**
	 * Set associated ID.
	 * @param $assocId int
	 */
	function setAssocId($assocId) {
		return $this->setData('assocId', $assocId);
	}
	
	/**
	 * Return the full name of the sender (not necessarily the same as the from address).
	 * @return string
	 */
	function getSenderFullName() {
		static $senderFullName;
		
		if(!isset($senderFullName)) {
			$userDao = &DAORegistry::getDAO('UserDAO');
			$senderFullName = $userDao->getUserFullName($this->getSenderId());
		}
		
		return $senderFullName ? $senderFullName : '';
	}
	
	/**
	 * Return the email address of sender.
	 * @return string
	 */
	function getSenderEmail() {
		static $senderEmail;
		
		if(!isset($senderEmail)) {
			$userDao = &DAORegistry::getDAO('UserDAO');
			$senderEmail = $userDao->getUserEmail($this->getSenderId());
		}
		
		return $senderEmail ? $senderEmail : '';
	}
	
	/**
	 * Return string representation of the associated type.
	 * @return string
	 */
	function getAssocTypeString() {
		switch ($this->getData('assocType')) {
			case ARTICLE_LOG_TYPE_AUTHOR:
				return 'AUT';
			case ARTICLE_LOG_TYPE_EDITOR:
				return 'EDR';
			case ARTICLE_LOG_TYPE_REVIEW:
				return 'REV';
			case ARTICLE_LOG_TYPE_COPYEDIT:
				return 'CPY';
			case ARTICLE_LOG_TYPE_LAYOUT:
				return 'LYT';
			case ARTICLE_LOG_TYPE_PROOFREAD:
				return 'PRF';
			default:
				return 'ART';
		}
	}
	
	/**
	 * Return locale message key for the long format of the associated type.
	 * @return string
	 */
	function getAssocTypeLongString() {
		switch ($this->getData('assocType')) {
			case ARTICLE_LOG_TYPE_AUTHOR:
				return 'submission.logType.author';
			case ARTICLE_LOG_TYPE_EDITOR:
				return 'submission.logType.editor';
			case ARTICLE_LOG_TYPE_REVIEW:
				return 'submission.logType.review';
			case ARTICLE_LOG_TYPE_COPYEDIT:
				return 'submission.logType.copyedit';
			case ARTICLE_LOG_TYPE_LAYOUT:
				return 'submission.logType.layout';
			case ARTICLE_LOG_TYPE_PROOFREAD:
				return 'submission.logType.proofread';
			default:
				return 'submission.logType.article';
		}
	}
	
	
	//
	// Email data
	//
	
	function getFrom() {
		return $this->getData('from');
	}
	
	function setFrom($from) {
		return $this->setData('from', $from);
	}
	
	function getRecipients() {
		return $this->getData('recipients');
	}
	
	function setRecipients($recipients) {
		return $this->setData('recipients', $recipients);
	}
	
	function getCcs() {
		return $this->getData('ccs');
	}
	
	function setCcs($ccs) {
		return $this->setData('ccs', $ccs);
	}
	
	function getBccs() {
		return $this->getData('bccs');
	}
	
	function setBccs($bccs) {
		return $this->setData('bccs', $bccs);
	}
	
	function getSubject() {
		return $this->getData('subject');
	}
	
	function setSubject($subject) {
		return $this->setData('subject', $subject);
	}
	
	function getBody() {
		return $this->getData('body');
	}
	
	function setBody($body) {
		return $this->setData('body', $body);
	}
	
}

?>
