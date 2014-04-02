<?php

/**
 * @file pages/manager/GroupHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GroupHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for editorial team management functions.
 */

import('pages.manager.ManagerHandler');

class GroupHandler extends ManagerHandler {
	/** group associated with the request **/
	var $group;

	/** groupMembership associated with the request **/
	var $groupMembership;

	/** user associated with the request **/
	var $user;

	/**
	 * Constructor
	 **/
	function GroupHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display a list of groups for the current journal.
	 */
	function groups() {
		$this->validate();
		$this->setupTemplate();

		$journal =& Request::getJournal();

		$rangeInfo =& $this->getRangeInfo('groups');

		$groupDao =& DAORegistry::getDAO('GroupDAO');
		$groups =& $groupDao->getGroups(ASSOC_TYPE_JOURNAL, $journal->getId(), null, $rangeInfo);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->addJavaScript('lib/pkp/js/lib/jquery/plugins/jquery.tablednd.js');
		$templateMgr->addJavaScript('lib/pkp/js/functions/tablednd.js');
		$templateMgr->assign_by_ref('groups', $groups);
		$templateMgr->assign('boardEnabled', $journal->getSetting('boardEnabled'));
		$templateMgr->display('manager/groups/groups.tpl');
	}

	/**
	 * Delete a group.
	 * @param $args array first parameter is the ID of the group to delete
	 */
	function deleteGroup($args) {
		$groupId = isset($args[0])?(int)$args[0]:0;
		$this->validate($groupId);

		$group =& $this->group;

		$groupDao =& DAORegistry::getDAO('GroupDAO');
		$groupDao->deleteObject($group);
		$groupDao->resequenceGroups($group->getAssocType(), $group->getAssocId());

		Request::redirect(null, null, 'groups');
	}

	/**
	 * Change the sequence of a group.
	 */
	function moveGroup() {
		$groupId = (int) Request::getUserVar('id');
		$this->validate($groupId);

		$group =& $this->group;
		$groupDao =& DAORegistry::getDAO('GroupDAO');
		$direction = Request::getUserVar('d');

		if ($direction != null) {
			// moving with up or down arrow
			$group->setSequence($group->getSequence() + ($direction == 'u' ? -1.5 : 1.5));

		} else {
			// Dragging and dropping
			$prevId = Request::getUserVar('prevId');
			if ($prevId == null)
				$prevSeq = 0;
			else {
				$journal =& Request::getJournal();
				$prevGroup =& $groupDao->getById($prevId, ASSOC_TYPE_JOURNAL, $journal->getId());
				$prevSeq = $prevGroup->getSequence();
			}

			$group->setSequence($prevSeq + .5);
		}


		$groupDao->updateObject($group);
		$groupDao->resequenceGroups($group->getAssocType(), $group->getAssocId());

		// Moving up or down with the arrows requires a page reload.
		// In the case of a drag and drop move, the display has been
		// updated on the client side, so no reload is necessary.
		if ($direction != null) {
			Request::redirect(null, null, 'groups');
		}
	}

	/**
	 * Display form to edit a group.
	 * @param $args array optional, first parameter is the ID of the group to edit
	 */
	function editGroup($args = array()) {
		$groupId = isset($args[0])?(int)$args[0]:null;
		$this->validate($groupId);
		$journal =& Request::getJournal();

		if ($groupId !== null) {
			$groupDao =& DAORegistry::getDAO('GroupDAO');
			$group =& $groupDao->getById($groupId, ASSOC_TYPE_JOURNAL, $journal->getId());
			if (!$group) {
				Request::redirect(null, null, 'groups');
			}
		} else $group = null;

		$this->setupTemplate($group, true);
		import('classes.manager.form.GroupForm');

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign('pageTitle',
			$group === null?
				'manager.groups.createTitle':
				'manager.groups.editTitle'
		);

		$groupForm = new GroupForm($group);
		if ($groupForm->isLocaleResubmit()) {
			$groupForm->readInputData();
		} else {
			$groupForm->initData();
		}
		$groupForm->display();
	}

	/**
	 * Display form to create new group.
	 */
	function createGroup($args) {
		$this->editGroup($args);
	}

