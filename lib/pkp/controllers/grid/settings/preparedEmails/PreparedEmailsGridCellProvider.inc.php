<?php

/**
 * @file controllers/grid/settings/preparedEmails/PreparedEmailsGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GridCellProvider
 * @ingroup controllers_grid_settings_preparedEmails
 *
 * @brief Class for a prepared email grid column's cell provider
 */

import('lib.pkp.classes.controllers.grid.DataObjectGridCellProvider');

class PreparedEmailsGridCellProvider extends DataObjectGridCellProvider {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Extracts variables for a given column from a data element
	 * so that they may be assigned to template before rendering.
	 * @param $element mixed
	 * @param $columnId string
	 * @return array
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$element =& $row->getData();
		$columnId = $column->getId();
		assert(is_a($element, 'DataObject') && !empty($columnId));
		$roleDao = DAORegistry::getDAO('RoleDAO'); /* @var $roleDao RoleDAO */
		switch ($columnId) {
			case 'name':
				$label = $element->getEmailKey();
				return array('label' => ucwords(strtolower(str_replace('_', ' ', $label))));
			case 'sender':
				$roleId = $element->getFromRoleId();
				$label = $roleDao->getRoleNames(false, array($roleId));
				return array('label' => __(array_shift($label)));
			case 'recipient':
				$roleId = $element->getToRoleId();
				$label = $roleDao->getRoleNames(false, array($roleId));
				return array('label' => __(array_shift($label)));
			case 'subject':
				$locale = AppLocale::getLocale();
				$label = $element->getSubject();
				return array('label' => $label);
			case 'enabled':
				$selectDisabled = $element->getCanDisable() ? false : true;
				return array('selected' => $element->getEnabled(), 'disabled' => $selectDisabled);
		}
	}

	/**
	 * @copydoc GridCellProvider::getCellActions()
	 */
	function getCellActions($request, $row, $column, $position = GRID_ACTION_POSITION_DEFAULT) {
		switch ($column->getId()) {
			case 'enabled':
				$element = $row->getData(); /* @var $element DataObject */
				$router = $request->getRouter();
				import('lib.pkp.classes.linkAction.LinkAction');
				if ($element->getCanDisable()) {
					if ($element->getEnabled()) {
						return array(new LinkAction(
							'disableEmail',
							new RemoteActionConfirmationModal(
								$request->getSession(),
								__('manager.emails.disable.message'), null,
								$router->url($request, null, 'grid.settings.preparedEmails.PreparedEmailsGridHandler',
									'disableEmail', null, array('emailKey' => $element->getEmailKey()))
							),
							__('manager.emails.disable'),
							'disable'
						));
					} else {
						return array(new LinkAction(
							'enableEmail',
							new RemoteActionConfirmationModal(
								$request->getSession(),
								__('manager.emails.enable.message'), null,
								$router->url($request, null, 'grid.settings.preparedEmails.PreparedEmailsGridHandler',
									'enableEmail', null, array('emailKey' => $element->getEmailKey()))
							),
							__('manager.emails.enable'),
							'enable'
						));
					}
			}
		}
		return parent::getCellActions($request, $row, $column, $position);
	}
}

?>
