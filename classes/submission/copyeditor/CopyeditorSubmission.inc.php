<?php

/**
 * CopyeditorSubmission.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * CopyeditorSubmission class.
 *
 * $Id$
 */

class CopyeditorSubmission extends Article {

	/**
	 * Constructor.
	 */
	function CopyeditorSubmission() {
		parent::Article();
	}
	
	/**
	 * Get/Set Methods.
	 */
	 
	/**
	 * Get copy edit id.
	 * @return int
	 */
	function getCopyedId() {
		return $this->getData('copyedId');
	}
	
	/**
	 * Set copy edit id.
	 * @param $copyedId int
	 */
	function setCopyedId($copyedId)
	{
		return $this->setData('copyedId', $copyedId);
	}
	
	/**
	 * Get copyeditor id.
	 * @return int
	 */
	function getCopyeditorId() {
		return $this->getData('copyeditorId');
	}
	
	/**
	 * Set copyeditor id.
	 * @param $copyeditorId int
	 */
	function setCopyeditorId($copyeditorId)
	{
		return $this->setData('copyeditorId', $copyeditorId);
	}

	/**
	 * Get comments.
	 * @return string
	 */
	function getComments() {
		return $this->getData('comments');
	}
	
	/**
	 * Set comments.
	 * @param $comments string
	 */
	function setComments($comments)
	{
		return $this->setData('comments', $comments);
	}
	
	/**
	 * Get date notified.
	 * @return string
	 */
	function getDateNotified() {
		return $this->getData('dateNotified');
	}
	
	/**
	 * Set date notified.
	 * @param $dateNotified string
	 */
	function setDateNotified($dateNotified)
	{
		return $this->setData('dateNotified', $dateNotified);
	}
	
	/**
	 * Get date completed.
	 * @return string
	 */
	function getDateCompleted() {
		return $this->getData('dateCompleted');
	}
	
	/**
	 * Set date completed.
	 * @param $dateCompleted string
	 */
	function setDateCompleted($dateCompleted)
	{
		return $this->setData('dateCompleted', $dateCompleted);
	}
	
	/**
	 * Get date acknowledged.
	 * @return string
	 */
	function getDateAcknowledged() {
		return $this->getData('dateAcknowledged');
	}
	
	/**
	 * Set date acknowledged.
	 * @param $dateAcknowledged string
	 */
	function setDateAcknowledged($dateAcknowledged)
	{
		return $this->setData('dateAcknowledged', $dateAcknowledged);
	}
	
	/**
	 * Get date author notified.
	 * @return string
	 */
	function getDateAuthorNotified() {
		return $this->getData('dateAuthorNotified');
	}
	
	/**
	 * Set date author notified.
	 * @param $dateAuthorNotified string
	 */
	function setDateAuthorNotified($dateAuthorNotified)
	{
		return $this->setData('dateAuthorNotified', $dateAuthorNotified);
	}
	
	/**
	 * Get date author completed.
	 * @return string
	 */
	function getDateAuthorCompleted() {
		return $this->getData('dateAuthorCompleted');
	}
	
	/**
	 * Set date author completed.
	 * @param $dateAuthorCompleted string
	 */
	function setDateAuthorCompleted($dateAuthorCompleted)
	{
		return $this->setData('dateAuthorCompleted', $dateAuthorCompleted);
	}
	
	/**
	 * Get date author acknowledged.
	 * @return string
	 */
	function getDateAuthorAcknowledged() {
		return $this->getData('dateAuthorAcknowledged');
	}
	
	/**
	 * Set date author acknowledged.
	 * @param $dateAuthorAcknowledged string
	 */
	function setDateAuthorAcknowledged($dateAuthorAcknowledged)
	{
		return $this->setData('dateAuthorAcknowledged', $dateAuthorAcknowledged);
	}
	
	/**
	 * Get date final notified.
	 * @return string
	 */
	function getDateFinalNotified() {
		return $this->getData('dateFinalNotified');
	}
	
	/**
	 * Set date final notified.
	 * @param $dateFinalNotified string
	 */
	function setDateFinalNotified($dateFinalNotified)
	{
		return $this->setData('dateFinalNotified', $dateFinalNotified);
	}
	
	/**
	 * Get date final completed.
	 * @return string
	 */
	function getDateFinalCompleted() {
		return $this->getData('dateFinalCompleted');
	}
	
	/**
	 * Set date final completed.
	 * @param $dateFinalCompleted string
	 */
	function setDateFinalCompleted($dateFinalCompleted)
	{
		return $this->setData('dateFinalCompleted', $dateFinalCompleted);
	}
	
	/**
	 * Get date final acknowledged.
	 * @return string
	 */
	function getDateFinalAcknowledged() {
		return $this->getData('dateFinalAcknowledged');
	}
	
	/**
	 * Set date final acknowledged.
	 * @param $dateAuthorAcknowledged string
	 */
	function setDateFinalAcknowledged($dateFinalAcknowledged)
	{
		return $this->setData('dateFinalAcknowledged', $dateFinalAcknowledged);
	}
	
	/**
	 * Get editor of this article.
	 * @return User
	 */
	function &getEditor() {
		return $this->getData('editor');
	}
	
	/**
	 * Set editor of this article.
	 * @param $editor User
	 */
	function setEditor($editor) {
		return $this->setData('editor', $editor);
	}
}

?>
