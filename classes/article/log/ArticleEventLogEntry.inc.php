<?php

/**
 * ArticleEventLogEntry.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article.log
 *
 * Article event log entry class.
 * Describes an entry in the article history log.
 *
 * $Id$
 */

// Log entry associative types. All types must be defined here
define('ARTICLE_LOG_TYPE_DEFAULT', 			0);
define('ARTICLE_LOG_TYPE_AUTHOR', 			0x01);
define('ARTICLE_LOG_TYPE_EDITOR', 			0x02);
define('ARTICLE_LOG_TYPE_REVIEW', 			0x03);
define('ARTICLE_LOG_TYPE_COPYEDIT', 			0x04);
define('ARTICLE_LOG_TYPE_LAYOUT', 			0x05);
define('ARTICLE_LOG_TYPE_PROOFREAD', 			0x06);

// Log entry event types. All types must be defined here
define('ARTICLE_LOG_DEFAULT', 0);

// General events 				0x10000000
define('ARTICLE_LOG_ARTICLE_SUBMIT', 		0x10000001);
define('ARTICLE_LOG_METADATA_UPDATE', 		0x10000002);
define('ARTICLE_LOG_SUPPFILE_UPDATE', 		0x10000003);
define('ARTICLE_LOG_ISSUE_SCHEDULE', 		0x10000004);
define('ARTICLE_LOG_ISSUE_ASSIGN', 		0x10000005);
define('ARTICLE_LOG_ARTICLE_PUBLISH', 		0x10000006);
define('ARTICLE_LOG_ARTICLE_ARCHIVE', 		0x10000007);

// Author events 				0x20000000
define('ARTICLE_LOG_AUTHOR_REVISION', 		0x20000001);

// Editor events 				0x30000000
define('ARTICLE_LOG_EDITOR_ASSIGN', 		0x30000001);
define('ARTICLE_LOG_EDITOR_UNASSIGN',	 	0x30000002);
define('ARTICLE_LOG_EDITOR_DECISION', 		0x30000003);

// Reviewer events 				0x40000000
define('ARTICLE_LOG_REVIEW_ASSIGN', 		0x40000001);
define('ARTICLE_LOG_REVIEW_UNASSIGN',	 	0x40000002);
define('ARTICLE_LOG_REVIEW_INITIATE', 		0x40000003);
define('ARTICLE_LOG_REVIEW_CANCEL', 		0x40000004);
define('ARTICLE_LOG_REVIEW_REINITIATE', 	0x40000005);
define('ARTICLE_LOG_REVIEW_ACCEPT', 		0x40000006);
define('ARTICLE_LOG_REVIEW_DECLINE', 		0x40000007);
define('ARTICLE_LOG_REVIEW_REVISION', 		0x40000008);
define('ARTICLE_LOG_REVIEW_RECOMMENDATION', 	0x40000009);

// Copyeditor events 				0x50000000
define('ARTICLE_LOG_COPYEDIT_ASSIGN', 		0x50000001);
define('ARTICLE_LOG_COPYEDIT_UNASSIGN',	 	0x50000002);
define('ARTICLE_LOG_COPYEDIT_INITIATE', 	0x50000003);
define('ARTICLE_LOG_COPYEDIT_REVISION', 	0x50000004);
define('ARTICLE_LOG_COPYEDIT_INITIAL', 		0x50000005);
define('ARTICLE_LOG_COPYEDIT_FINAL', 		0x50000006);

// Proofreader events 				0x60000000
define('ARTICLE_LOG_PROOFREAD_ASSIGN', 		0x60000001);
define('ARTICLE_LOG_PROOFREAD_UNASSIGN', 	0x60000002);
define('ARTICLE_LOG_PROOFREAD_INITIATE', 	0x60000003);
define('ARTICLE_LOG_PROOFREAD_REVISION', 	0x60000004);
define('ARTICLE_LOG_PROOFREAD_COMPLETE', 	0x60000005);

// Layout events 				0x70000000
define('ARTICLE_LOG_LAYOUT_ASSIGN', 		0x70000001);
define('ARTICLE_LOG_LAYOUT_UNASSIGN', 		0x70000002);
define('ARTICLE_LOG_LAYOUT_INITIATE', 		0x70000003);
define('ARTICLE_LOG_LAYOUT_GALLEY', 		0x70000004);
define('ARTICLE_LOG_LAYOUT_COMPLETE', 		0x70000005);


class ArticleEventLogEntry extends DataObject {

	/**
	 * Constructor.
	 */
	function ArticleEventLogEntry() {
		parent::DataObject();
	}
	
	/**
	 * Set localized log message (in the journal's primary locale)
	 * @param $key localization message key
	 * @param $params array optional array of parameters
	 */
	function setLogMessage($key, $params = array()) {
		$this->setMessage(Locale::translate($key, $params, Locale::getPrimaryLocale()));
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
	 * Get user ID of user that initiated the event.
	 * @return int
	 */
	function getUserId() {
		return $this->getData('userId');
	}
	
	/**
	 * Set user ID of user that initiated the event.
	 * @param $userId int
	 */
	function setUserId($userId) {
		return $this->setData('userId', $userId);
	}
	
	/**
	 * Get date entry was logged.
	 * @return datestamp
	 */
	function getDateLogged() {
		return $this->getData('dateLogged');
	}
	
	/**
	 * Set date entry was logged.
	 * @param $dateLogged datestamp
	 */
	function setDateLogged($dateLogged) {
		return $this->setData('dateLogged', $dateLogged);
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
	 * Get custom log message (non-localized).
	 * @return string
	 */
	function getMessage() {
		return $this->getData('message');
	}
	
	/**
	 * Set custom log message (non-localized).
	 * @param $message string
	 */
	function setMessage($message) {
		return $this->setData('message', $message);
	}
	
}

?>
