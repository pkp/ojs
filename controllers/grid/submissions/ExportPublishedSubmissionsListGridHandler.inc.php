<?php

/**
 * @file controllers/grid/submissions/ExportPublishedSubmissionsListGridHandler.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ExportPublishedSubmissionsListGridHandler
 * @ingroup controllers_grid_submissions
 *
 * @brief Handle exportable published submissions list grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');

class ExportPublishedSubmissionsListGridHandler extends GridHandler {
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
		$context = $request->getContext();

		// Basic grid configuration.
		$this->setTitle('plugins.importexport.common.export.articles');

		// Load submission-specific translations.
		AppLocale::requireComponents(
			LOCALE_COMPONENT_APP_SUBMISSION, // title filter
			LOCALE_COMPONENT_PKP_SUBMISSION, // authors filter
			LOCALE_COMPONENT_APP_MANAGER
		);

		$pluginCategory = $request->getUserVar('category');
		$pluginPathName = $request->getUserVar('plugin');
		$this->_plugin = PluginRegistry::loadPlugin($pluginCategory, $pluginPathName);
		assert(isset($this->_plugin));

		// Grid columns.
		$cellProvider = $this->getGridCellProvider();
		$this->addColumn(
			new GridColumn(
				'id',
				null,
				__('common.id'),
				'controllers/grid/gridCell.tpl',
				$cellProvider,
				array('alignment' => COLUMN_ALIGNMENT_LEFT,
						'width' => 10)
			)
		);
		$this->addColumn(
			new GridColumn(
				'title',
				'grid.submission.itemTitle',
				null,
				null,
				$cellProvider,
				array('html' => true,
						'alignment' => COLUMN_ALIGNMENT_LEFT)
			)
		);
		$this->addColumn(
			new GridColumn(
				'issue',
				'issue.issue',
				null,
				null,
				$cellProvider,
				array('alignment' => COLUMN_ALIGNMENT_LEFT,
					'width' => 20)
			)
		);
		if (method_exists($this, 'addAdditionalColumns')) {
			$this->addAdditionalColumns($cellProvider);
		}
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
		return 'selectedSubmissions';
	}

	/**
	 * @copydoc GridHandler::getFilterForm()
	 */
	protected function getFilterForm() {
		return 'controllers/grid/submissions/exportPublishedSubmissionsGridFilter.tpl';
	}

	/**
	 * @copydoc GridHandler::renderFilter()
	 */
	function renderFilter($request, $filterData = array()) {
		$context = $request->getContext();
		$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		$issuesIterator = $issueDao->getPublishedIssues($context->getId());
		$issues = $issuesIterator->toArray();
		foreach ($issues as $issue) {
			$issueOptions[$issue->getId()] = $issue->getIssueIdentification();
		}
		$issueOptions[0] = __('plugins.importexport.common.filter.issue');
		ksort($issueOptions);
		$statusNames = $this->_plugin->getStatusNames();
		$filterColumns = $this->getFilterColumns();
		$allFilterData = array_merge(
			$filterData,
			array(
				'columns' => $filterColumns,
				'issues' => $issueOptions,
				'status' => $statusNames,
				'gridId' => $this->getId(),
			));
		return parent::renderFilter($request, $allFilterData);
	}

	/**
	 * @copydoc GridHandler::getFilterSelectionData()
	 */
	function getFilterSelectionData($request) {
		$search = (string) $request->getUserVar('search');
		$column = (string) $request->getUserVar('column');
		$issueId = (int) $request->getUserVar('issueId');
		$statusId = (string) $request->getUserVar('statusId');
		return array(
			'search' => $search,
			'column' => $column,
			'issueId' => $issueId,
			'statusId' => $statusId,
		);
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	protected function loadData($request, $filter) {
		$context = $request->getContext();
		list($search, $column, $issueId, $statusId) = $this->getFilterValues($filter);
		$title = $author = null;
		if ($column == 'title') {
			$title = $search;
		} elseif ($column == 'author') {
			$author = $search;
		}
		$pubIdStatusSettingName = null;
		if ($statusId) {
			$pubIdStatusSettingName = $this->_plugin->getDepositStatusSettingName();
		}
		$submissionDao = DAORegistry::getDAO('SubmissionDAO'); /* @var $submissionDao SubmissionDAO */
		return $submissionDao->getExportable(
			$context->getId(),
			null,
			$title,
			$author,
			$issueId,
			$pubIdStatusSettingName,
			$statusId,
			$this->getGridRangeInfo($request, $this->getId())
		);
	}


	//
	// Own protected methods
	//
	/**
	 * Get which columns can be used by users to filter data.
	 * @return array
	 */
	protected function getFilterColumns() {
		return array(
			'title' => __('submission.title'),
			'author' => __('submission.authors')
		);
	}

	/**
	 * Process filter values, assigning default ones if
	 * none was set.
	 * @return array
	 */
	protected function getFilterValues($filter) {
		if (isset($filter['search']) && $filter['search']) {
			$search = $filter['search'];
		} else {
			$search = null;
		}
		if (isset($filter['column']) && $filter['column']) {
			$column = $filter['column'];
		} else {
			$column = null;
		}
		if (isset($filter['issueId']) && $filter['issueId']) {
			$issueId = $filter['issueId'];
		} else {
			$issueId = null;
		}
		if (isset($filter['statusId']) && $filter['statusId'] != EXPORT_STATUS_ANY) {
			$statusId = $filter['statusId'];
		} else {
			$statusId = null;
		}
		return array($search, $column, $issueId, $statusId);
	}

	/**
	 * Get the grid cell provider instance
	 * @return DataObjectGridCellProvider
	 */
	function getGridCellProvider() {
		// Fetch the authorized roles.
		$authorizedRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);
		import('controllers.grid.submissions.ExportPublishedSubmissionsListGridCellProvider');
		return new ExportPublishedSubmissionsListGridCellProvider($this->_plugin, $authorizedRoles);
	}

}


