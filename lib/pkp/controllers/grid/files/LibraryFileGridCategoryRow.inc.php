<?php

/**
 * @file controllers/grid/files/LibraryFileGridCategoryRow.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LibraryFileGridCategoryRow
 * @ingroup controllers_grid_settings_library
 *
 * @brief Library file grid category row definition
 */

import('lib.pkp.classes.controllers.grid.GridCategoryRow');
import('classes.file.LibraryFileManager');

class LibraryFileGridCategoryRow extends GridCategoryRow {
	/** the context for our Library file manager */
	var $_context;

	/**
	 * Constructor
	 */
	function __construct($context) {
		$this->_context =& $context;
		parent::__construct();
	}

	//
	// Overridden methods from GridCategoryRow
	//
	/**
	 * Category rows only have one cell and one label.  This is it.
	 * return string
	 */
	function getCategoryLabel() {
		$context = $this->getContext();
		$libraryFileManager = new LibraryFileManager($context->getId());
		return __($libraryFileManager->getTitleKeyFromType($this->getData()));
	}

	/**
	 * Get the context
	 * @return object context
	 */
	function getContext() {
		return $this->_context;
	}

	/**
	 * @copydoc GridCategoryRow::initialize()
	 */
	function initialize($request) {
		parent::initialize($request);
		$this->setId($this->getData());
	}
}
?>
