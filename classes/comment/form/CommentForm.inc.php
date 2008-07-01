<?php

/**
 * @defgroup rt_ojs_form
 */
 

/**
 * @file classes/comment/form/CommentForm.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CommentForm
 * @ingroup rt_ojs_form
 * @see Comment, CommentDAO
 *
 * @brief Form to change metadata information for an RT comment.
 */

// $Id$


import('form.Form');

class CommentForm extends Form {

	/** @var int the ID of the comment */
	var $commentId;

	/** @var boolean Whether or not Captcha support is enabled */
	var $captchaEnabled;

	/** @var int the ID of the article */
	var $articleId;

	/** @var Comment current comment */
	var $comment;

	/** @var Comment parent comment ID if applicable */
	var $parentId;

	/** @var int Galley view by which the user entered the comments pages */
	var $galleyId;

	/**
	 * Constructor.
	 */
	function CommentForm($commentId, $articleId, $galleyId, $parentId = null) {
		parent::Form('comment/comment.tpl');

		$this->articleId = $articleId;

		$commentDao = &DAORegistry::getDAO('CommentDAO');
		$this->comment = &$commentDao->getComment($commentId, $articleId);

		import('captcha.CaptchaManager');
		$captchaManager =& new CaptchaManager();
		$this->captchaEnabled = ($captchaManager->isEnabled() && Config::getVar('captcha', 'captcha_on_comments'))?true:false;

		if (isset($this->comment)) {
			$this->commentId = $commentId;
		}

		$this->parentId = $parentId;
		$this->galleyId = $galleyId;

		if ($this->captchaEnabled) {
			$this->addCheck(new FormValidatorCaptcha($this, 'captcha', 'captchaId', 'common.captchaField.badCaptcha'));
		}
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Initialize form data from current comment.
	 */
	function initData() {
		if (isset($this->comment)) {
			$comment = &$this->comment;
			$this->_data = array(
				'title' => $comment->getTitle(),
				'body' => $comment->getBody(),
				'posterName' => $comment->getPosterName(),
				'posterEmail' => $comment->getPosterEmail()
			);
		} else {
			$this->_data = array();
			$user = Request::getUser();
			if ($user) {
				$this->_data['posterName'] = $user->getFullName();
				$this->_data['posterEmail'] = $user->getEmail();
			}
		}
	}

	/**
	 * Display the form.
	 */
	function display() {
		$journal = Request::getJournal();

		$templateMgr = &TemplateManager::getManager();

		if (isset($this->comment)) {
			$templateMgr->assign_by_ref('comment', $this->comment);
			$templateMgr->assign('commentId', $this->commentId);
		}

		$user = Request::getUser();
		if ($user) {
			$templateMgr->assign('userName', $user->getFullName());
			$templateMgr->assign('userEmail', $user->getEmail());
		}

		if ($this->captchaEnabled) {
			import('captcha.CaptchaManager');
			$captchaManager =& new CaptchaManager();
			$captcha =& $captchaManager->createCaptcha();
			if ($captcha) {
				$templateMgr->assign('captchaEnabled', $this->captchaEnabled);
				$this->setData('captchaId', $captcha->getCaptchaId());
			}
		}

		$templateMgr->assign('parentId', $this->parentId);
		$templateMgr->assign('articleId', $this->articleId);
		$templateMgr->assign('galleyId', $this->galleyId);
		$templateMgr->assign('enableComments', $journal->getSetting('enableComments'));

		parent::display();
	}


	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$userVars = array(
			'body',
			'title',
			'posterName',
			'posterEmail'
		);
		if ($this->captchaEnabled) {
			$userVars[] = 'captchaId';
			$userVars[] = 'captcha';
		}

		$this->readUserVars($userVars);
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
			$comment = &new Comment();
		}

		$user = &Request::getUser();

		$comment->setTitle($this->getData('title'));
		$comment->setBody($this->getData('body'));

		if (($enableComments == COMMENTS_ANONYMOUS || $enableComments == COMMENTS_UNAUTHENTICATED) && (Request::getUserVar('anonymous') || $user == null)) {
			$comment->setPosterName($this->getData('posterName'));
			$comment->setPosterEmail($this->getData('posterEmail'));
			$comment->setUser(null);
		} else {
			$comment->setPosterName($user->getFullName());
			$comment->setPosterEmail($user->getEmail());
			$comment->setUser($user);
		}

		$comment->setParentCommentId($this->parentId);

		if (isset($this->comment)) {
			$commentDao->updateComment($comment);
		} else {
			$comment->setArticleId($this->articleId);
			$comment->setChildCommentCount(0);
			$commentDao->insertComment($comment);
			$this->commentId = $comment->getCommentId();
		}

		return $this->commentId;
	}

}

?>
