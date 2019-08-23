<?php

/**
 * @file controllers/grid/submissions/ExportPublishedSubmissionsListGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ExportPublishedSubmissionsListGridCellProvider
 * @ingroup controllers_grid_submissions
 *
 * @brief Class for a cell provider that can retrieve labels from submissions
 */

import('lib.pkp.classes.controllers.grid.DataObjectGridCellProvider');

class ExportPublishedSubmissionsListGridCellProvider extends DataObjectGridCellProvider {
	/** @var ImportExportPlugin */
	var $_plugin;

	/**
	 * Constructor
	 */
	function __construct($plugin, $authorizedRoles = null) {
		$this->_plugin  = $plugin;
		if ($authorizedRoles) {
			$this->_authorizedRoles = $authorizedRoles;
		}
		parent::__construct();
	}

	//
	// Template methods from GridCellProvider
	//
	/**
	 * Get cell actions associated with this row/column combination
	 *
	 * @copydoc GridCellProvider::getCellActions()
	 */
	function getCellActions($request, $row, $column, $position = GRID_ACTION_POSITION_DEFAULT) {
		$publishedSubmission = $row->getData();
		$columnId = $column->getId();
		assert(is_a($publishedSubmission, 'PublishedSubmission') && !empty($columnId));

		import('lib.pkp.classes.linkAction.request.RedirectAction');
		switch ($columnId) {
			case 'title':
				$this->_titleColumn = $column;
				$title = $publishedSubmission->getLocalizedTitle();
				if (empty($title)) $title = __('common.untitled');
				$authorsInTitle = $publishedSubmission->getShortAuthorString();
				$title = $authorsInTitle . '; ' . $title;
				import('classes.core.Services');
				return array(
					new LinkAction(
						'itemWorkflow',
						new RedirectAction(
							Services::get('submission')->getWorkflowUrlByUserRoles($publishedSubmission)
						),
						htmlspecialchars($title)
					)
				);
			case 'issue':
				$contextId = $publishedSubmission->getContextId();
				$issueId = $publishedSubmission->getIssueId();
				$issueDao = DAORegistry::getDAO('IssueDAO');
				$issue = $issueDao->getById($issueId, $contextId);
				// Link to the issue edit modal
				$application = Application::getApplication();
				$dispatcher = $application->getDispatcher();
				import('lib.pkp.classes.linkAction.request.AjaxModal');
				return array(
					new LinkAction(
						'edit',
						new AjaxModal(
							$dispatcher->url($request, ROUTE_COMPONENT, null, 'grid.issues.BackIssueGridHandler', 'editIssue', null, array('issueId' => $issue->getId())),
							__('plugins.importexport.common.settings.DOIPluginSettings')
						),
						htmlspecialchars($issue->getIssueIdentification()),
						null
					)
				);
			case 'status':
				$status = $publishedSubmission->getData($this->_plugin->getDepositStatusSettingName());
				$statusNames = $this->_plugin->getStatusNames();
				$statusActions = $this->_plugin->getStatusActions($publishedSubmission);
				if ($status && array_key_exists($status, $statusActions)) {
					assert(array_key_exists($status, $statusNames));
					return array($statusActions[$status]);
				}
		}
		return parent::getCellActions($request, $row, $column, $position);
	}

	/**
	 * Extracts variables for a given column from a data element
	 * so that they may be assigned to template before rendering.
	 *
	 * @copydoc DataObjectGridCellProvider::getTemplateVarsFromRowColumn()
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$publishedSubmission = $row->getData();
		$columnId = $column->getId();
		assert(is_a($publishedSubmission, 'PublishedSubmission') && !empty($columnId));

		switch ($columnId) {
			case 'id':
				return array('label' => $publishedSubmission->getId());
			case 'title':
				return array('label' => '');
			case 'issue':
				return array('label' => '');
			case 'status':
				$status = $publishedSubmission->getData($this->_plugin->getDepositStatusSettingName());
				$statusNames = $this->_plugin->getStatusNames();
				$statusActions = $this->_plugin->getStatusActions($publishedSubmission);
				if ($status) {
					if (array_key_exists($status, $statusActions)) {
						$label = '';
					} else {
						assert(array_key_exists($status, $statusNames));
						$label = $statusNames[$status];
					}
				} else {
					$label = $statusNames[EXPORT_STATUS_NOT_DEPOSITED];
				}
				return array('label' => $label);
		}
	}

}


