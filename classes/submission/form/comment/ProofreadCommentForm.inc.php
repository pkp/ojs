<?php

/**
 * ProofreadCommentForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.form
 *
 * ProofreadComment form.
 *
 * $Id$
 */
 
import("submission.form.comment.CommentForm");

class ProofreadCommentForm extends CommentForm {

	/**
	 * Constructor.
	 * @param $articleId int
	 */
	function ProofreadCommentForm($articleId, $roleId) {
		parent::CommentForm($articleId, COMMENT_TYPE_PROOFREAD, $roleId, $articleId);
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('commentAction', 'postProofreadComment');
		$templateMgr->assign('commentType', 'proofread');
		$templateMgr->assign('hiddenFormParams', 
			array(
				'articleId' => $this->articleId
			)
		);
		
		parent::display();
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		parent::readInputData();
	}
	
	/**
	 * Add the comment.
	 */
	function execute() {
		parent::execute();
	}
}

?>
