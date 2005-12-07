<?php

/**
 * ReviewerSubmission.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * ReviewerSubmission class.
 *
 * $Id$
 */

import('article.Article');

define('SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT', 1);
define('SUBMISSION_REVIEWER_RECOMMENDATION_PENDING_REVISIONS', 2);
define('SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_HERE', 3);
define('SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_ELSEWHERE', 4);
define('SUBMISSION_REVIEWER_RECOMMENDATION_DECLINE', 5);
define('SUBMISSION_REVIEWER_RECOMMENDATION_SEE_COMMENTS', 6);

class ReviewerSubmission extends Article {

	/** @var array ArticleFiles reviewer file revisions of this article */
	var $reviewerFileRevisions;
	
	/** @var array ArticleComments peer review comments of this article */
	var $peerReviewComments;

	/** @var array the editor decisions of this article */
	var $editorDecisions;

	/**
	 * Constructor.
	 */
	function ReviewerSubmission() {
		parent::Article();
	}
	
	/**
	 * Get/Set Methods.
	 */
	 
	/**
	 * Get edit assignments for this article.
	 * @return array
	 */
	function &getEditAssignments() {
		$editAssignments = &$this->getData('editAssignments');
		return $editAssignments;
	}
	
	/**
	 * Set edit assignments for this article.
	 * @param $editAssignments array
	 */
	function setEditAssignments($editAssignments) {
		return $this->setData('editAssignments', $editAssignments);
	}
	
	/**
	 * Get ID of review assignment.
	 * @return int
	 */
	function getReviewId() {
		return $this->getData('reviewId');
	}
	
