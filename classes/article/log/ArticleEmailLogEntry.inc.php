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
