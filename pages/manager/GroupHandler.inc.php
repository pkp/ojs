<?php

/**
 * GroupHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 *
 * Handle requests for editorial team management functions. 
 *
 * $Id$
 */

class GroupHandler extends ManagerHandler {

	/**
	 * Display a list of groups for the current journal.
	 */
	function groups() {
		parent::validate();
		GroupHandler::setupTemplate();

		$journal = &Request::getJournal();
		$rangeInfo = &Handler::getRangeInfo('groups');

		$groupDao =& DAORegistry::getDAO('GroupDAO');
		$groups =& $groupDao->getGroups($journal->getJournalId(), $rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('groups', $groups);
		$templateMgr->assign('boardEnabled', $journal->getSetting('boardEnabled'));
		$templateMgr->display('manager/groups/groups.tpl');
	}

	/**
	 * Delete a group.
	 * @param $args array first parameter is the ID of the group to delete
	 */
	function deleteGroup($args) {
		parent::validate();
		
		if (isset($args) && !empty($args)) {
			$journal = &Request::getJournal();
			$groupId = (int) $args[0];
		
			$groupDao = &DAORegistry::getDAO('GroupDAO');
			$group =& $groupDao->getGroup($groupId);

			// Ensure group belongs to this journal
			if ($group && $group->getJournalId() === $journal->getJournalId()) {
				$groupDao->deleteGroup($group);
			}
		}
		
		Request::redirect('manager/groups');
	}

	/**
	 * Display form to edit a group.
	 * @param $args array optional, first parameter is the ID of the group to edit
	 */
	function editGroup($args = array()) {
		parent::validate();

		$journal =& Request::getJournal();
		$groupId = isset($args[0]) ? (int) $args[0] : 0;

		$groupDao =& DAORegistry::getDAO('GroupDAO');
		$group =& $groupDao->getGroup($groupId);

		if (!$group || $group->getJournalId() === $journal->getJournalId()) {
			GroupHandler::setupTemplate($group, true);
			import('manager.form.GroupForm');

			$templateMgr = &TemplateManager::getManager();

			$templateMgr->assign('pageTitle',
				$group === null?
					'manager.groups.createTitle':
					'manager.groups.editTitle'
			);

			$groupForm = &new GroupForm($group);
			$groupForm->initData();
			$groupForm->display();
		
		} else {
			Request::redirect('manager/groups');
		}
	}

	/**
	 * Display form to create new group.
	 */
	function createGroup() {
		GroupHandler::editGroup();
	}

	/**
	 * Save changes to a group.
	 */
	function updateGroup() {
		parent::validate();
		
		import('manager.form.GroupForm');
		
		$journal =& Request::getJournal();
		$groupId = Request::getUserVar('groupId') == null ? null : (int) Request::getUserVar('groupId');

		$groupDao =& DAORegistry::getDAO('GroupDAO');
		$group =& $groupDao->getGroup($groupId);

		if (!$group || $group->getJournalId() === $journal->getJournalId()) {

			$groupForm =& new GroupForm($group);
			$groupForm->readInputData();
			
			if ($groupForm->validate()) {
				$groupForm->execute();
				Request::redirect('manager/groups');
			} else {
				GroupHandler::setupTemplate($group);

				$templateMgr = &TemplateManager::getManager();
				$templateMgr->append('pageHierarchy', array('manager/groups', 'manager.groups'));

				$templateMgr->assign('pageTitle',
					$group?
						'manager.groups.editTitle':
						'manager.groups.createTitle'
				);

				$groupForm->display();
			}
			
		} else {
			Request::redirect('manager/groups');
		}
	}
	
	/**
	 * View group membership.
	 */
	function groupMembership($args) {
		parent::validate();

		$journal = &Request::getJournal();
		$rangeInfo = &Handler::getRangeInfo('memberships');

		$groupId = isset($args[0])?(int)$args[0]:0;

		$groupDao =& DAORegistry::getDAO('GroupDAO');
		$group =& $groupDao->getGroup($groupId);

		if ($group && $group->getJournalId() === $journal->getJournalId()) {
			GroupHandler::setupTemplate($group, true);
			$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');
			$memberships =& $groupMembershipDao->getMemberships($group->getGroupId(), $rangeInfo);
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign_by_ref('memberships', $memberships);
			$templateMgr->assign_by_ref('group', $group);
			$templateMgr->display('manager/groups/memberships.tpl');
		} else {
			Request::redirect('manager/groups');
		}
	}

	/**
	 * Add group membership (or list users if none chosen).
	 */
	function addMembership($args) {
		parent::validate();

		$journal = &Request::getJournal();

		$groupId = isset($args[0])?(int)$args[0]:0;
		$userId = isset($args[1])?(int)$args[1]:0;

		$groupDao =& DAORegistry::getDAO('GroupDAO');
		$group =& $groupDao->getGroup($groupId);

		if ($group && $group->getJournalId() === $journal->getJournalId()) {
			$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');

			// A valid group has been chosen; if a user has been
			// selected, add them to the group. Otherwise list
			// users.
			$userDao =& DAORegistry::getDAO('UserDAO');
			$user =& $userDao->getUser($userId);
			
			if ($user) {
				// A valid user has been chosen. Add them to
				// the membership list and redirect.

				// Avoid duplicating memberships.
				$groupMembership =& $groupMembershipDao->getMembership($group->getGroupId(), $user->getUserId());

				if (!$groupMembership) {
					$groupMembership =& new GroupMembership();
					$groupMembership->setGroupId($group->getGroupId());
					$groupMembership->setUserId($user->getUserId());
					// For now, all memberships are displayed in About
					$groupMembership->setAboutDisplayed(true);
					$groupMembershipDao->insertMembership($groupMembership);
				}
				Request::redirect('manager/groupMembership/' . $group->getGroupId());
			} else {
				GroupHandler::setupTemplate($group, true);
				$searchType = null;
				$searchMatch = null;
				$search = $searchQuery = Request::getUserVar('search');
				$searchInitial = Request::getUserVar('searchInitial');
				if (isset($search)) {
					$searchType = Request::getUserVar('searchField');
					$searchMatch = Request::getUserVar('searchMatch');
					
				} else if (isset($searchInitial)) {
					$searchInitial = String::strtoupper($searchInitial);
					$searchType = USER_FIELD_INITIAL;
					$search = $searchInitial;
				}

				$roleDao =& DAORegistry::getDAO('RoleDAO');
				$users = $roleDao->getUsersByRoleId(null, $journal->getJournalId(), $searchType, $search, $searchMatch);
	
				$templateMgr = &TemplateManager::getManager();
	
				$templateMgr->assign('searchField', $searchType);
				$templateMgr->assign('searchMatch', $searchMatch);
				$templateMgr->assign('search', $searchQuery);
				$templateMgr->assign('searchInitial', $searchInitial);
		
				$templateMgr->assign_by_ref('users', $users);
				$templateMgr->assign('fieldOptions', Array(
					USER_FIELD_FIRSTNAME => 'user.firstName',
					USER_FIELD_LASTNAME => 'user.lastName',
					USER_FIELD_USERNAME => 'user.username',
					USER_FIELD_EMAIL => 'user.email'
				));
				$templateMgr->assign_by_ref('group', $group);

				$templateMgr->display('manager/groups/selectUser.tpl');
			}
		} else {
			Request::redirect('manager/groups');
		}
	}

	/**
	 * Delete group membership.
	 */
	function deleteMembership($args) {
		parent::validate();

		$journal = &Request::getJournal();

		$groupId = isset($args[0])?(int)$args[0]:0;
		$userId = isset($args[1])?(int)$args[1]:0;

		$groupDao =& DAORegistry::getDAO('GroupDAO');
		$group =& $groupDao->getGroup($groupId);

		if ($group && $group->getJournalId() === $journal->getJournalId()) {
			$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');
			$groupMembershipDao->deleteMembershipById($group->getGroupId(), $userId);
		}
		Request::redirect('manager/groupMembership/' . $group->getGroupId());
	}

	function setBoardEnabled($args) {
		parent::validate();
		$journal = &Request::getJournal();
		$boardEnabled = Request::getUserVar('boardEnabled')==1?true:false;
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$journalSettingsDao->updateSetting($journal->getJournalId(), 'boardEnabled', $boardEnabled);
		Request::redirect('manager/groups');
	}

	function setupTemplate($group = null, $subclass = false) {
		parent::setupTemplate(true);
		$templateMgr = &TemplateManager::getManager();
		if ($subclass) {
			$templateMgr->append('pageHierarchy', array('manager/groups', 'manager.groups'));
		}
		if ($group) {
			$templateMgr->append('pageHierarchy', array('manager/editGroup/' . $group->getGroupId(), $group->getTitle(), true));
		}
	}
}

?>
