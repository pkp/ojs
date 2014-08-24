<?php
/**
 * @file classes/handler/HandlerValidatorSubmissionComment.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class HandlerValidatorSubmissionComment
 * @ingroup handler_validation
 *
 * @brief Class to validate that a comment exists (by id) and that the current user has access
 */

import('lib.pkp.classes.handler.validation.HandlerValidator');

class HandlerValidatorSubmissionComment extends HandlerValidator {
	var $commentId;
	var $user;

	/**
	 * Constructor.
	 * @param $handler Handler the associated form
	 * @param $commentId int
	 * @param $user object Optional user
	 */	 
	function HandlerValidatorSubmissionComment(&$handler, $commentId, $user = null) {
		parent::HandlerValidator($handler);

		$this->commentId = $commentId;
		if ($user) $this->user =& $user;
		else $this->user =& Request::getUser();
	}

	/**
	 * Check if field value is valid.
	 * Value is valid if it is empty and optional or validated by user-supplied function.
	 * @return boolean
	 */
	function isValid() {
		$isValid = true;

		$articleCommentDao =& DAORegistry::getDAO('ArticleCommentDAO');
		$comment =& $articleCommentDao->getArticleCommentById($this->commentId);

		if ($comment == null) {
			$isValid = false;

		} else if ($comment->getAuthorId() != $this->user->getId()) {
			$isValid = false;
		}

		if (!$isValid) {
			Request::redirect(null, Request::getRequestedPage());
		}
		
		$handler =& $this->handler;
		$handler->comment =& $comment;		
		return true;
	}
}

?>
