<?php

/**
 * @file ProfileHandler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ProfileHandler
 * @ingroup pages_user
 *
 * @brief Handle requests for modifying user profiles. 
 */

// $Id$


class ProfileHandler extends UserHandler {

	/**
	 * Display form to edit user's profile.
	 */
	function profile() {
		ProfileHandler::validate();
		ProfileHandler::setupTemplate(true);

		import('user.form.ProfileForm');

		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$profileForm =& new ProfileForm();
		if ($profileForm->isLocaleResubmit()) {
			$profileForm->readInputData();
		} else {
			$profileForm->initData();
		}
		$profileForm->display();
	}

	/**
	 * Validate and save changes to user's profile.
	 */
	function saveProfile() {
		ProfileHandler::validate();
		ProfileHandler::setupTemplate();
		$dataModified = false;

		import('user.form.ProfileForm');

		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$profileForm =& new ProfileForm();
		$profileForm->readInputData();

		if (Request::getUserVar('uploadProfileImage')) {
			if (!$profileForm->uploadProfileImage()) {
				$profileForm->addError('profileImage', Locale::translate('user.profile.form.profileImageInvalid'));
			}
			$dataModified = true;
		} else if (Request::getUserVar('deleteProfileImage')) {
			$profileForm->deleteProfileImage();
			$dataModified = true;
		}

		if (!$dataModified && $profileForm->validate()) {
			$profileForm->execute();
			Request::redirect(null, Request::getRequestedPage());

		} else {
			ProfileHandler::setupTemplate(true);
			$profileForm->display();
		}
	}

	/**
	 * Display form to change user's password.
	 */
	function changePassword() {
		ProfileHandler::validate();
		ProfileHandler::setupTemplate(true);

		import('user.form.ChangePasswordForm');

		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$passwordForm =& new ChangePasswordForm();
		$passwordForm->initData();
		$passwordForm->display();
	}

	/**
	 * Save user's new password.
	 */
	function savePassword() {
		ProfileHandler::validate();

		import('user.form.ChangePasswordForm');

		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$passwordForm =& new ChangePasswordForm();
		$passwordForm->readInputData();

		if ($passwordForm->validate()) {
			$passwordForm->execute();
			Request::redirect(null, Request::getRequestedPage());

		} else {
			ProfileHandler::setupTemplate(true);
			$passwordForm->display();
		}
	}

}

?>
