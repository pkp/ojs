<?php

/**
 * @defgroup article
 */
 
/**
 * @file classes/article/Article.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @class Article
 * @ingroup article
 * @see ArticleDAO
 *
 * @brief Article class.
 */

// $Id$


// Submission status constants
define('STATUS_ARCHIVED', 0);
define('STATUS_QUEUED', 1);
// define('STATUS_SCHEDULED', 2); // #2187: Scheduling queue removed.
define('STATUS_PUBLISHED', 3);
define('STATUS_DECLINED', 4);

// AuthorSubmission::getSubmissionStatus will return one of these in place of QUEUED:
define ('STATUS_QUEUED_UNASSIGNED', 5);
define ('STATUS_QUEUED_REVIEW', 6);
define ('STATUS_QUEUED_EDITING', 7);
define ('STATUS_INCOMPLETE', 8);

// Author display in ToC
define ('AUTHOR_TOC_DEFAULT', 0);
define ('AUTHOR_TOC_HIDE', 1);
define ('AUTHOR_TOC_SHOW', 2);

// Article RT comments
define ('COMMENTS_SECTION_DEFAULT', 0);
define ('COMMENTS_DISABLE', 1);
define ('COMMENTS_ENABLE', 2);

import('submission.Submission');

class Article extends Submission {
	/**
	 * Constructor.
	 */
	function Article() {
		parent::Submission();
	}

	/**
	 * Add an author.
	 * @param $author Author
	 */
	function addAuthor($author) {
		if ($author->getArticleId() == null) {
			$author->setArticleId($this->getArticleId());
		}
		parent::addAuthor($author);
	}

	/**
	 * Get "localized" article title (if applicable).
	 * @return string
	 */
	function getArticleTitle() {
		return $this->getLocalizedData('title');
	}

	/**
	 * Get "localized" article abstract (if applicable).
	 * @return string
	 */
	function getArticleAbstract() {
		return $this->getLocalizedData('abstract');
	}


	//
	// Get/set methods
	//

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
	 * Get ID of journal.
	 * @return int
	 */
	function getJournalId() {
		return $this->getData('journalId');
	}

	/**
	 * Set ID of journal.
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		return $this->setData('journalId', $journalId);
	}

	/**
	 * Get ID of article's section.
	 * @return int
	 */
	function getSectionId() {
		return $this->getData('sectionId');
	}

	/**
	 * Set ID of article's section.
	 * @param $sectionId int
	 */
	function setSectionId($sectionId) {
		return $this->setData('sectionId', $sectionId);
	}

	/**
	 * Get title of article's section.
	 * @return string
	 */
	function getSectionTitle() {
		return $this->getData('sectionTitle');
	}

	/**
	 * Set title of article's section.
	 * @param $sectionTitle string
	 */
	function setSectionTitle($sectionTitle) {
		return $this->setData('sectionTitle', $sectionTitle);
	}

	/**
	 * Get section abbreviation.
	 * @return string
	 */
	function getSectionAbbrev() {
		return $this->getData('sectionAbbrev');
	}

	/**
	 * Set section abbreviation.
	 * @param $sectionAbbrev string
	 */
	function setSectionAbbrev($sectionAbbrev) {
		return $this->setData('sectionAbbrev', $sectionAbbrev);
	}

	/**
	 * Return the localized discipline
	 * @return string
	 */
	function getArticleDiscipline() {
		return $this->getLocalizedData('discipline');
	}

	/**
	 * Return the localized subject classification
	 * @return string
	 */
	function getArticleSubjectClass() {
		return $this->getLocalizedData('subjectClass');
	}

	/**
	 * Return the localized subject
	 * @return string
	 */
	function getArticleSubject() {
		return $this->getLocalizedData('subject');
	}

	/**
	 * Return the localized geographical coverage
	 * @return string
	 */
	function getArticleCoverageGeo() {
		return $this->getLocalizedData('coverageGeo');
	}

