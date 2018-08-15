<?php

/**
 * @file controllers/grid/pubIds/PubIdExportIssuesListGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PubIdExportIssuesListGridCellProvider
 * @ingroup controllers_grid_pubIds
 *
 * @brief Class for a cell provider that can retrieve labels from issues with pub ids
 */

import('lib.pkp.classes.controllers.grid.DataObjectGridCellProvider');

class PubIdExportIssuesListGridCellProvider extends DataObjectGridCellProvider {
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
		$publishedIssue = $row->getData();
		$columnId = $column->getId();
		assert(is_a($publishedIssue, 'Issue') && !empty($columnId));

		switch ($columnId) {
			case 'identification':
				// Link to the issue edit modal
				$application = Application::getApplication();
				$dispatcher = $application->getDispatcher();
				import('lib.pkp.classes.linkAction.request.AjaxModal');
				return array(
					new LinkAction(
						'edit',
						new AjaxModal(
							$dispatcher->url($request, ROUTE_COMPONENT, null, 'grid.issues.BackIssueGridHandler', 'editIssue', null, array('issueId' => $publishedIssue->getId())),
							__('plugins.importexport.common.settings.DOIPluginSettings')
						),
						$publishedIssue->getIssueIdentification(),
						null
					)
				);
			case 'status':
				$status = $publishedIssue->getData($this->_plugin->getDepositStatusSettingName());
				$statusNames = $this->_plugin->getStatusNames();
				$statusActions = $this->_plugin->getStatusActions($publishedIssue);
				if ($status && array_key_exists($status, $statusActions)) {
					assert(array_key_exists($status, $statusNames));
					import('lib.pkp.classes.linkAction.request.RedirectAction');
					return array(
						new LinkAction(
							'edit',
							new RedirectAction(
								$statusActions[$status],
								'_blank'
							),
							$statusNames[$status]
						)
					);
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
		$publishedIssue = $row->getData();
		$columnId = $column->getId();
		assert(is_a($publishedIssue, 'Issue') && !empty($columnId));

		switch ($columnId) {
			case 'identification':
				return array('label' => '');
			case 'published':
				return array('label' => $publishedIssue->getDatePublished());
			case 'pubId':
				return array('label' => $publishedIssue->getStoredPubId($this->_plugin->getPubIdType()));
			case 'status':
				$status = $publishedIssue->getData($this->_plugin->getDepositStatusSettingName());
				$statusNames = $this->_plugin->getStatusNames();
				$statusActions = $this->_plugin->getStatusActions($publishedIssue);
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


