<?php

/**
 * @file controllers/grid/settings/submissionChecklist/SubmissionChecklistGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionChecklistGridHandler
 * @ingroup controllers_grid_settings_submissionChecklist
 *
 * @brief Handle submissionChecklist grid requests.
 */

import('lib.pkp.controllers.grid.settings.SetupGridHandler');
import('lib.pkp.controllers.grid.settings.submissionChecklist.SubmissionChecklistGridRow');

class SubmissionChecklistGridHandler extends SetupGridHandler {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(array(ROLE_ID_MANAGER),
				array('fetchGrid', 'fetchRow', 'addItem', 'editItem', 'updateItem', 'deleteItem', 'saveSequence'));
	}

	//
	// Overridden template methods
	//
	/**
	 * @copydoc SetupGridHandler::initialize()
	 */
	function initialize($request) {
		parent::initialize($request);

		// Basic grid configuration
		$this->setId('submissionChecklist');
		$this->setTitle('manager.setup.submissionPreparationChecklist');

		// Add grid-level actions
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$router = $request->getRouter();
		$this->addAction(
			new LinkAction(
				'addItem',
				new AjaxModal(
					$router->url($request, null, null, 'addItem', null, array('gridId' => $this->getId())),
					__('grid.action.addItem'),
					'modal_add_item',
					true),
				__('grid.action.addItem'),
				'add_item')
		);

		// Columns
		$this->addColumn(
			new GridColumn(
				'content',
				'grid.submissionChecklist.column.checklistItem',
				null,
				null,
				null,
				array('html' => true, 'maxLength' => 220)
			)
		);
	}


	//
	// Overridden methods from GridHandler
	//
	/**
	 * @copydoc GridHandler::initFeatures()
	 */
	function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.OrderGridItemsFeature');
		return array(new OrderGridItemsFeature());
	}

	/**
	 * @copydoc GridHandler::getRowInstance()
	 */
	protected function getRowInstance() {
		return new SubmissionChecklistGridRow();
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	protected function loadData($request, $filter) {
		// Elements to be displayed in the grid
		$router = $request->getRouter();
		$context = $router->getContext($request);
		$submissionChecklist = $context->getSetting('submissionChecklist');

		return $submissionChecklist[AppLocale::getLocale()];
	}


	//
	// Public grid actions.
	//
	/**
	 * An action to add a new submissionChecklist
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function addItem($args, $request) {
		// Calling editItem with an empty row id will add a new row.
		return $this->editItem($args, $request);
	}

	/**
	 * An action to edit a submissionChecklist
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editItem($args, $request) {
		import('lib.pkp.controllers.grid.settings.submissionChecklist.form.SubmissionChecklistForm');
		$submissionChecklistId = isset($args['rowId']) ? $args['rowId'] : null;
		$submissionChecklistForm = new SubmissionChecklistForm($submissionChecklistId);

		$submissionChecklistForm->initData($args, $request);

		return new JSONMessage(true, $submissionChecklistForm->fetch($request));
	}

	/**
	 * Update a submissionChecklist
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateItem($args, $request) {
		// -> submissionChecklistId must be present and valid
		// -> htmlId must be present and valid

		import('lib.pkp.controllers.grid.settings.submissionChecklist.form.SubmissionChecklistForm');
		$submissionChecklistId = isset($args['rowId']) ? $args['rowId'] : null;
		$submissionChecklistForm = new SubmissionChecklistForm($submissionChecklistId);
		$submissionChecklistForm->readInputData();

		if ($submissionChecklistForm->validate()) {
			$submissionChecklistForm->execute($args, $request);
			return DAO::getDataChangedEvent($submissionChecklistForm->submissionChecklistId);
		} else {
			return new JSONMessage(false);
		}
	}

	/**
	 * Delete a submissionChecklist
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function deleteItem($args, $request) {
		if (!$request->checkCSRF()) return new JSONMessage(false);

		$rowId = $request->getUserVar('rowId');

		$router = $request->getRouter();
		$context = $router->getContext($request);

		// get all of the submissionChecklists
		$submissionChecklistAll = $context->getSetting('submissionChecklist');

		foreach (AppLocale::getSupportedLocales() as $locale => $name) {
			if ( isset($submissionChecklistAll[$locale][$rowId]) ) {
				unset($submissionChecklistAll[$locale][$rowId]);
			} else {
				// only fail if the currently displayed locale was not set
				// (this is the one that needs to be removed from the currently displayed grid)
				if ( $locale == AppLocale::getLocale() ) {
					return new JSONMessage(false, __('manager.setup.errorDeletingSubmissionChecklist'));
				}
			}
		}

		$context->updateSetting('submissionChecklist', $submissionChecklistAll, 'object', true);
		return DAO::getDataChangedEvent($rowId);
	}

	/**
	 * @copydoc GridHandler::getDataElementSequence()
	 */
	function getDataElementSequence($gridDataElement) {
		return $gridDataElement['order'];
	}

	/**
	 * @copydoc GridHandler::setDataElementSequence()
	 */
	function setDataElementSequence($request, $rowId, $gridDataElement, $newSequence) {
		$router = $request->getRouter();
		$context = $router->getContext($request);

		// Get all of the submissionChecklists.
		$submissionChecklistAll = $context->getSetting('submissionChecklist');
		$locale = AppLocale::getLocale();

		if (isset($submissionChecklistAll[$locale][$rowId])) {
			$submissionChecklistAll[$locale][$rowId]['order'] = $newSequence;
		}

		$orderMap = array();
		foreach ($submissionChecklistAll[$locale] as $id => $checklistItem) {
			$orderMap[$id] = $checklistItem['order'];
		}

		asort($orderMap);

		// Build the new order checklist object.
		$orderedChecklistItems = array();
		foreach ($orderMap as $id => $order) {
			if (isset($submissionChecklistAll[$locale][$id])) {
				$orderedChecklistItems[$locale][$id] = $submissionChecklistAll[$locale][$id];
			}
		}

		$context->updateSetting('submissionChecklist', $orderedChecklistItems, 'object', true);
	}
}

?>
