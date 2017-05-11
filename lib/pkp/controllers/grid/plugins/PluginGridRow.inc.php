<?php

/**
 * @file controllers/grid/plugins/PluginGridRow.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PluginGridRow
 * @ingroup controllers_grid_plugins
 *
 * @brief Plugin grid row definition
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class PluginGridRow extends GridRow {

	/** @var Array */
	var $_userRoles;

	/**
	 * Constructor
	 * @param $userRoles array
	 */
	function __construct($userRoles) {
		parent::__construct();
		$this->_userRoles = $userRoles;
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
		$plugin =& $this->getData(); /* @var $plugin Plugin */
		assert(is_a($plugin, 'Plugin'));

		$rowId = $this->getId();

		// Only add row actions if this is an existing row
		if (!is_null($rowId)) {
			$router = $request->getRouter(); /* @var $router PKPRouter */

			$actionArgs = array_merge(
				array('plugin' => $plugin->getName()),
				$this->getRequestArgs()
			);

			if ($this->_canEdit($plugin)) {
				foreach ($plugin->getActions($request, $actionArgs) as $action) {
					$this->addAction($action);
				}
			}

			// Administrative functions.
			if (in_array(ROLE_ID_SITE_ADMIN, $this->_userRoles)) {
				import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
				$this->addAction(new LinkAction(
					'delete',
					new RemoteActionConfirmationModal(
						$request->getSession(),
						__('manager.plugins.deleteConfirm'),
						__('common.delete'),
						$router->url($request, null, null, 'deletePlugin', null, $actionArgs), 'modal_delete'),
					__('common.delete'),
					'delete'
				));

				$this->addAction(new LinkAction(
					'upgrade',
					new AjaxModal(
						$router->url($request, null, null, 'upgradePlugin', null, $actionArgs),
						__('manager.plugins.upgrade'), 'modal_upgrade'),
					__('grid.action.upgrade'),
					'upgrade'
				));
			}
		}
	}


	//
	// Protected helper methods
	//
	/**
	 * Return if user can edit a plugin settings or not.
	 * @param $plugin Plugin
	 * @return boolean
	 */
	protected function _canEdit($plugin) {
		if ($plugin->isSitePlugin()) {
			if (in_array(ROLE_ID_SITE_ADMIN, $this->_userRoles)) return true;
		} else {
			if (in_array(ROLE_ID_MANAGER, $this->_userRoles)) return true;
		}
		return false;
	}
}

?>