	/**
	 * Return the localized chronological coverage
	 * @return string
	 */
	function getArticleCoverageChron() {
		return $this->getLocalizedData('coverageChron');
	}

	/**
	 * Return the localized sample coverage
	 * @return string
	 */
	function getArticleCoverageSample() {
		return $this->getLocalizedData('coverageSample');
	}

	/**
	 * Return the localized type (method/approach)
	 * @return string
	 */
	function getArticleType() {
		return $this->getLocalizedData('type');
	}

	/**
	 * Return the localized sponsor
	 * @return string
	 */
	function getArticleSponsor() {
		return $this->getLocalizedData('sponsor');
	}

	/**
	 * Get the localized article cover filename
	 * @return string
	 */
	function getArticleFileName() {
		return $this->getLocalizedData('fileName');
	}

	/**
	 * Get the localized article cover width
	 * @return string
	 */
	function getArticleWidth() {
		return $this->getLocalizedData('width');
	}

	/**
	 * Get the localized article cover height
	 * @return string
	 */
	function getArticleHeight() {
		return $this->getLocalizedData('height');
	}

	/**
	 * Get the localized article cover filename on the uploader's computer
	 * @return string
	 */
	function getArticleOriginalFileName() {
		return $this->getLocalizedData('originalFileName');
	}

	/**
	 * Get the localized article cover alternate text
	 * @return string
	 */
	function getArticleCoverPageAltText() {
		return $this->getLocalizedData('coverPageAltText');
	}

	/**
	 * Get the localized article cover filename
	 * @return string
	 */
	function getArticleShowCoverPage() {
		return $this->getLocalizedData('showCoverPage');
	}

	/**
	 * Get comments to editor.
	 * @return string
	 */
	function getCommentsToEditor() {
		return $this->getData('commentsToEditor');
	}

	/**
	 * Set comments to editor.
	 * @param $commentsToEditor string
	 */
	function setCommentsToEditor($commentsToEditor) {
		return $this->setData('commentsToEditor', $commentsToEditor);
	}

	/**
	 * Get current review round.
	 * @return int
	 */
	function getCurrentRound() {
		return $this->getData('currentRound');
	}

	/**
	 * Set current review round.
	 * @param $currentRound int
	 */
	function setCurrentRound($currentRound) {
		return $this->setData('currentRound', $currentRound);
	}

	/**
	 * Get editor file id.
	 * @return int
	 */
	function getEditorFileId() {
		return $this->getData('editorFileId');
	}

	/**
	 * Set editor file id.
	 * @param $editorFileId int
	 */
	function setEditorFileId($editorFileId) {
		return $this->setData('editorFileId', $editorFileId);
	}

	/**
	 * Get copyedit file id.
	 * @return int
	 */
	function getCopyeditFileId() {
		return $this->getData('copyeditFileId');
	}

	/**
	 * Set copyedit file id.
	 * @param $copyeditFileId int
	 */
	function setCopyeditFileId($copyeditFileId) {
		return $this->setData('copyeditFileId', $copyeditFileId);
	}

	/**
	 * get expedited
	 * @return boolean
	 */
	function getFastTracked() {
		return $this->getData('fastTracked');
	}
	 
	/**
	 * set fastTracked
	 * @param $fastTracked boolean
	 */
	function setFastTracked($fastTracked) {
		return $this->setData('fastTracked',$fastTracked);
	}	

	/**
	 * Return boolean indicating if author should be hidden in issue ToC.
	 * @return boolean
	 */
	function getHideAuthor() {
		return $this->getData('hideAuthor');
	}

	/**
	 * Set if author should be hidden in issue ToC.
	 * @param $hideAuthor boolean
	 */
	function setHideAuthor($hideAuthor) {
		return $this->setData('hideAuthor', $hideAuthor);
	}

	/**
	 * Return locale string corresponding to RT comments status.
	 * @return string
	 */
	function getCommentsStatusString() {
		switch ($this->getCommentsStatus()) {
			case COMMENTS_DISABLE:
				return 'article.comments.disable';
			case COMMENTS_ENABLE:
				return 'article.comments.enable';
			default:
				return 'article.comments.sectionDefault';
		}
	}

