<?php

/**
 * SectionEditorAction.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * SectionEditorAction class.
 *
 * $Id$
 */

class SectionEditorAction {

	/**
	 * Constructor.
	 */
	function SectionEditorAction() {

	}
	
	/**
	 * Actions.
	 */
	 
	/**
	 * Records an editor's submission recommendation.
	 * @param $articleId int
	 * @param $recommendation int
	 */
	function recordRecommendation($articleId, $recommendation) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$sectionEditorSubmission->setRecommendation($recommendation);
		
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
	}
	
	/**
	 * Assigns a reviewer to a submission.
	 * @param $articleId int
	 * @param $reviewerId int
	 */
	function addReviewer($articleId, $reviewerId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);

		$now = date('Y-m-d H:i:d');

		$reviewAssignment = new ReviewAssignment();
		$reviewAssignment->setReviewerId($reviewerId);
		$reviewAssignment->setDateAssigned($now);
		$reviewAssignment->setDeclined(0);
		$reviewAssignment->setReplaced(0);
		
		$sectionEditorSubmission->AddReviewAssignment($reviewAssignment);
		
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);	
	}
	
	/**
	 * Unassigns a reviewer from a submission.
	 * @param $articleId int
	 * @param $reviewId int
	 */
	function clearReviewer($articleId, $reviewId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$sectionEditorSubmission->removeReviewAssignment($reviewId);

		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
	}
	
}

?>
