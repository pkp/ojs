<?php

/**
 * @file classes/plugins/PKPViewableFilePlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ViewableFilePlugin
 * @ingroup plugins
 *
 * @brief Abstract class for article galley plugins
 */

import('lib.pkp.classes.plugins.GenericPlugin');

abstract class PKPViewableFilePlugin extends GenericPlugin {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Get the filename of the template. (Default behavior may
	 * be overridden through some combination of this function and the
	 * displayArticleGalley function.)
	 * Returning null from this function results in an empty display.
	 *
	 * @return string
	 */
	function getTemplateFilename() {
		return 'display.tpl';
	}
}

?>
