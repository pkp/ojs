<?php

/**
 * @file classes/submission/form/comment/CommentForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CommentForm
 * @ingroup submission_form
 * @see Comment, ArticleCommentDAO
 *
 * @brief Comment form.
 */

import('lib.pkp.classes.form.Form');

class CommentForm extends Form {

	/** @var int the comment type */
	var $commentType;

	/** @var int the role id of the comment poster */
	var $roleId;

	/** @var Article current article */
	var $article;

	/** @var User comment author */
	var $user;

	/** @var int the ID of the comment after insertion */
	var $commentId;

	/**
	 * Constructor.
	 * @param $article object
	 */
	function CommentForm($article, $commentType, $roleId, $assocId = null) {
		if ($commentType == COMMENT_TYPE_PEER_REVIEW) {
			parent::Form('submission/comment/peerReviewComment.tpl');
		} else if ($commentType == COMMENT_TYPE_EDITOR_DECISION) {
			parent::Form('submission/comment/editorDecisionComment.tpl');
		} else {
			parent::Form('submission/comment/comment.tpl');
		}

		$this->article = $article;
		$this->commentType = $commentType;
		$this->roleId = $roleId;
		$this->assocId = $assocId == null ? $article->getId() : $assocId;

		$this->user =& Request::getUser();

		if ($commentType != COMMENT_TYPE_PEER_REVIEW) $this->addCheck(new FormValidator($this, 'comments', 'required', 'editor.article.commentsRequired'));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Set the user this comment form is associated with.
	 * @param $user object
	 */
	function setUser(&$user) {
		$this->user =& $user;
	}

	/**
	 * Display the form.
	 */
	function display() {
		$article = $this->article;

		$articleCommentDao =& DAORegistry::getDAO('ArticleCommentDAO');
		$articleComments =& $articleCommentDao->getArticleComments($article->getId(), $this->commentType, $this->assocId);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('articleId', $article->getId());
		$templateMgr->assign('commentTitle', strip_tags($article->getLocalizedTitle()));
		$user =& $this->user;
		$templateMgr->assign('userId', $user->getId());
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
		$commentDao =& DAORegistry::getDAO('ArticleCommentDAO');
		$article = $this->article;

		// Insert new comment		
		$comment = new ArticleComment();
		$comment->setCommentType($this->commentType);
		$comment->setRoleId($this->roleId);
		$comment->setArticleId($article->getId());
		$comment->setAssocId($this->assocId);
		$comment->setAuthorId($this->user->getId());
		$comment->setCommentTitle($this->getData('commentTitle'));
		$comment->setComments($this->getData('comments'));
		$comment->setDatePosted(Core::getCurrentDate());
		$comment->setViewable($this->getData('viewable'));

		$this->commentId = $commentDao->insertArticleComment($comment);
	}

	/**
	 * Email the comment.
	 * @param $recipients array of recipients (email address => name)
	 */
	function email($recipients, $request) {
		$article = $this->article;
		$articleCommentDao =& DAORegistry::getDAO('ArticleCommentDAO');
		$journal =& Request::getJournal();

		import('classes.mail.ArticleMailTemplate');
		$email = new ArticleMailTemplate($article, 'SUBMISSION_COMMENT');
		$email->setFrom($this->user->getEmail(), $this->user->getFullName());

		$commentText = $this->getData('comments');

		// Individually send an email to each of the recipients.
		foreach ($recipients as $emailAddress => $name) {
			$email->addRecipient($emailAddress, $name);

			$paramArray = array(
				'name' => $name,
				'commentName' => $this->user->getFullName(),
				'comments' => String::html2text($commentText)
			);

			$email->sendWithParams($paramArray, $request);
			$email->clearRecipients();
		}
	}
}

?>
