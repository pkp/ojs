<?php

/**
 * @file classes/controllers/listbuilder/ListbuilderGridColumn.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ListbuilderGridColumn
 * @ingroup controllers_listbuilder
 *
 * @brief Represents a column within a listbuilder.
 */


import('lib.pkp.classes.controllers.grid.GridColumn');

class ListbuilderGridColumn extends GridColumn {
	/**
	 * Constructor
	 * @param $listbuilder ListbuilderHandler The listbuilder handler this column belongs to.
	 * @param $id string The optional symbolic ID for this column.
	 * @param $title string The optional title for this column.
	 * @param $titleTranslated string The optional translated title for this column.
	 * @param $template string The optional overridden template for this column.
	 * @param $cellProvider ListbuilderGridCellProvider The optional overridden grid cell provider.
	 * @param $flags array Optional set of flags for this column's display.
	 */
	function __construct($listbuilder, $id = '', $title = null, $titleTranslated = null,
			$template = null, $cellProvider = null, $flags = array()) {

		// Set this here so that callers using later optional parameters don't need to
		// duplicate it.
		if ($template === null) $template = 'controllers/listbuilder/listbuilderGridCell.tpl';

		// Make the listbuilder's source type available to the cell template as a flag
		$flags['sourceType'] = $listbuilder->getSourceType();
		parent::__construct($id, $title, $titleTranslated, $template, $cellProvider, $flags);
	}
}

?>
