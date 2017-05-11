<?php

/**
 * @file controllers/grid/admin/systemInfo/ServerInfoGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ServerInfoGridHandler
 * @ingroup controllers_grid_admin_systemInfo
 *
 * @brief Handle server info grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('lib.pkp.controllers.grid.admin.systemInfo.InfoGridCellProvider');


class ServerInfoGridHandler extends GridHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(array(
			ROLE_ID_SITE_ADMIN),
			array('fetchGrid', 'fetchRow')
		);
	}


	//
	// Implement template methods from PKPHandler.
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.PolicySet');
		$rolePolicy = new PolicySet(COMBINING_PERMIT_OVERRIDES);

		import('lib.pkp.classes.security.authorization.RoleBasedHandlerOperationPolicy');
		foreach($roleAssignments as $role => $operations) {
			$rolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, $role, $operations));
		}
		$this->addPolicy($rolePolicy);

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @copydoc GridHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		// Load user-related translations.
		AppLocale::requireComponents(
			LOCALE_COMPONENT_PKP_USER,
			LOCALE_COMPONENT_PKP_ADMIN,
			LOCALE_COMPONENT_APP_ADMIN,
			LOCALE_COMPONENT_APP_MANAGER,
		);

		// Basic grid configuration.
		$this->setTitle('admin.serverInformation');

		//
		// Grid columns.
		//
		$infoGridCellProvider = new InfoGridCellProvider(true);

		// Setting name.
		$this->addColumn(
			new GridColumn(
				'name',
				'admin.systemInfo.settingName',
				null,
				null,
				$infoGridCellProvider,
				array('width' => 20, 'alignment' => COLUMN_ALIGNMENT_LEFT)
			)
		);

		// Setting value.
		$this->addColumn(
			new GridColumn(
				'value',
				'admin.systemInfo.settingValue',
				null,
				null,
				$infoGridCellProvider
			)
		);
	}


	//
	// Implement template methods from GridHandler
	//

	/**
	 * @copydoc GridHandler::loadData()
	 */
	protected function loadData($request, $filter) {

		$dbconn = DBConnection::getConn();
		$dbServerInfo = $dbconn->ServerInfo();

		$serverInfo = array(
			'admin.server.platform' => PHP_OS,
			'admin.server.phpVersion' => phpversion(),
			'admin.server.apacheVersion' => $_SERVER['SERVER_SOFTWARE'],
			'admin.server.dbDriver' => Config::getVar('database', 'driver'),
			'admin.server.dbVersion' => (empty($dbServerInfo['description']) ? $dbServerInfo['version'] : $dbServerInfo['description'])
		);

		return $serverInfo;
	}
}
?>