	/**
	 * Save changes to a group.
	 */
	function updateGroup() {
		$groupId = Request::getUserVar('groupId') === null? null : (int) Request::getUserVar('groupId');
		if ($groupId === null) {
			$this->validate();
			$group = null;
		} else {
			$this->validate($groupId);
			$group =& $this->group;
		}
		$this->setupTemplate($group);

		import('classes.manager.form.GroupForm');

		$groupForm = new GroupForm($group);
		$groupForm->readInputData();

		if ($groupForm->validate()) {
			$groupForm->execute();
			Request::redirect(null, null, 'groups');
		} else {

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, 'manager', 'groups'), 'manager.groups'));

			$templateMgr->assign('pageTitle',
				$group?
					'manager.groups.editTitle':
					'manager.groups.createTitle'
			);

			$groupForm->display();
		}
	}

	/**
	 * View group membership.
	 */
	function groupMembership($args) {
		$groupId = isset($args[0])?(int)$args[0]:0;
		$this->validate($groupId);
		$group =& $this->group;

		$rangeInfo =& $this->getRangeInfo('memberships');

		$this->setupTemplate($group, true);
		$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');
		$memberships =& $groupMembershipDao->getMemberships($group->getId(), $rangeInfo);
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->addJavaScript('lib/pkp/js/lib/jquery/plugins/jquery.tablednd.js');
		$templateMgr->addJavaScript('lib/pkp/js/functions/tablednd.js');
		$templateMgr->assign_by_ref('memberships', $memberships);
		$templateMgr->assign_by_ref('group', $group);
		$templateMgr->display('manager/groups/memberships.tpl');
	}

	/**
	 * Add group membership (or list users if none chosen).
	 */
	function addMembership($args) {
		$groupId = isset($args[0])?(int)$args[0]:0;
		$userId = isset($args[1])?(int)$args[1]:null;

		$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');

		// If a user has been selected, add them to the group.
		// Otherwise list users.
		if ($userId !== null) {
			$this->validate($groupId, $userId);
			$group =& $this->group;
			$user =& $this->user;
			// A valid user has been chosen. Add them to
			// the membership list and redirect.

			// Avoid duplicating memberships.
			$groupMembership =& $groupMembershipDao->getMembership($group->getId(), $user->getId());

			if (!$groupMembership) {
				$groupMembership = new GroupMembership();
				$groupMembership->setGroupId($group->getId());
				$groupMembership->setUserId($user->getId());
				// For now, all memberships are displayed in About
				$groupMembership->setAboutDisplayed(true);
				$groupMembershipDao->insertMembership($groupMembership);
			}
			Request::redirect(null, null, 'groupMembership', $group->getId());
		} else {
			$this->validate($groupId);
			$group =& $this->group;
			$this->setupTemplate($group, true);

			$searchType = null;
			$searchMatch = null;
			$search = $searchQuery = Request::getUserVar('search');
			$searchInitial = Request::getUserVar('searchInitial');
			if (!empty($search)) {
				$searchType = Request::getUserVar('searchField');
				$searchMatch = Request::getUserVar('searchMatch');

			} elseif (!empty($searchInitial)) {
				$searchInitial = String::strtoupper($searchInitial);
				$searchType = USER_FIELD_INITIAL;
				$search = $searchInitial;
			}

			$roleDao =& DAORegistry::getDAO('RoleDAO');
			$journal =& Request::getJournal();
			$users = $roleDao->getUsersByRoleId(null, $journal->getId(), $searchType, $search, $searchMatch);

			$templateMgr =& TemplateManager::getManager();

			$templateMgr->assign('searchField', $searchType);
			$templateMgr->assign('searchMatch', $searchMatch);
			$templateMgr->assign('search', $searchQuery);
			$templateMgr->assign('searchInitial', Request::getUserVar('searchInitial'));

			$templateMgr->assign_by_ref('users', $users);
			$templateMgr->assign('fieldOptions', Array(
				USER_FIELD_FIRSTNAME => 'user.firstName',
				USER_FIELD_LASTNAME => 'user.lastName',
				USER_FIELD_USERNAME => 'user.username',
				USER_FIELD_EMAIL => 'user.email'
			));
			$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));
			$templateMgr->assign_by_ref('group', $group);

			$templateMgr->display('manager/groups/selectUser.tpl');
		}
	}

	/**
	 * Delete group membership.
	 */
	function deleteMembership($args) {
		$groupId = isset($args[0])?(int)$args[0]:0;
		$userId = isset($args[1])?(int)$args[1]:0;

		$this->validate($groupId, $userId, true);
		$group =& $this->group;
		$user =& $this->user;
		$groupMembership =& $this->groupMembership;

		$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');
		$groupMembershipDao->deleteMembershipById($group->getId(), $user->getId());
		$groupMembershipDao->resequenceMemberships($group->getId());

		Request::redirect(null, null, 'groupMembership', $group->getId());
	}

	/**
	 * Change the sequence of a group membership.
	 */
	function moveMembership($args) {
		$groupId = isset($args[0])?(int)$args[0]:0;
		$userId = (int) Request::getUserVar('id');
		$this->validate($groupId, $userId, true);
		$group =& $this->group;
		$groupMembership =& $this->groupMembership;

		$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');
		$direction = Request::getUserVar('d');
		if ($direction != null) {
			// moving with up or down arrow
			$groupMembership->setSequence($groupMembership->getSequence() + ($direction == 'u' ? -1.5 : 1.5));
		} else {
			// drag and drop
			$prevId = Request::getUserVar('prevId');
			if ($prevId == null)
				$prevSeq = 0;
			else {
				$prevMembership =& $groupMembershipDao->getMembership($groupId, $prevId);
				$prevSeq = $prevMembership->getSequence();
			}

			$groupMembership->setSequence($prevSeq + .5);
		}
		$groupMembershipDao->updateObject($groupMembership);
		$groupMembershipDao->resequenceMemberships($group->getId());

		// Moving up or down with the arrows requires a page reload.
		// In the case of a drag and drop move, the display has been
		// updated on the client side, so no reload is necessary.
		if ($direction != null) {
			Request::redirect(null, null, 'groupMembership', $group->getId());
		}
	}

	function setBoardEnabled($args) {
		$this->validate();
		$journal =& Request::getJournal();
		$boardEnabled = Request::getUserVar('boardEnabled')==1?true:false;
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$journalSettingsDao->updateSetting($journal->getId(), 'boardEnabled', $boardEnabled);
		Request::redirect(null, null, 'groups');
	}

	function setupTemplate($group = null, $subclass = false) {
		parent::setupTemplate(true);
		$templateMgr =& TemplateManager::getManager();
		if ($subclass) {
			$templateMgr->append('pageHierarchy', array(Request::url(null, 'manager', 'groups'), 'manager.groups'));
		}
		if ($group) {
			$templateMgr->append('pageHierarchy', array(Request::url(null, 'manager', 'editGroup', $group->getId()), $group->getLocalizedTitle(), true));
		}
		$templateMgr->assign('helpTopicId', 'journal.managementPages.groups');
	}

	/**
	 * Validate the request. If a group ID is supplied, the group object
	 * will be fetched and validated against the current journal. If,
	 * additionally, the user ID is supplied, the user and membership
	 * objects will be validated and fetched.
	 * @param $groupId int optional
	 * @param $userId int optional
	 * @param $fetchMembership boolean Whether or not to fetch membership object as last element of return array, redirecting if it doesn't exist; default false
	 * @return array [$journal] iff $groupId is null, [$journal, $group] iff $userId is null and $groupId is supplied, and [$journal, $group, $user] iff $userId and $groupId are both supplied. $fetchMembership===true will append membership info to the last case, redirecting if it doesn't exist.
	 */
	function validate($groupId = null, $userId = null, $fetchMembership = false) {
		parent::validate();

		$journal =& Request::getJournal();

		$passedValidation = true;

		if ($groupId !== null) {
			$groupDao =& DAORegistry::getDAO('GroupDAO');
			$group =& $groupDao->getById($groupId, ASSOC_TYPE_JOURNAL, $journal->getId());

			if (!$group) $passedValidation = false;
			else $this->group =& $group;

			if ($userId !== null) {
				$userDao =& DAORegistry::getDAO('UserDAO');
				$user =& $userDao->getUser($userId);

				if (!$user) $passedValidation = false;
				else $this->user =& $user;

				if ($fetchMembership === true) {
					$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');
					$groupMembership =& $groupMembershipDao->getMembership($groupId, $userId);
					if (!$groupMembership) $validationPassed = false;
					else $this->groupMembership =& $groupMembership;
				}
			}
		}
		if (!$passedValidation) Request::redirect(null, null, 'groups');
		return true;
	}
}

?>
