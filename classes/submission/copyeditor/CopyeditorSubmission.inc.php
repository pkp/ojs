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
	 * Get copyeditor of this article.
	 * @return User
	 */
	function &getCopyeditor() {
		return $this->getData('copyeditor');
	}
	
	/**
	 * Set copyeditor of this article.
	 * @param $copyeditor User
	 */
	function setCopyeditor($copyeditor) {
		return $this->setData('copyeditor', $copyeditor);
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
	function setDateNotified($dateNotified) {
		return $this->setData('dateNotified', $dateNotified);
	}
	
	/**
	 * Get date underway.
	 * @return string
	 */
	function getDateUnderway() {
		return $this->getData('dateUnderway');
	}
	
	/**
	 * Set date underway.
	 * @param $dateUnderway string
	 */
	function setDateUnderway($dateUnderway) {
		return $this->setData('dateUnderway', $dateUnderway);
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
	 * Get date author underway.
	 * @return string
	 */
	function getDateAuthorUnderway() {
		return $this->getData('dateAuthorUnderway');
	}
	
	/**
	 * Set date author underway.
	 * @param $dateAuthorUnderway string
	 */
	function setDateAuthorUnderway($dateAuthorUnderway) {
		return $this->setData('dateAuthorUnderway', $dateAuthorUnderway);
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
	 * Get date final underway.
	 * @return string
	 */
	function getDateFinalUnderway() {
		return $this->getData('dateFinalUnderway');
	}
	
	/**
	 * Set date final underway.
	 * @param $dateFinalUnderway string
	 */
	function setDateFinalUnderway($dateFinalUnderway) {
		return $this->setData('dateFinalUnderway', $dateFinalUnderway);
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
	 * Get initial copyedit file.
	 * (The file to be copyeditted in the Initial Copyedit stage.)
	 * @return ArticleFile
	 */
	function getInitialCopyeditFile() {
		return $this->getData('initialCopyeditFile');
	}
	
	/**
	 * Set initial copyedit file.
	 * (The file to be copyeditted in the Initial Copyedit stage.)
	 * @param $initialCopyeditFile ArticleFile
	 */
	function setInitialCopyeditFile($initialCopyeditFile) {
		return $this->setData('initialCopyeditFile', $initialCopyeditFile);
	}
	
	/**
	 * Get editor author copyedit file.
	 * (The file to be copyeditted in the Author Copyedit stage.)
	 * @return ArticleFile
	 */
	function getEditorAuthorCopyeditFile() {
		return $this->getData('editorAuthorCopyeditFile');
	}
	
	/**
	 * Set editor author copyedit file.
	 * (The file to be copyeditted in the Author Copyedit stage.)
	 * @param $editorAuthorCopyeditFile ArticleFile
	 */
	function setEditorAuthorCopyeditFile($editorAuthorCopyeditFile) {
		return $this->setData('editorAuthorCopyeditFile', $editorAuthorCopyeditFile);
	}
	
	/**
	 * Get final copyedit file.
	 * (The file to be copyeditted in the Final Copyedit stage.)
	 * @return ArticleFile
	 */
	function getFinalCopyeditFile() {
		return $this->getData('finalCopyeditFile');
	}
	
	/**
	 * Set final copyedit file.
	 * (The file to be copyeditted in the Final Copyedit stage.)
	 * @param $finalCopyeditFile ArticleFile
	 */
	function setFinalCopyeditFile($finalCopyeditFile) {
		return $this->setData('finalCopyeditFile', $finalCopyeditFile);
	}
	
	//
	// Comments
	//
	
	/**
	 * Get most recent copyedit comment.
	 * @return ArticleComment
	 */
	function getMostRecentCopyeditComment() {
		return $this->getData('mostRecentCopyeditComment');
	}
	
	/**
	 * Set most recent copyedit comment.
	 * @param $mostRecentCopyeditComment ArticleComment
	 */
	function setMostRecentCopyeditComment($mostRecentCopyeditComment) {
		return $this->setData('mostRecentCopyeditComment', $mostRecentCopyeditComment);
	}

	/**
	 * Get proof assignment.
	 * @return proofAssignment object
	 */
	function getProofAssignment() {
		return $this->getData('proofAssignment');
	}

	/**
	 * Set proof assignment.
	 * @param $proofAssignment
	 */
	function setProofAssignment($proofAssignment) {
		return $this->setData('proofAssignment', $proofAssignment);
	}
}

?>