	/**
	 * Return boolean indicating if article RT comments should be enabled.
	 * Checks both the section and article comments status. Article status
	 * overrides section status.
	 * @return int 
	 */
	function getEnableComments() {
		switch ($this->getCommentsStatus()) {
			case COMMENTS_DISABLE:
				return false;
			case COMMENTS_ENABLE:
				return true;
			case COMMENTS_SECTION_DEFAULT:
				$sectionDao =& DAORegistry::getDAO('SectionDAO');
				$section =& $sectionDao->getSection($this->getSectionId(), $this->getJournalId());
				if ($section->getDisableComments()) {
					return false;
				} else {
					return true;
				}
		}
	}

	/**
	 * Get an associative array matching RT comments status codes with locale strings.
	 * @return array comments status => localeString
	 */
	function &getCommentsStatusOptions() {
		static $commentsStatusOptions = array(
			COMMENTS_SECTION_DEFAULT => 'article.comments.sectionDefault',
			COMMENTS_DISABLE => 'article.comments.disable',
			COMMENTS_ENABLE => 'article.comments.enable'
		);
		return $commentsStatusOptions;
	}
	
	/**
	 * Get an array of user IDs associated with this article
	 * @param $authors boolean
	 * @param $reviewers boolean
	 * @param $editors boolean
	 * @param $proofreaders boolean
	 * @param $copyeditors boolean
	 * @param $layoutEditors boolean
	 * @return array User IDs
	 */
	function getAssociatedUserIds($authors = true, $reviewers = true, $editors = true, $proofreaders = true, $copyeditors = true, $layoutEditors = true) {
		$articleId = $this->getArticleId();
		
		$userIds = array();

		if($authors) {
			$authorDao = &DAORegistry::getDAO('AuthorDAO');
			$authors = $authorDao->getAuthorsByArticle($articleId);
			foreach ($authors as $author) {
				$userIds[] = array('id' => $author->getAuthorId(), 'role' => 'author');
			}
		}
		
		if($editors) {
			$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
			$editAssignments =& $editAssignmentDao->getEditorAssignmentsByArticleId($articleId);
			while ($editAssignment =& $editAssignments->next()) {
				$userIds[] = array('id' => $editAssignment->getEditorId(), 'role' => 'editor');
				unset($editAssignment);
			}
		}
		
		if($copyeditors) {
			$copyAssignmentDao = &DAORegistry::getDAO('CopyAssignmentDAO');
			$copyAssignment =& $copyAssignmentDao->getCopyAssignmentByArticleId($articleId);
			if ($copyAssignment != null && $copyAssignment->getCopyeditorId() > 0) {
				$userIds[] =array('id' =>  $copyAssignment->getCopyeditorId(), 'role' => 'copyeditor');
			}
		}
		
		if($layoutEditors) {
			$layoutAssignmentDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
			$layoutEditorId = $layoutAssignmentDao->getLayoutEditorIdByArticleId($articleId);
			if ($layoutEditorId != null && $layoutEditorId > 0) {
				$userIds[] = array('id' => $layoutEditorId, 'role' => 'layoutEditor');
			}
		}	
		
		if($proofreaders) {
			$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
			$proofAssignment =& $proofAssignmentDao->getProofAssignmentByArticleId($articleId);
			if ($proofAssignment != null && $proofAssignment->getProofreaderId() > 0) {
				$userIds[] = array('id' => $proofAssignment->getProofreaderId(), 'role' => 'proofreader');
			}
		}
		
		if($reviewers) {
			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewAssignments =& $reviewAssignmentDao->getReviewAssignmentsByArticleId($articleId);
			foreach ($reviewAssignments as $reviewAssignment) {
				$userIds[] = array('id' => $reviewAssignment->getReviewerId(), 'role' => 'reviewer');
				unset($reviewAssignment);
			}
		}
				
		return $userIds;
	}
}

?>
