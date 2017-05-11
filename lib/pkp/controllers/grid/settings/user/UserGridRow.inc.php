<?php

/**
 * @file controllers/grid/settings/user/UserGridRow.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserGridRow
 * @ingroup controllers_grid_settings_user
 *
 * @brief User grid row definition
 */

import('lib.pkp.classes.controllers.grid.GridRow');
import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
import('lib.pkp.classes.linkAction.request.RedirectConfirmationModal');
import('lib.pkp.classes.linkAction.request.JsEventConfirmationModal');

class UserGridRow extends GridRow {
	/** the user id of the old user to remove when merging users. */
	var $_oldUserId;

	/**
	 * Constructor
	 */
	function __construct($oldUserId = null) {
		$this->_oldUserId = $oldUserId;
		parent::__construct();
	}


	//
	// Overridden methods from GridRow
	//
	/**
	 * @copydoc GridRow::initialize()
	 */
	function initialize($request, $template = null) {
		parent::initialize($request, $template);

		// Is this a new row or an existing row?
		$element =& $this->getData();
		assert(is_a($element, 'User'));

		$rowId = $this->getId();

		if (!empty($rowId) && is_numeric($rowId)) {
			// Only add row actions if this is an existing row
			$router = $request->getRouter();
			$actionArgs = array(
				'gridId' => $this->getGridId(),
				'rowId' => $rowId
			);

			$actionArgs = array_merge($actionArgs, $this->getRequestArgs());

			$this->addAction(
				new LinkAction(
					'email',
					new AjaxModal(
						$router->url($request, null, null, 'editEmail', null, $actionArgs),
						__('grid.user.email'),
						'modal_email',
						true
						),
					__('grid.user.email'),
					'notify')
			);
			$this->addAction(
				new LinkAction(
					'edit',
					new AjaxModal(
						$router->url($request, null, null, 'editUser', null, $actionArgs),
						__('grid.user.edit'),
						'modal_edit',
						true
						),
					__('grid.user.edit'),
					'edit')
			);
			if ($element->getDisabled()) {
				$actionArgs['enable'] = true;
				$this->addAction(
					new LinkAction(
						'enable',
						new AjaxModal(
							$router->url($request, null, null, 'editDisableUser', null, $actionArgs),
							__('common.enable'),
							'enable',
							true
							),
						__('common.enable'),
						'enable')
				);
			} else {
				$actionArgs['enable'] = false;
				$this->addAction(
					new LinkAction(
						'disable',
						new AjaxModal(
							$router->url($request, null, null, 'editDisableUser', null, $actionArgs),
							__('grid.user.disable'),
							'disable',
							true
							),
						__('grid.user.disable'),
						'disable')
				);
			}
			$this->addAction(
				new LinkAction(
					'remove',
					new RemoteActionConfirmationModal(
						$request->getSession(),
						__('manager.people.confirmRemove'),
						__('common.remove'),
						$router->url($request, null, null, 'removeUser', null, $actionArgs),
						'modal_delete'
						),
					__('grid.action.remove'),
					'delete')
			);

			$canAdminister = Validation::canAdminister($this->getId(), $request->getUser()->getId());
			if (
				!Validation::isLoggedInAs() and
				$request->getUser()->getId() != $this->getId() and
				$canAdminister
			) {
				$dispatcher = $router->getDispatcher();
				$this->addAction(
					new LinkAction(
						'logInAs',
						new RedirectConfirmationModal(
							__('grid.user.confirmLogInAs'),
							__('grid.action.logInAs'),
							$dispatcher->url($request, ROUTE_PAGE, null, 'login', 'signInAsUser', $this->getId())
						),
						__('grid.action.logInAs'),
						'enroll_user'
					)
				);
			}

			$oldUserId = $this->getOldUserId();
			$userDao = DAORegistry::getDAO('UserDAO');
			$oldUser = $userDao->getById($this->getOldUserId());
			if ($oldUser) {
				$actionArgs['oldUserId'] = $this->getOldUserId();
				$actionArgs['newUserId'] = $rowId;

				// Don't merge a user in itself
				if ($actionArgs['oldUserId'] != $actionArgs['newUserId']) {
					$userDao = DAORegistry::getDAO('UserDAO');
					$oldUser = $userDao->getById($this->getOldUserId());
					$this->addAction(
						new LinkAction(
							'mergeUser',
							new RemoteActionConfirmationModal(
								$request->getSession(),
								__('grid.user.mergeUsers.confirm', array('oldUsername' => $oldUser->getUsername(), 'newUsername' => $element->getUsername())),
								null,
								$router->url($request, null, null, 'mergeUsers', null, $actionArgs),
								'modal_merge_users'
							),
							__('grid.user.mergeUsers.mergeIntoUser'),
							'merge_users')
					);
				}

			} else {
				// do not allow the deletion of the admin account.
				if ($rowId > 1 && $canAdminister) {
					$this->addAction(
						new LinkAction(
							'mergeUser',
							new JsEventConfirmationModal(
								__('grid.user.mergeUsers.mergeUserSelect.confirm'),
								'confirmationModalConfirmed',
								array('oldUserId' => $rowId),
								null,
								'modal_merge_users'
							),
							__('grid.user.mergeUsers.mergeUser'),
							'merge_users')
					);
				}
			}
		}
	}

	/**
	 * Returns the stored user id of the user to be removed.
	 * @return int the user id.
	 */
	function getOldUserId() {
		return $this->_oldUserId;
	}
}

?>
