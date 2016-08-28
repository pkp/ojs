<?php

/**
 * @file controllers/grid/pubIds/PubIdExportSubmissionsListGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PubIdExportSubmissionsListGridCellProvider
 * @ingroup controllers_grid_pubIds
 *
 * @brief Class for a cell provider that can retrieve labels from submissions with pub ids
 */

import('controllers.grid.submissions.ExportPublishedSubmissionsListGridCellProvider');


class PubIdExportSubmissionsListGridCellProvider extends ExportPublishedSubmissionsListGridCellProvider {
	/**
	 * Constructor
	 */
	function PubIdExportSubmissionsListGridCellProvider($plugin, $authorizedRoles = null) {
		parent::ExportPublishedSubmissionsListGridCellProvider($plugin, $authorizedRoles);
	}

	/**
	 * @copydoc ExportPublishedSubmissionsListGridCellProvider::getTemplateVarsFromRowColumn()
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$publishedSubmission = $row->getData();
		$columnId = $column->getId();
		assert(is_a($publishedSubmission, 'PublishedArticle') && !empty($columnId));

		switch ($columnId) {
			case 'pubId':
				return array('label' => $publishedSubmission->getStoredPubId($this->_plugin->getPubIdType()));
		}
		return parent::getTemplateVarsFromRowColumn($row, $column);
	}

}

?>
