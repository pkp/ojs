<?php
/**
 * @file controllers/grid/ExternalFeedGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ExternalFeedGridCellProvider
 * @ingroup controllers_grid_externalFeed
 *
 * @brief Class for a cell provider to display information about external feed
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');
import('lib.pkp.classes.linkAction.request.RedirectAction');

class ExternalFeedGridCellProvider extends GridCellProvider {
	/**
	 * Extracts variables for a given column from a data element
	 * so that they may be assigned to template before rendering.
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$feed = $row->getData();
		switch ($column->getId()) {
			case 'title':
				return array('label' => $feed->getLocalizedTitle());
			case 'homepage':
				return array('selected' => $feed->getDisplayHomepage() ? true : false, 'disabled' => true ); 
			case 'displayBlockAll':
				return array('selected' => ($feed->getDisplayBlock() == EXTERNAL_FEED_DISPLAY_BLOCK_ALL) ? true : false, 'disabled' => true );
			case 'displayBlockHomepage':
				return array('selected' => ($feed->getDisplayBlock() == EXTERNAL_FEED_DISPLAY_BLOCK_HOMEPAGE) ? true : false, 'disabled' => true );
		}
		return parent::getTemplateVarsFromRowColumn($row, $column);
	}
}