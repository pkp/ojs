<?php

/**
 * @defgroup controllers_tab_user
 */

/**
 * @file controllers/tab/user/ProfileTabHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ProfileTabHandler
 * @ingroup controllers_tab_user
 *
 * @brief Handle requests for profile tab operations.
 */


import('classes.handler.Handler');
import('lib.pkp.classes.core.JSONMessage');

class ProfileTabHandler extends Handler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		// User must be logged in
		import('lib.pkp.classes.security.authorization.UserRequiredPolicy');
		$this->addPolicy(new UserRequiredPolicy($request));

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Display form to edit user's identity.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON-formatted response
	 */
	function identity($args, $request) {
		$this->setupTemplate($request);
		import('lib.pkp.classes.user.form.IdentityForm');
		$identityForm = new IdentityForm($request->getUser());
		$identityForm->initData($request);
		return new JSONMessage(true, $identityForm->fetch($request));
	}

	/**
	 * Validate and save changes to user's identity info.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON-formatted response
	 */
	function saveIdentity($args, $request) {
		$this->setupTemplate($request);

		import('lib.pkp.classes.user.form.IdentityForm');
		$identityForm = new IdentityForm($request->getUser());
		$identityForm->readInputData();
		if ($identityForm->validate()) {
			$identityForm->execute($request);
			$notificationMgr = new NotificationManager();
			$notificationMgr->createTrivialNotification($request->getUser()->getId());
			return new JSONMessage(true);
		}
		return new JSONMessage(false, $identityForm->fetch($request));
	}

	/**
	 * Display form to edit user's contact information.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON-formatted response
	 */
	function contact($args, $request) {
		$this->setupTemplate($request);
		import('lib.pkp.classes.user.form.ContactForm');
		$contactForm = new ContactForm($request->getUser());
		$contactForm->initData($request);
		return new JSONMessage(true, $contactForm->fetch($request));
	}

	/**
	 * Validate and save changes to user's contact info.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON-formatted response
	 */
	function saveContact($args, $request) {
		$this->setupTemplate($request);

		import('lib.pkp.classes.user.form.ContactForm');
		$contactForm = new ContactForm($request->getUser());
		$contactForm->readInputData();
		if ($contactForm->validate()) {
			$contactForm->execute($request);
			$notificationMgr = new NotificationManager();
			$notificationMgr->createTrivialNotification($request->getUser()->getId());
			return new JSONMessage(true);
		}
		return new JSONMessage(false, $contactForm->fetch($request));
	}

	/**
	 * Display form to edit user's roles information.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON-formatted response
	 */
	function roles($args, $request) {
		$this->setupTemplate($request);
		import('lib.pkp.classes.user.form.RolesForm');
		$rolesForm = new RolesForm($request->getUser());
		$rolesForm->initData($request);
		return new JSONMessage(true, $rolesForm->fetch($request));
	}

	/**
	 * Validate and save changes to user's roles info.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON-formatted response
	 */
	function saveRoles($args, $request) {
		$this->setupTemplate($request);

		import('lib.pkp.classes.user.form.RolesForm');
		$rolesForm = new RolesForm($request->getUser());
		$rolesForm->readInputData();
		if ($rolesForm->validate()) {
			$rolesForm->execute($request);
			$notificationMgr = new NotificationManager();
			$notificationMgr->createTrivialNotification($request->getUser()->getId());
			return new JSONMessage(true);
		}
		return new JSONMessage(false, $rolesForm->fetch($request));
	}

	/**
	 * Display form to edit user's publicProfile information.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON-formatted response
	 */
	function publicProfile($args, $request) {
		$this->setupTemplate($request);
		import('lib.pkp.classes.user.form.PublicProfileForm');
		$publicProfileForm = new PublicProfileForm($request->getUser());
		$publicProfileForm->initData($request);
		return new JSONMessage(true, $publicProfileForm->fetch($request));
	}

	/**
	 * Upload a public profile image.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON-formatted response
	 */
	function uploadProfileImage($args, $request) {
		import('lib.pkp.classes.user.form.PublicProfileForm');
		$publicProfileForm = new PublicProfileForm($request->getUser());
		$result = $publicProfileForm->uploadProfileImage();
		if ($result) {
			return $request->redirectUrlJson($request->getDispatcher()->url($request, ROUTE_PAGE, null, 'user', 'profile', null, array('uniq' => uniqid()), 'publicProfile'));
		} else {
			return new JSONMessage(false, __('common.uploadFailed'));
		}
	}

	/**
	 * Remove a public profile image.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function deleteProfileImage($args, $request) {
		import('lib.pkp.classes.user.form.PublicProfileForm');
		$publicProfileForm = new PublicProfileForm($request->getUser());
		$publicProfileForm->deleteProfileImage();
		$request->redirectUrl($request->getDispatcher()->url($request, ROUTE_PAGE, null, 'user', 'profile', null, array('uniq' => uniqid()), 'publicProfile'));
	}

	/**
	 * Validate and save changes to user's publicProfile info.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON-formatted response
	 */
	function savePublicProfile($args, $request) {
		$this->setupTemplate($request);

		import('lib.pkp.classes.user.form.PublicProfileForm');
		$publicProfileForm = new PublicProfileForm($request->getUser());
		$publicProfileForm->readInputData();
		if ($publicProfileForm->validate()) {
			$publicProfileForm->execute($request);
			$notificationMgr = new NotificationManager();
			$notificationMgr->createTrivialNotification($request->getUser()->getId());
			return new JSONMessage(true);
		}
		return new JSONMessage(true, $publicProfileForm->fetch($request));
	}

	/**
	 * Display form to change user's password.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON-formatted response
	 */
	function changePassword($args, $request) {
		$this->setupTemplate($request);
		import('lib.pkp.classes.user.form.ChangePasswordForm');
		$passwordForm = new ChangePasswordForm($request->getUser(), $request->getSite());
		$passwordForm->initData($args, $request);
		return new JSONMessage(true, $passwordForm->fetch($request));
	}

	/**
	 * Save user's new password.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON-formatted response
	 */
	function savePassword($args, $request) {
		$this->setupTemplate($request);
		import('lib.pkp.classes.user.form.ChangePasswordForm');
		$passwordForm = new ChangePasswordForm($request->getUser(), $request->getSite());
		$passwordForm->readInputData();

		if ($passwordForm->validate()) {
			$passwordForm->execute($request);
			$notificationMgr = new NotificationManager();
			$notificationMgr->createTrivialNotification($request->getUser()->getId());
			return new JSONMessage(true);

		}
		return new JSONMessage(true, $passwordForm->fetch($request));
	}

	/**
	 * Fetch notifications tab content.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON-formatted response
	 */
	function notificationSettings($args, $request) {
		$this->setupTemplate($request);

		$user = $request->getUser();
		import('classes.notification.form.NotificationSettingsForm');
		$notificationSettingsForm = new NotificationSettingsForm();
		return new JSONMessage(true, $notificationSettingsForm->fetch($request));
	}

	/**
	 * Save user notification settings.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON-formatted response
	 */
	function saveNotificationSettings($args, $request) {
		$this->setupTemplate($request);

		import('classes.notification.form.NotificationSettingsForm');

		$notificationSettingsForm = new NotificationSettingsForm();
		$notificationSettingsForm->readInputData();

		$json = new JSONMessage();
		if ($notificationSettingsForm->validate()) {
			$notificationSettingsForm->execute($request);
			$notificationMgr = new NotificationManager();
			$notificationMgr->createTrivialNotification($request->getUser()->getId());
		} else {
			$json->setStatus(false);
		}

		return $json;
	}
}

?>
