<?php

/**
 * CopyeditCommentForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.form
 *
 * CopyeditComment form.
 *
 * $Id$
 */
 
import("submission.form.comment.CommentForm");

class CopyeditCommentForm extends CommentForm {

	/**
	 * Constructor.
	 * @param $articleId int
	 */
	function CopyeditCommentForm($articleId, $roleId) {
		parent::CommentForm($articleId, COMMENT_TYPE_COPYEDIT, $roleId, $articleId);
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('commentAction', 'postCopyeditComment');
		$templateMgr->assign('commentType', 'copyedit');
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
