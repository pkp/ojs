<?php

/**
 * @file controllers/grid/pubIds/PubIdExportSubmissionsListGridHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PubIdExportSubmissionsListGridHandler
 * @ingroup controllers_grid_pubIds
 *
 * @brief Handle exportable submissions with pub ids list grid requests.
 */

import('controllers.grid.submissions.ExportPublishedSubmissionsListGridHandler');

class PubIdExportSubmissionsListGridHandler extends ExportPublishedSubmissionsListGridHandler {

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
		return [];
		// return $publishedSubmissionDao->getExportable(
		// 	$context->getId(),
		// 	$this->_plugin->getPubIdType(),
		// 	$title,
		// 	$author,
		// 	$issueId,
		// 	$pubIdStatusSettingName,
		// 	$statusId,
		// 	$this->getGridRangeInfo($request, $this->getId())
		// );
	}

	/**
	 * @copydoc ExportPublishedSubmissionsListGridHandler::getGridCellProvider()
	 */
	function getGridCellProvider() {
		// Fetch the authorized roles.
		$authorizedRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);
		import('controllers.grid.pubIds.PubIdExportSubmissionsListGridCellProvider');
		return new PubIdExportSubmissionsListGridCellProvider($this->_plugin, $authorizedRoles);
	}

	/**
	 * Get the grid cell provider instance
	 * @return DataObjectGridCellProvider
	 */
	function addAdditionalColumns($cellProvider) {
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
	}

}


