<?php
/**
 * @defgroup plugins_citationOutput_vancouver Vancouver Citation Format
 */

/**
 * @file plugins/citationOutput/vancouver/PKPVancouverCitationOutputPlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPVancouverCitationOutputPlugin
 * @ingroup plugins_citationOutput_vancouver
 *
 * @brief Cross-application Vancouver citation style plugin
 */


import('lib.pkp.classes.plugins.Plugin');

class PKPVancouverCitationOutputPlugin extends Plugin {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Override protected template methods from Plugin
	//
	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path) {
		if (!parent::register($category, $path)) return false;
		$this->addLocaleData();
		return true;
	}

	/**
	 * @copydoc Plugin::getName()
	 */
	function getName() {
		return 'VancouverCitationOutputPlugin';
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.citationOutput.vancouver.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.citationOutput.vancouver.description');
	}
}

?>
