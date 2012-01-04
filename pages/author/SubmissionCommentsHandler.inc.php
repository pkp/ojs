<?php

/**
 * @file pages/author/SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionCommentsHandler
 * @ingroup pages_author
 *
 * @brief Handle requests for submission comments. 
 */

// $Id$


import('pages.author.TrackSubmissionHandler');

class SubmissionCommentsHandler extends AuthorHandler {
	/** comment associated with the request **/
	var $comment;
	
	/**
	 * Constructor
	 **/
	function SubmissionCommentsHandler() {
		parent::AuthorHandler();
	}

	/**
	 * View editor decision comments.
	 */
	function viewEditorDecisionComments($args, $request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$articleId = (int) array_shift($args);

		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($request, $articleId);
		$authorSubmission =& $trackSubmissionHandler->submission;

		AuthorAction::viewEditorDecisionComments($authorSubmission);
	}

	/**
	 * View copyedit comments.
	 */
	function viewCopyeditComments($args, $request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$articleId = (int) array_shift($args);

		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($request, $articleId);
		$authorSubmission =& $trackSubmissionHandler->submission;

		AuthorAction::viewCopyeditComments($authorSubmission);
	}

	/**
	 * Post copyedit comment.
	 * @param $args array
	 * @param $request object
	 */
	function postCopyeditComment($args, $request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$articleId = (int) $request->getUserVar('articleId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($request, $articleId);
		$authorSubmission =& $trackSubmissionHandler->submission;

		if (AuthorAction::postCopyeditComment($authorSubmission, $emailComment, $request)) {
			AuthorAction::viewCopyeditComments($authorSubmission);
		}
	}

	/**
	 * View proofread comments.
	 */
	function viewProofreadComments($args, $request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$articleId = (int) array_shift($args);

		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($request, $articleId);
		$authorSubmission =& $trackSubmissionHandler->submission;
		AuthorAction::viewProofreadComments($authorSubmission);
	}

	/**
	 * Post proofread comment.
	 * @param $args array
	 * @param $request object
	 */
	function postProofreadComment($args, $request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$articleId = (int) $request->getUserVar('articleId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($request, $articleId);
		$authorSubmission =& $trackSubmissionHandler->submission;

		if (AuthorAction::postProofreadComment($authorSubmission, $emailComment, $request)) {
			AuthorAction::viewProofreadComments($authorSubmission);
		}
	}

	/**
	 * View layout comments.
	 */
	function viewLayoutComments($args, $request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$articleId = (int) array_shift($args);

		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($request, $articleId);
		$authorSubmission =& $trackSubmissionHandler->submission;
		AuthorAction::viewLayoutComments($authorSubmission);

	}

	/**
	 * Post layout comment.
	 */
	function postLayoutComment($args, $request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$articleId = (int) $request->getUserVar('articleId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($request, $articleId);
		$authorSubmission =& $trackSubmissionHandler->submission;
		if (AuthorAction::postLayoutComment($authorSubmission, $emailComment, $request)) {
			AuthorAction::viewLayoutComments($authorSubmission);
		}
	}

	/**
	 * Email an editor decision comment.
	 */
	function emailEditorDecisionComment($args, $request) {
		$this->setupTemplate($request, true);
				
		$articleId = (int) $request->getUserVar('articleId');
		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($request, $articleId);
		$authorSubmission =& $trackSubmissionHandler->submission;

		if (AuthorAction::emailEditorDecisionComment($authorSubmission, $request->getUserVar('send'), $request)) {
			$request->redirect(null, null, 'submissionReview', array($articleId));
		}
	}

	/**
	 * Edit comment.
	 */
	function editComment($args, $request) {
		$articleId = (int) array_shift($args);
		$commentId = array_shift($args);

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate();
		$comment =& $this->comment;

		$this->setupTemplate($request, true);
		
		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($request, $articleId);
		$authorSubmission =& $trackSubmissionHandler->submission;
		
		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			// Cannot edit an editor decision comment.
			$request->redirect(null, $request->getRequestedPage());
		}

		AuthorAction::editComment($authorSubmission, $comment);
	}

	/**
	 * Save comment.
	 * @param $args array
	 * @param $request object
	 */
	function saveComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$commentId = (int) $request->getUserVar('commentId');

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate();
		$comment =& $this->comment;
		
		$this->setupTemplate($request, true);
				
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($request, $articleId);
		$authorSubmission =& $trackSubmissionHandler->submission;

		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			// Cannot edit an editor decision comment.
			$request->redirect(null, $request->getRequestedPage());
		}

		AuthorAction::saveComment($authorSubmission, $comment, $emailComment, $request);

		// refresh the comment
		$articleCommentDao =& DAORegistry::getDAO('ArticleCommentDAO');
		$comment =& $articleCommentDao->getArticleCommentById($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			$request->redirect(null, null, 'viewEditorDecisionComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_COPYEDIT) {
			$request->redirect(null, null, 'viewCopyeditComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			$request->redirect(null, null, 'viewLayoutComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_PROOFREAD) {
			$request->redirect(null, null, 'viewProofreadComments', $articleId);
		}
	}

	/**
	 * Delete comment.
	 */
	function deleteComment($args, $request) {
		$articleId = (int) array_shift($args);
		$commentId = (int) array_shift($args);

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate();
		$comment =& $this->comment;
		
		$this->setupTemplate($request, true);

		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($request, $articleId);
		$authorSubmission =& $trackSubmissionHandler->submission;
		AuthorAction::deleteComment($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			$request->redirect(null, null, 'viewEditorDecisionComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_COPYEDIT) {
			$request->redirect(null, null, 'viewCopyeditComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			$request->redirect(null, null, 'viewLayoutComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_PROOFREAD) {
			$request->redirect(null, null, 'viewProofreadComments', $articleId);
		}
	}
}

?>
