<?php

/**
 * CommentForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.form
 *
 * Comment form.
 *
 * $Id$
 */


class CommentForm extends Form {

	/** @var int the ID of the article */
	var $articleId;

	/** @var int the comment type */
	var $commentType;
	
	/** @var int the role id of the comment poster */
	var $roleId;

	/** @var Article current article */
	var $article;

	/** @var User comment author */
	var $user;
	
	/**
	 * Constructor.
	 * @param $articleId int
	 */
	function CommentForm($articleId, $commentType, $roleId, $assocId = null) {
		if ($commentType == COMMENT_TYPE_PEER_REVIEW) {
			parent::Form('submission/comment/peerReviewComment.tpl');
		} else {
			parent::Form('submission/comment/comment.tpl');
		}
		
		$this->articleId = $articleId;
		$this->commentType = $commentType;
		$this->roleId = $roleId;
		$this->assocId = $assocId == null ? $articleId : $assocId;
		
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$this->article = &$articleDao->getArticle($articleId);

		$this->user = &Request::getUser();
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$articleComments = &$articleCommentDao->getArticleComments($this->articleId, $this->commentType, $this->assocId);
	
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $this->articleId);
		$templateMgr->assign('userId', $this->user->getUserId());
		$templateMgr->assign('articleComments', $articleComments);
		
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
	 * Add the comment.
	 */
	function execute() {
		$commentDao = &DAORegistry::getDAO('ArticleCommentDAO');
	
		// Insert new comment		
		$comment = &new ArticleComment();
		$comment->setCommentType($this->commentType);
		$comment->setRoleId($this->roleId);
		$comment->setArticleId($this->articleId);
		$comment->setAssocId($this->assocId);
		$comment->setAuthorId($this->user->getUserId());
		$comment->setCommentTitle($this->getData('commentTitle'));
		$comment->setComments($this->getData('comments'));
		$comment->setDatePosted(Core::getCurrentDate());
		$comment->setViewable($this->getData('viewable'));
		
		$commentDao->insertArticleComment($comment);
	}
	
	/**
	 * Email the comment.
	 * @param $recipients array of recipients (email address => name)
	 * @param $insertedComments array of comment IDs (currently only used for review-type emails)
	 */
	function email($recipients, $insertedComments = null) {
		$email = &new ArticleMailTemplate($this->articleId, 'COMMENT_EMAIL');
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		
		$article = &$articleDao->getArticle($this->articleId);
		
		$commentText = "";
		if ($insertedComments != null) {
			foreach ($insertedComments as $commentId) {
				$comment = &$articleCommentDao->getArticleCommentById($commentId);
				$commentText .= $comment->getComments() . "\n\n";
			}
		} else {
			$commentText = $this->getData('comments');
		}

		foreach ($recipients as $emailAddress => $name) {
			$email->addRecipient($emailAddress, $name);
			$email->setSubject($article->getArticleTitle());

			$paramArray = array(
				'name' => $name,
				'commentName' => $this->user->getFullName(),
				'articleTitle' => $article->getArticleTitle(),
				'comments' => $commentText	
			);
			$email->assignParams($paramArray);

			$email->send();
			$email->clearRecipients();
		}
	}
}

?>
