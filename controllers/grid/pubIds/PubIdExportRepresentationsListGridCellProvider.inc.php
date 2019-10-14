<?php

/**
 * @file controllers/grid/pubIds/PubIdExportRepresentationsListGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PubIdExportRepresentationssListGridCellProvider
 * @ingroup controllers_grid_pubIds
 *
 * @brief Class for a cell provider that can retrieve labels from representations with pub ids
 */

import('lib.pkp.classes.controllers.grid.DataObjectGridCellProvider');


class PubIdExportRepresentationsListGridCellProvider extends DataObjectGridCellProvider {
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
		$galley = $row->getData();
		$columnId = $column->getId();
		assert(is_a($galley, 'ArticleGalley') && !empty($columnId));

		$publication = Services::get('publication')->get($galley->getData('publicationId'));
		$submission = Services::get('submission')->get($publication->getData('submissionId'));
		import('lib.pkp.classes.linkAction.request.RedirectAction');
		switch ($columnId) {
			case 'title':
				$this->_titleColumn = $column;
				$title = $submission->getLocalizedTitle();
				if (empty($title)) $title = __('common.untitled');
				$authorsInTitle = $submission->getShortAuthorString();
				$title = $authorsInTitle . '; ' . $title;
				import('classes.core.Services');
				return array(
					new LinkAction(
						'itemWorkflow',
						new RedirectAction(
							Services::get('submission')->getWorkflowUrlByUserRoles($submission)
						),
						htmlspecialchars($title)
					)
				);
			case 'issue':
				$contextId = $submission->getContextId();
				$issueId = $submission->getCurrentPublication()->getData('issueId');
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
				$status = $galley->getData($this->_plugin->getDepositStatusSettingName());
				$statusNames = $this->_plugin->getStatusNames();
				$statusActions = $this->_plugin->getStatusActions($submission);
				if ($status && array_key_exists($status, $statusActions)) {
					assert(array_key_exists($status, $statusNames));
					return array(
						new LinkAction(
							'edit',
							new RedirectAction(
								$statusActions[$status],
								'_blank'
							),
							htmlspecialchars($statusNames[$status])
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
		$submissionGalley = $row->getData();
		$columnId = $column->getId();
		assert(is_a($submissionGalley, 'ArticleGAlley') && !empty($columnId));

		switch ($columnId) {
			case 'id':
				return array('label' => $submissionGalley->getId());
			case 'title':
				return array('label' => '');
			case 'issue':
				return array('label' => '');
			case 'galley':
				return array('label' => $submissionGalley->getGalleyLabel());
			case 'pubId':
				return array('label' => $submissionGalley->getStoredPubId($this->_plugin->getPubIdType()));
			case 'status':
				$status = $submissionGalley->getData($this->_plugin->getDepositStatusSettingName());
				$statusNames = $this->_plugin->getStatusNames();
				$statusActions = $this->_plugin->getStatusActions($submissionGalley);
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