	/**
	 * Set ID of review assignment
	 * @param $reviewId int
	 */
	function setReviewId($reviewId) {
		return $this->setData('reviewId', $reviewId);
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
	 * Get ID of reviewer.
	 * @return int
	 */
	function getReviewerId() {
		return $this->getData('reviewerId');
	}
	
	/**
	 * Set ID of reviewer.
	 * @param $reviewerId int
	 */
	function setReviewerId($reviewerId) {
		return $this->setData('reviewerId', $reviewerId);
	}
	
	/**
	 * Get full name of reviewer.
	 * @return string
	 */
	function getReviewerFullName() {
		return $this->getData('reviewerFullName');
	}
	
	/**
	 * Set full name of reviewer.
	 * @param $reviewerFullName string
	 */
	function setReviewerFullName($reviewerFullName) {
		return $this->setData('reviewerFullName', $reviewerFullName);
	}
	
	/**
	 * Get editor decisions.
	 * @return array
	 */
	function getDecisions($round = null) {
		if ($round == null) {
			return $this->editorDecisions;
		} else {
			if (isset($this->editorDecisions[$round])) return $this->editorDecisions[$round];
			else return null;
		}
	}
	
	/**
	 * Set editor decisions.
	 * @param $editorDecisions array
	 * @param $round int
	 */
	function setDecisions($editorDecisions, $round) {
		$this->stampStatusModified();
		return $this->editorDecisions[$round] = $editorDecisions;
	}
	
	/**
	 * Get reviewer recommendation.
	 * @return string
	 */
	function getRecommendation() {
		return $this->getData('recommendation');
	}
	
	/**
	 * Set reviewer recommendation.
	 * @param $recommendation string
	 */
	function setRecommendation($recommendation) {
		return $this->setData('recommendation', $recommendation);
	}
	
	/**
	 * Get the reviewer's assigned date.
	 * @return string
	 */
	function getDateAssigned() {
		return $this->getData('dateAssigned');
	}
	
	/**
	 * Set the reviewer's assigned date.
	 * @param $dateAssigned string
	 */
	function setDateAssigned($dateAssigned) {
		return $this->setData('dateAssigned', $dateAssigned);
	}
	
	/**
	 * Get the reviewer's notified date.
	 * @return string
	 */
	function getDateNotified() {
		return $this->getData('dateNotified');
	}
	
	/**
	 * Set the reviewer's notified date.
	 * @param $dateNotified string
	 */
	function setDateNotified($dateNotified) {
		return $this->setData('dateNotified', $dateNotified);
	}
	
	/**
	 * Get the reviewer's confirmed date.
	 * @return string
	 */
	function getDateConfirmed() {
		return $this->getData('dateConfirmed');
	}
	
	/**
	 * Set the reviewer's confirmed date.
	 * @param $dateConfirmed string
	 */
	function setDateConfirmed($dateConfirmed) {
		return $this->setData('dateConfirmed', $dateConfirmed);
	}
	
	/**
	 * Get the reviewer's completed date.
	 * @return string
	 */
	function getDateCompleted() {
		return $this->getData('dateCompleted');
	}
	
	/**
	 * Set the reviewer's completed date.
	 * @param $dateCompleted string
	 */
	function setDateCompleted($dateCompleted) {
		return $this->setData('dateCompleted', $dateCompleted);
	}
	
	/**
	 * Get the reviewer's acknowledged date.
	 * @return string
	 */
	function getDateAcknowledged() {
		return $this->getData('dateAcknowledged');
	}
	
	/**
	 * Set the reviewer's acknowledged date.
	 * @param $dateAcknowledged string
	 */
	function setDateAcknowledged($dateAcknowledged) {
		return $this->setData('dateAcknowledged', $dateAcknowledged);
	}
	
	/**
	 * Get the reviewer's due date.
	 * @return string
	 */
	function getDateDue() {
		return $this->getData('dateDue');
	}
	
	/**
	 * Set the reviewer's due date.
	 * @param $dateDue string
	 */
	function setDateDue($dateDue) {
		return $this->setData('dateDue', $dateDue);
	}
	
	/**
	 * Get the declined value.
	 * @return boolean
	 */
	function getDeclined() {
		return $this->getData('declined');
	}
	
	/**
	 * Set the reviewer's declined value.
	 * @param $declined boolean
	 */
	function setDeclined($declined) {
		return $this->setData('declined', $declined);
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
	 * Get the cancelled value.
	 * @return boolean
	 */
	function getCancelled() {
		return $this->getData('cancelled');
	}
	
	/**
	 * Set the reviewer's cancelled value.
	 * @param $replaced boolean
	 */
	function setCancelled($cancelled) {
		return $this->setData('cancelled', $cancelled);
	}

	/**
	 * Get reviewer file id.
	 * @return int
	 */
	function getReviewerFileId() {
		return $this->getData('reviewerFileId');
	}
	
	/**
	 * Set reviewer file id.
	 * @param $reviewerFileId int
	 */
	function setReviewerFileId($reviewerFileId) {
		return $this->setData('reviewerFileId', $reviewerFileId);
	}
	
	/**
	 * Get quality.
	 * @return int
	 */
	function getQuality() {
		return $this->getData('quality');
	}
	
	/**
	 * Set quality.
	 * @param $quality int
	 */
	function setQuality($quality) {
		return $this->setData('quality', $quality);
	}
	
	
	/**
	 * Get round.
	 * @return int
	 */
	function getRound() {
		return $this->getData('round');
	}
	
	/**
	 * Set round.
	 * @param $round int
	 */
	function setRound($round) {
		return $this->setData('round', $round);
	}
	
	/**
	 * Get review file id.
	 * @return int
	 */
	function getReviewFileId() {
		return $this->getData('reviewFileId');
	}
	
	/**
	 * Set review file id.
	 * @param $reviewFileId int
	 */
	function setReviewFileId($reviewFileId) {
		return $this->setData('reviewFileId', $reviewFileId);
	}
	
	/**
	 * Get review revision.
	 * @return int
	 */
	function getReviewRevision() {
		return $this->getData('reviewRevision');
	}
	
	/**
	 * Set review revision.
	 * @param $reviewRevision int
	 */
	function setReviewRevision($reviewRevision) {
		return $this->setData('reviewRevision', $reviewRevision);
	}
	
	//
	// Files
	//
	
	/**
	 * Get submission file for this article.
	 * @return ArticleFile
	 */
	function &getSubmissionFile() {
		$returner =& $this->getData('submissionFile');
		return $returner;
	}
	
	/**
	 * Set submission file for this article.
	 * @param $submissionFile ArticleFile
	 */
	function setSubmissionFile($submissionFile) {
		return $this->setData('submissionFile', $submissionFile);
	}
	
	/**
	 * Get revised file for this article.
	 * @return ArticleFile
	 */
	function &getRevisedFile() {
		$returner =& $this->getData('revisedFile');
		return $returner;
	}
	
	/**
	 * Set revised file for this article.
	 * @param $submissionFile ArticleFile
	 */
	function setRevisedFile($revisedFile) {
		return $this->setData('revisedFile', $revisedFile);
	}
	
	/**
	 * Get supplementary files for this article.
	 * @return array SuppFiles
	 */
	function &getSuppFiles() {
		$returner =& $this->getData('suppFiles');
		return $returner;
	}
	
	/**
	 * Set supplementary file for this article.
	 * @param $suppFiles array SuppFiles
	 */
	function setSuppFiles($suppFiles) {
		return $this->setData('suppFiles', $suppFiles);
	}
	
	/**
	 * Get review file.
	 * @return ArticleFile
	 */
	function &getReviewFile() {
		$returner =& $this->getData('reviewFile');
		return $returner;
	}
	
	/**
	 * Set review file.
	 * @param $reviewFile ArticleFile
	 */
	function setReviewFile($reviewFile) {
		return $this->setData('reviewFile', $reviewFile);
	}

	/**
	 * Get reviewer file.
	 * @return ArticleFile
	 */
	function &getReviewerFile() {
		$returner =& $this->getData('reviewerFile');
		return $returner;
	}
	
	/**
	 * Set reviewer file.
	 * @param $reviewFile ArticleFile
	 */
	function setReviewerFile($reviewerFile) {
		return $this->setData('reviewerFile', $reviewerFile);
	}
	
	/**
	 * Get all reviewer file revisions.
	 * @return array ArticleFiles
	 */
	function getReviewerFileRevisions() {
		return $this->reviewerFileRevisions;
	}
	
	/**
	 * Set all reviewer file revisions.
	 * @param $reviewerFileRevisions array ArticleFiles
	 */
	function setReviewerFileRevisions($reviewerFileRevisions) {
		return $this->reviewerFileRevisions = $reviewerFileRevisions;
	}
	
	//
	// Comments
	//
	
	/**
	 * Get most recent peer review comment.
	 * @return ArticleComment
	 */
	function getMostRecentPeerReviewComment() {
		return $this->getData('peerReviewComment');
	}
	
	/**
	 * Set most recent peer review comment.
	 * @param $peerReviewComment ArticleComment
	 */
	function setMostRecentPeerReviewComment($peerReviewComment) {
		return $this->setData('peerReviewComment', $peerReviewComment);
	}
}

?>
