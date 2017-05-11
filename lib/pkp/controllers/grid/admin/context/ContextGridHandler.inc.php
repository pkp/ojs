<?php

/**
 * @file controllers/grid/admin/context/ContextGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ContextGridHandler
 * @ingroup controllers_grid_admin_context
 *
 * @brief Handle context grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('lib.pkp.controllers.grid.admin.context.ContextGridRow');

class ContextGridHandler extends GridHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(array(
			ROLE_ID_SITE_ADMIN),
			array('fetchGrid', 'fetchRow', 'createContext', 'editContext', 'updateContext',
				'deleteContext', 'saveSequence')
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
			LOCALE_COMPONENT_APP_MANAGER,
			LOCALE_COMPONENT_APP_ADMIN
		);

		// Grid actions.
		$router = $request->getRouter();

		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$this->addAction(
			new LinkAction(
				'createContext',
				new AjaxModal(
					$router->url($request, null, null, 'createContext', null, null),
					__('admin.contexts.create'),
					'modal_add_item',
					true
					),
				__('admin.contexts.create'),
				'add_item'
			)
		);

		//
		// Grid columns.
		//
		import('lib.pkp.controllers.grid.admin.context.ContextGridCellProvider');
		$contextGridCellProvider = new ContextGridCellProvider();

		// Context name.
		$this->addColumn(
			new GridColumn(
				'name',
				'common.name',
				null,
				null,
				$contextGridCellProvider
			)
		);

		// Context path.
		$this->addColumn(
			new GridColumn(
				'path',
				'context.path',
				null,
				null,
				$contextGridCellProvider
			)
		);
	}


	//
	// Implement methods from GridHandler.
	//
	/**
	 * @copydoc GridHandler::getRowInstance()
	 * @return UserGridRow
	 */
	protected function getRowInstance() {
		return new ContextGridRow();
	}

	/**
	 * @copydoc GridHandler::loadData()
	 * @param $request PKPRequest
	 * @return array Grid data.
	 */
	protected function loadData($request) {
		// Get all contexts.
		$contextDao = Application::getContextDAO();
		$contexts = $contextDao->getAll();

		return $contexts->toAssociativeArray();
	}

	/**
	 * @copydoc GridHandler::setDataElementSequence()
	 */
	function setDataElementSequence($request, $rowId, $gridDataElement, $newSequence) {
		$contextDao = Application::getContextDAO();
		$gridDataElement->setSequence($newSequence);
		$contextDao->updateObject($gridDataElement);
	}

	/**
	 * @copydoc GridHandler::getDataElementSequence()
	 */
	function getDataElementSequence($gridDataElement) {
		return $gridDataElement->getSequence();
	}

	/**
	 * @copydoc GridHandler::addFeatures()
	 */
	function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.OrderGridItemsFeature');
		return array(new OrderGridItemsFeature());
	}

	/**
	 * Get the list of "publish data changed" events.
	 * Used to update the site context switcher upon create/delete.
	 * @return array
	 */
	function getPublishChangeEvents() {
		return array('updateHeader');
	}


	//
	// Public grid actions.
	//
	/**
	 * Add a new context.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function createContext($args, $request) {
		// Calling editContext with an empty row id will add a new context.
		return $this->editContext($args, $request);
	}


	//
	// Protected helper methods.
	//
	/**
	 * Return a redirect event.
	 * @param $request Request
	 * @param $newContextPath string
	 * @param $openWizard boolean
	 */
	protected function _getRedirectEvent($request, $newContextPath, $openWizard) {
		$dispatcher = $request->getDispatcher();

		$url = $dispatcher->url($request, ROUTE_PAGE, $newContextPath, 'admin', 'contexts', null, array('openWizard' => $openWizard));
		return $request->redirectUrlJson($url);
	}
}

?>
