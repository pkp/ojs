<?php

/**
 * RegistrationHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.user
 *
 * Handle requests for user registration. 
 *
 * $Id$
 */

class RegistrationHandler extends UserHandler {

	/**
	 * Display registration form for new users.
	 */
	function register() {
		parent::setupTemplate(true);
		import('user.form.RegistrationForm');
		
		$regForm = &new RegistrationForm();
		$regForm->initData();
		$regForm->display();
	}
	
	/**
	 * Validate user registration information and register new user.
	 */
	function registerUser() {
		import('user.form.RegistrationForm');
		
		$regForm = &new RegistrationForm();
		$regForm->readInputData();
		
		if ($regForm->validate()) {
			$regForm->execute();
			Request::redirect('login');
			
		} else {
			parent::setupTemplate(true);
			$regForm->display();
		}
	}
	
}

?>
