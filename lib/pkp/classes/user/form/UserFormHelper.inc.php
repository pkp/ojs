<?php

/**
 * @file classes/user/form/UserFormHelper.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserFormHelper
 * @ingroup user_form
 *
 * @brief Helper functions for shared user form concerns.
 */

class UserFormHelper {
	/**
	 * Constructor
	 */
	function __construct() {
	}

	/**
	 * Assign role selection content to the template manager.
	 * @param $templateMgr PKPTemplateManager
	 * @param $request PKPRequest
	 */
	function assignRoleContent($templateMgr, $request) {
		// Need the count in order to determine whether to display
		// extras-on-demand for role selection in other contexts.
		$contextDao = Application::getContextDAO();
		$contexts = $contextDao->getAll(true)->toArray();
		$contextsWithUserRegistration = array();
		foreach ($contexts as $context) {
			if (!$context->getSetting('disableUserReg')) {
				$contextsWithUserRegistration[] = $context;
			}
		}
		$templateMgr->assign(array(
			'contexts' => $contexts,
			'showOtherContexts' => !$request->getContext() || count($contextsWithUserRegistration)>1,
		));

		// Expose potential self-registration user groups to template
		$authorUserGroups = $reviewerUserGroups = $readerUserGroups = array();
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		foreach ($contexts as $context) {
			$reviewerUserGroups[$context->getId()] = $userGroupDao->getByRoleId($context->getId(), ROLE_ID_REVIEWER)->toArray();
			$authorUserGroups[$context->getId()] = $userGroupDao->getByRoleId($context->getId(), ROLE_ID_AUTHOR)->toArray();
			$readerUserGroups[$context->getId()] = $userGroupDao->getByRoleId($context->getId(), ROLE_ID_READER)->toArray();
		}
		$templateMgr->assign(array(
			'reviewerUserGroups' => $reviewerUserGroups,
			'authorUserGroups' => $authorUserGroups,
			'readerUserGroups' => $readerUserGroups,
		));
	}

	/**
	 * Save role elements of an executed user form.
	 * @param $form Form The form from which to fetch elements
	 * @param $user User The current user
	 */
	function saveRoleContent($form, $user) {
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$contextDao = Application::getContextDAO();
		$contexts = $contextDao->getAll(true);
		while ($context = $contexts->next()) {
			foreach (array(
				array(
					'roleId' => ROLE_ID_REVIEWER,
					'formElement' => 'reviewerGroup'
				),
				array(
					'roleId' => ROLE_ID_AUTHOR,
					'formElement' => 'authorGroup'
				),
				array(
					'roleId' => ROLE_ID_READER,
					'formElement' => 'readerGroup'
				),
			) as $groupData) {
				$groupFormData = (array) $form->getData($groupData['formElement']);
				$userGroups = $userGroupDao->getByRoleId($context->getId(), $groupData['roleId']);
				while ($userGroup = $userGroups->next()) {
					if (!$userGroup->getPermitSelfRegistration()) continue;

					$groupId = $userGroup->getId();
					$inGroup = $userGroupDao->userInGroup($user->getId(), $groupId);
					if (!$inGroup && array_key_exists($groupId, $groupFormData)) {
						$userGroupDao->assignUserToGroup($user->getId(), $groupId, $context->getId());
					} elseif ($inGroup && !array_key_exists($groupId, $groupFormData)) {
						$userGroupDao->removeUserFromGroup($user->getId(), $groupId, $context->getId());
					}
				}
			}
		}
	}
}

?>
