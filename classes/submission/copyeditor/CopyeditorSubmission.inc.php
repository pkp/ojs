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

	/** @var array the revisions of the copyeditor file */
	var $copyeditorFileRevisions;

	/** @var array the revisions of the author revised version of the copyedit file */
	var $copyeditRevisedFileRevisions;
	
	/** @var array the revisions of the copyeditor's final file */
	var $copyeditorFinalFileRevisions;

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
	 * Get copyedit revision.
	 * @return int
	 */
	function getCopyeditRevision() {
		return $this->getData('copyeditRevision');
	}
	
	/**
	 * Set copyedit revision.
	 * @param $copyeditRevision int
	 */
	function setCopyeditRevision($copyeditRevision)	{
		return $this->setData('copyeditRevision', $copyeditRevision);
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
	 * Get the replaced value.
	 * @return boolean
	 */
	function getReplaced() {
		return $this->getData('replaced');
	}
	
	/**
	 * Set the reviewer's replaced value.
	 * @param $replaced boolean
	 */
	function setReplaced($replaced) {
		return $this->setData('replaced', $replaced);
	}
	
	/**
	 * Get initial revision.
	 * @return int
	 */
	function getInitialRevision() {
		return $this->getData('initialRevision');
	}
	
	/**
	 * Set initial revision.
	 * @param $initialRevision int
	 */
	function setInitialRevision($initialRevision)	{
		return $this->setData('initialRevision', $initialRevision);
	}
	
	/**
	 * Get editor/author revision.
	 * @return int
	 */
	function getEditorAuthorRevision() {
		return $this->getData('editorAuthorRevision');
	}
	
	/**
	 * Set editor/author revision.
	 * @param $editorAuthorRevision int
	 */
	function setEditorAuthorRevision($editorAuthorRevision)	{
		return $this->setData('editorAuthorRevision', $editorAuthorRevision);
	}
	
	/**
	 * Get final revision.
	 * @return int
	 */
	function getFinalRevision() {
		return $this->getData('finalRevision');
	}
	
	/**
	 * Set final revision.
	 * @param $finalRevision int
	 */
	function setFinalRevision($finalRevision)	{
		return $this->setData('finalRevision', $finalRevision);
	}
	
	//
	// Editor
	//	
	
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
	
	//
	// Files
	//

	/**
	 * Get initial revision file.
	 * (The file to be copyeditted in the Initial Copyedit stage.)
	 * @return ArticleFile
	 */
	function getInitialRevisionFile() {
		return $this->getData('initialRevisionFile');
	}
	
	/**
	 * Set initial revision file.
	 * (The file to be copyeditted in the Initial Copyedit stage.)
	 * @param $initialRevisionFile ArticleFile
	 */
	function setInitialRevisionFile($initialRevisionFile) {
		return $this->setData('initialRevisionFile', $initialRevisionFile);
	}
	
	/**
	 * Get final revision file.
	 * (The file to be copyeditted in the Final Copyedit stage.)
	 * @return ArticleFile
	 */
	function getFinalRevisionFile() {
		return $this->getData('finalRevisionFile');
	}
	
	/**
	 * Set final revision file.
	 * (The file to be copyeditted in the Final Copyedit stage.)
	 * @param $finalRevisionFile ArticleFile
	 */
	function setFinalRevisionFile($finalRevisionFile) {
		return $this->setData('finalRevisionFile', $finalRevisionFile);
	}
}

?>
