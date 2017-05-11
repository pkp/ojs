<?php
/**
 * @file lib/pkp/controllers/grid/filter/ParserFilterGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ParserFilterGridHandler
 * @ingroup controllers_grid_filter
 *
 * @brief Defines the filters that will be configured in this grid.
 */

import('classes.controllers.grid.filter.FilterGridHandler');

class ParserFilterGridHandler extends FilterGridHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * @copydoc PKPHandler::initialize()
	 */
	function initialize($request) {
		// Set the filter group defining the filters
		// configured in this grid.
		$this->setFilterGroupSymbolic(CITATION_PARSER_FILTER_GROUP);

		// Set the title of this grid
		$this->setTitle('manager.setup.filter.parser.grid.title');
		$this->setFormDescription('manager.setup.filter.parser.grid.description');

		parent::initialize($request);
	}
}

?>
