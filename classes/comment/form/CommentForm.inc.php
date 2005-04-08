<?php

/**
 * CommentForm.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package rt.ojs.form
 *
 * Form to change metadata information for an RT comment.
 *
 * $Id$
 */

class CommentForm extends Form {
	
	/** @var int the ID of the comment */
	var $commentId;

	/** @var int the ID of the article */
	var $articleId;

	/** @var Comment current comment */
	var $comment;

	/** @var Comment parent comment ID if applicable */
	var $parentId;
	
	/**
	 * Constructor.
	 */
	function CommentForm($commentId, $articleId, $parentId = null) {
		parent::Form('comment/comment.tpl');

		$this->articleId = $articleId;

		$commentDao = &DAORegistry::getDAO('CommentDAO');
		$this->comment = &$commentDao->getComment($commentId, $articleId);

		if (isset($this->comment)) {
			$this->commentId = $commentId;
		}

		$this->parentId = $parentId;
	}
	
	/**
	 * Initialize form data from current comment.
	 */
	function initData() {
		if (isset($this->comment)) {
			$comment = &$this->comment;
			$this->_data = array(
				'title' => $comment->getTitle(),
				'body' => $comment->getBody()
			);
		} else {
			$this->_data = array();
		}
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$journal = Request::getJournal();

		$templateMgr = &TemplateManager::getManager();

		if (isset($this->comment)) {
			$templateMgr->assign('comment', $this->comment);
			$templateMgr->assign('commentId', $this->commentId);
		}

		$templateMgr->assign('parentId', $this->parentId);
		$templateMgr->assign('articleId', $this->articleId);
		$templateMgr->assign('enableComments', $journal->getSetting('enableComments'));

		parent::display();
	}
	
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'body',
				'title'
			)
		);
	}

	/**
	 * Save changes to comment.
	 * @return int the comment ID
	 */
	function execute() {
		$journal = &Request::getJournal();
		$enableComments = $journal->getSetting('enableComments');

		$commentDao = &DAORegistry::getDAO('CommentDAO');
		
		$comment = $this->comment;
		if (!isset($comment)) {
			$comment = new Comment();
		}

		$comment->setTitle($this->getData('title'));
		$comment->setBody($this->getData('body'));
		$comment->setParentCommentId($this->parentId);

		$user = Request::getUser();

		$comment->setUser((Request::getUserVar('anonymous') && $enableComments!='authenticated')?null:$user);
		
		if (isset($this->comment)) {
			$commentDao->updateComment(&$comment);
		} else {
			$comment->setArticleId($this->articleId);
			$comment->setChildCommentCount(0);
			$commentDao->insertComment(&$comment);
			$this->commentId = $comment->getCommentId();
			$commentDao->incrementChildCount($this->parentId);
		}
		
		return $this->commentId;
	}
	
}

?>
