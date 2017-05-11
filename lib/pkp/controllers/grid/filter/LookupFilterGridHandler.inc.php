<?php
/**
 * @file lib/pkp/controllers/grid/filter/LookupFilterGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LookupFilterGridHandler
 * @ingroup controllers_grid_filter
 *
 * @brief Defines the filters that will be configured in this grid.
 */

import('classes.controllers.grid.filter.FilterGridHandler');

class LookupFilterGridHandler extends FilterGridHandler {
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
		$this->setFilterGroupSymbolic(CITATION_LOOKUP_FILTER_GROUP);

		// Set the title and form description of this grid
		$this->setTitle('manager.setup.filter.lookup.grid.title');
		$this->setFormDescription('manager.setup.filter.lookup.grid.description');

		parent::initialize($request);
	}
}

?>
