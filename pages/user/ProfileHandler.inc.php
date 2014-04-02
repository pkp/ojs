<?php

/**
 * @file pages/user/ProfileHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ProfileHandler
 * @ingroup pages_user
 *
 * @brief Handle requests for modifying user profiles.
 */

import('pages.user.UserHandler');

class ProfileHandler extends UserHandler {
	/**
	 * Constructor
	 **/
	function ProfileHandler() {
		parent::UserHandler();
	}

	/**
	 * Display form to edit user's profile.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function profile($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		import('classes.user.form.ProfileForm');

		$profileForm = new ProfileForm();
		if ($profileForm->isLocaleResubmit()) {
			$profileForm->readInputData();
		} else {
			$profileForm->initData($args, $request);
		}
		$profileForm->display();
	}

	/**
	 * Validate and save changes to user's profile.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function saveProfile($args, &$request) {
		$this->validate();
		$this->setupTemplate($request);
		$dataModified = false;

		import('classes.user.form.ProfileForm');

		$profileForm = new ProfileForm();
		$profileForm->readInputData();

		if ($request->getUserVar('uploadProfileImage')) {
			if (!$profileForm->uploadProfileImage()) {
				$profileForm->addError('profileImage', __('user.profile.form.profileImageInvalid'));
			}
			$dataModified = true;
		} else if ($request->getUserVar('deleteProfileImage')) {
			$profileForm->deleteProfileImage();
			$dataModified = true;
		}

		if (!$dataModified && $profileForm->validate()) {
			$profileForm->execute();
			$request->redirect(null, $request->getRequestedPage());

		} else {
			$profileForm->display();
		}
	}

	/**
	 * Display form to change user's password.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function changePassword($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		import('classes.user.form.ChangePasswordForm');

		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$passwordForm = new ChangePasswordForm();
		} else {
			$passwordForm =& new ChangePasswordForm();
		}
		$passwordForm->initData();
		$passwordForm->display();
	}

	/**
	 * Save user's new password.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function savePassword($args, &$request) {
		$this->validate();

		import('classes.user.form.ChangePasswordForm');

		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$passwordForm = new ChangePasswordForm();
		} else {
			$passwordForm =& new ChangePasswordForm();
		}
		$passwordForm->readInputData();

		$this->setupTemplate($request, true);
		if ($passwordForm->validate()) {
			$passwordForm->execute();
			$request->redirect(null, $request->getRequestedPage());

		} else {
			$passwordForm->display();
		}
	}
}

?>
