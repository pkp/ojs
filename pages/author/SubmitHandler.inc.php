<?php

/**
 * SubmitHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.author
 *
 * Handle requests for journal author submit functions. 
 *
 * $Id$
 */

class SubmitHandler extends AuthorHandler {
	
	/**
	 * Display journal author article submission.
	 * Displays author index page if a valid step is not specified.
	 * @param $args array optional, if set the first paremeter is the step to display
	 */
	function submit($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		$step = isset($args[0]) ? (int) $args[0] : 0;
		
		if ($step >= 1 && $step <= 5) {
			
			$formClass = "AuthorSubmitStep{$step}Form";
			import("author.form.submit.$formClass");
			
			$setupForm = &new $formClass();
			$setupForm->initData();
			$setupForm->display();
		
		} else {
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->display('author/submit/index.tpl');
		}
	}
	
	
}
?>
