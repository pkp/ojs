<?php

/**
 * CopyAssignment.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.copyAssignment
 *
 * CopyedAssignment class.
 * Describes copyediting assignments.
 *
 * $Id$
 */

class CopyAssignment extends DataObject {

	/**
	 * Constructor.
	 */
	function CopyAssignment() {
		parent::DataObject();
	}
	
	//
	// Get/set methods
	//
	
	/**
	 * Get ID of copyed assignment.
	 * @return int
	 */
	function getCopyedId() {
		return $this->getData('copyedId');
	}
	
	/**
	 * Set ID of copyed assignment
	 * @param $copyedId int
	 */
	function setCopyedId($copyedId) {
		return $this->setData('copyedId', $copyedId);
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
	 * Get copyeditor id of this article.
	 * @return int
	 */
	function getCopyeditorId() {
		return $this->getData('copyeditorId');
	}
	
	/**
	 * Set copyeditor id of this article.
	 * @param $copyeditorId User
	 */
	function setCopyeditorId($copyeditorId) {
		return $this->setData('copyeditorId', $copyeditorId);
	}
	
	/**
	 * Get full name of copyeditor.
	 * @return string
	 */
	function getCopyeditorFullName() {
		return $this->getData('copyeditorFullName');
	}
	
	/**
	 * Set full name of copyeditor.
	 * @param $copyeditorFullName string
	 */
	function setCopyeditorFullName($copyeditorFullName) {
		return $this->setData('copyeditorFullName', $copyeditorFullName);
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
	 * Get ID of the copyed file.
	 * @return int
	 */
	function getCopyedFileId() {
		return $this->getData('copyedFileId');
	}
	
	/**
	 * Set ID of the copyed file.
	 * @param $copyedFileId int
	 */
	function setCopyedFileId($copyedFileId) {
		return $this->setData('copyedFileId', $copyedFileId);
	}
	
	/**
	 * Get copyed file.
	 * @return ArticleFile
	 */
	function getLayoutFile() {
		return $this->getData('layoutFile');
	}
	
	/**
	 * Set layout file.
	 * @param $layoutFile ArticleFile
	 */
	function setLayoutFile($layoutFile) {
		return $this->setData('layoutFile', $layoutFile);
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
	// Files
	//

	/**
	 * Get initial copyedit file.
	 * (The file to be copyeditted in the Initial Copyedit stage.)
	 * @return ArticleFile
	 */
	function &getInitialCopyeditFile() {
		$returner =& $this->getData('initialCopyeditFile');
		return $returner;
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
	function &getEditorAuthorCopyeditFile() {
		$returner =& $this->getData('editorAuthorCopyeditFile');
		return $returner;
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
	function &getFinalCopyeditFile() {
		$returner =& $this->getData('finalCopyeditFile');
		return $returner;
	}
	
	/**
	 * Set final copyedit file.
	 * (The file to be copyeditted in the Final Copyedit stage.)
	 * @param $finalCopyeditFile ArticleFile
	 */
	function setFinalCopyeditFile($finalCopyeditFile) {
		return $this->setData('finalCopyeditFile', $finalCopyeditFile);
	}

}

?>
