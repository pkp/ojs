<?php

/**
 * @file classes/user/form/ProfileForm.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ProfileForm
 * @ingroup user_form
 *
 * @brief Form to edit user profile.
 */

import('lib.pkp.classes.user.form.PKPProfileForm');

class ProfileForm extends PKPProfileForm {

	/**
	 * Constructor.
	 */
	function ProfileForm($user) {
		parent::PKPProfileForm('user/profile.tpl', $user);
	}

	/**
	 * Display the form.
	 */
	function display($request) {
		$user =& $request->getUser();

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$userSettingsDao =& DAORegistry::getDAO('UserSettingsDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$journals =& $journalDao->getJournals(true);
		$journals =& $journals->toArray();

		$templateMgr =& TemplateManager::getManager($request);

		foreach ($journals as $thisJournal) {
			if ($thisJournal->getSetting('publishingMode') == PUBLISHING_MODE_SUBSCRIPTION && $thisJournal->getSetting('enableOpenAccessNotification')) {
				$templateMgr->assign('displayOpenAccessNotification', true);
				$templateMgr->assign_by_ref('user', $user);
				break;
			}
		}

		$journals = $journalDao->getJournals(true);
		$journals = $journals->toArray();

		$templateMgr->assign_by_ref('journals', $journals);
		$templateMgr->assign_by_ref('journalNotifications', $journalNotifications);
		$templateMgr->assign('helpTopicId', 'user.registerAndProfile');

		$journal =& $request->getJournal();
		if ($journal) {
			$roleDao =& DAORegistry::getDAO('RoleDAO');
			$roles =& $roleDao->getRolesByUserId($user->getId(), $journal->getId());
			$roleNames = array();
			foreach ($roles as $role) $roleNames[$role->getPath()] = $role->getRoleName();
			$templateMgr->assign('allowRegReviewer', $journal->getSetting('allowRegReviewer'));
			$templateMgr->assign('allowRegAuthor', $journal->getSetting('allowRegAuthor'));
			$templateMgr->assign('allowRegReader', $journal->getSetting('allowRegReader'));
			$templateMgr->assign('roles', $roleNames);
		}

		parent::display($request);
	}

	function initData() {
		parent::initData();
		$this->_data += array(
			'isAuthor' => Validation::isAuthor(),
			'isReader' => Validation::isReader(),
			'isReviewer' => Validation::isReviewer(),
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		parent::readInputData();
		$this->readUserVars(array(
			'readerRole',
			'authorRole',
			'reviewerRole'
		));
	}

	/**
	 * Save profile settings.
	 */
	function execute($request) {
		$user = $request->getUser();

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$journalDao =& DAORegistry::getDAO('JournalDAO');

		// Roles
		$journal =& Request::getJournal();
		if ($journal) {
			$role = new Role();
			$role->setUserId($user->getId());
			$role->setJournalId($journal->getId());
			if ($journal->getSetting('allowRegReviewer')) {
				$role->setRoleId(ROLE_ID_REVIEWER);
				$hasRole = Validation::isReviewer();
				$wantsRole = Request::getUserVar('reviewerRole');
				if ($hasRole && !$wantsRole) $roleDao->deleteRole($role);
				if (!$hasRole && $wantsRole) $roleDao->insertRole($role);
			}
			if ($journal->getSetting('allowRegAuthor')) {
				$role->setRoleId(ROLE_ID_AUTHOR);
				$hasRole = Validation::isAuthor();
				$wantsRole = Request::getUserVar('authorRole');
				if ($hasRole && !$wantsRole) $roleDao->deleteRole($role);
				if (!$hasRole && $wantsRole) $roleDao->insertRole($role);
			}
			if ($journal->getSetting('allowRegReader')) {
				$role->setRoleId(ROLE_ID_READER);
				$hasRole = Validation::isReader();
				$wantsRole = Request::getUserVar('readerRole');
				if ($hasRole && !$wantsRole) $roleDao->deleteRole($role);
				if (!$hasRole && $wantsRole) $roleDao->insertRole($role);
			}
		}

		$openAccessNotify = Request::getUserVar('openAccessNotify');

		$userSettingsDao =& DAORegistry::getDAO('UserSettingsDAO');
		$journals =& $journalDao->getJournals(true);
		$journals =& $journals->toArray();

		foreach ($journals as $thisJournal) {
			if ($thisJournal->getSetting('publishingMode') == PUBLISHING_MODE_SUBSCRIPTION && $thisJournal->getSetting('enableOpenAccessNotification')) {
				$currentlyReceives = $user->getSetting('openAccessNotification', $thisJournal->getId());
				$shouldReceive = !empty($openAccessNotify) && in_array($thisJournal->getId(), $openAccessNotify);
				if ($currentlyReceives != $shouldReceive) {
					$userSettingsDao->updateSetting($user->getId(), 'openAccessNotification', $shouldReceive, 'bool', $thisJournal->getId());
				}
			}
		}

		parent::execute($request);
	}
}

?>
