<?php

/**
 * EditCommentForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.form
 *
 * Edit comment form.
 *
 * $Id$
 */


class EditCommentForm extends Form {

	/** @var int the ID of the comment */
	var $commentId;
	
	/** @var ArticleComment the comment */
	var $comment;
	
	/** @var User the user */
	var $user;

	/**
	 * Constructor.
	 * @param $commentId int
	 */
	function EditCommentForm($commentId) {
		parent::Form('submission/comment/editComment.tpl');
		$this->commentId = $commentId;
		
		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$this->comment = &$articleCommentDao->getArticleCommentById($commentId);

		$this->articleId = $this->comment->getArticleId();
		$this->user = &Request::getUser();
	}
	
	/**
	 * Initialize form data from current comment.
	 */
	function initData() {
		if (isset($this->comment)) {
			$comment = &$this->comment;
			$this->_data = array(
				'commentId' => $comment->getCommentId(),
				'commentTitle' => $comment->getCommentTitle(),
				'comments' => $comment->getComments()
			);
		}
	}	
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('comment', $this->comment);
		$templateMgr->assign('hiddenFormParams', 
			array(
				'articleId' => $this->articleId,
				'commentId' => $this->commentId
			)
		);
		
		parent::display();
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'commentTitle',
				'comments',
				'viewable'
			)
		);
	}
	
	/**
	 * Update the comment.
	 */
	function execute() {
		$commentDao = &DAORegistry::getDAO('ArticleCommentDAO');
	
		// Update comment		
		$comment = $this->comment;
		$comment->setCommentTitle($this->getData('commentTitle'));
		$comment->setComments($this->getData('comments'));
		$comment->setViewable($this->getData('viewable'));
		$comment->setDateModified(Core::getCurrentDate());
		
		$commentDao->updateArticleComment($comment);
	}
}

?>
