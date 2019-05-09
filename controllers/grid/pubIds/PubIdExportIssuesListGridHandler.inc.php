<?php

/**
 * @file controllers/grid/pubIds/PubIdExportIssuesListGridHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PubIdExportIssuesListGridHandler
 * @ingroup controllers_grid_pubIds
 *
 * @brief Handle exportable issues with pub ids list grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('controllers.grid.pubIds.PubIdExportIssuesListGridCellProvider');

class PubIdExportIssuesListGridHandler extends GridHandler {
	/** @var ImportExportPlugin */
	var $_plugin;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER),
			array('fetchGrid', 'fetchRow')
		);
	}

	//
	// Implement template methods from PKPHandler
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

		// Basic grid configuration.
		$this->setTitle('plugins.importexport.common.export.issues');

		// Load submission-specific translations.
		AppLocale::requireComponents(
			LOCALE_COMPONENT_APP_EDITOR, // date grid column
			LOCALE_COMPONENT_APP_MANAGER
		);

		$pluginCategory = $request->getUserVar('category');
		$pluginPathName = $request->getUserVar('plugin');
		$this->_plugin = PluginRegistry::loadPlugin($pluginCategory, $pluginPathName);
		assert(isset($this->_plugin));

		// Fetch the authorized roles.
		$authorizedRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);

		// Grid columns.
		$cellProvider = new PubIdExportIssuesListGridCellProvider($this->_plugin, $authorizedRoles);
		$this->addColumn(
			new GridColumn(
				'identification',
				'issue.issue',
				null,
				null,
				$cellProvider,
				array('html' => true,
						'alignment' => COLUMN_ALIGNMENT_LEFT)
			)
		);
		$this->addColumn(
			new GridColumn(
				'published',
				'editor.issues.published',
				null,
				null,
				$cellProvider,
				array('html' => true,
						'alignment' => COLUMN_ALIGNMENT_LEFT)
			)
		);
		$this->addColumn(
			new GridColumn(
				'pubId',
				null,
				$this->_plugin->getPubIdDisplayType(),
				null,
				$cellProvider,
				array('alignment' => COLUMN_ALIGNMENT_LEFT,
						'width' => 15)
				)
		);
		$this->addColumn(
			new GridColumn(
				'status',
				'common.status',
				null,
				null,
				$cellProvider,
				array('alignment' => COLUMN_ALIGNMENT_LEFT,
						'width' => 10)
			)
		);
	}


	//
	// Implemented methods from GridHandler.
	//
	/**
	 * @copydoc GridHandler::initFeatures()
	 */
	function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.selectableItems.SelectableItemsFeature');
		import('lib.pkp.classes.controllers.grid.feature.PagingFeature');
		return array(new SelectableItemsFeature(), new PagingFeature());
	}

	/**
	 * @copydoc GridHandler::getRequestArgs()
	 */
	function getRequestArgs() {
		return array_merge(parent::getRequestArgs(), array('category' => $this->_plugin->getCategory(), 'plugin' => basename($this->_plugin->getPluginPath())));
	}

	/**
	 * @copydoc GridHandler::isDataElementSelected()
	 */
	function isDataElementSelected($gridDataElement) {
		return false; // Nothing is selected by default
	}

	/**
	 * @copydoc GridHandler::getSelectName()
	 */
	function getSelectName() {
		return 'selectedIssues';
	}

	/**
	 * @copydoc GridHandler::getFilterForm()
	 */
	protected function getFilterForm() {
		return 'controllers/grid/pubIds/pubIdExportIssuesGridFilter.tpl';
	}

	/**
	 * @copydoc GridHandler::renderFilter()
	 */
	function renderFilter($request, $filterData = array()) {
		$statusNames = $this->_plugin->getStatusNames();
		$allFilterData = array_merge(
			$filterData,
			array(
				'status' => $statusNames,
				'gridId' => $this->getId(),
			));
		return parent::renderFilter($request, $allFilterData);
	}

	/**
	 * @copydoc GridHandler::getFilterSelectionData()
	 */
	function getFilterSelectionData($request) {
		$statusId = (string) $request->getUserVar('statusId');
		return array(
			'statusId' => $statusId,
		);
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	protected function loadData($request, $filter) {
		$context = $request->getContext();
		list($statusId) = $this->getFilterValues($filter);
		$pubIdStatusSettingName = null;
		if ($statusId) {
			$pubIdStatusSettingName = $this->_plugin->getDepositStatusSettingName();
		}
		$issueDao = DAORegistry::getDAO('IssueDAO');
		return $issueDao->getExportable(
			$context->getId(),
			$this->_plugin->getPubIdType(),
			$pubIdStatusSettingName,
			$statusId,
			$this->getGridRangeInfo($request, $this->getId())
		);
	}

	/**
	 * Process filter values, assigning default ones if
	 * none was set.
	 * @return array
	 */
	protected function getFilterValues($filter) {
		if (isset($filter['statusId']) && $filter['statusId'] != EXPORT_STATUS_ANY) {
			$statusId = $filter['statusId'];
		} else {
			$statusId = null;
		}
		return array($statusId);
	}

}


