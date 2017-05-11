<?php
/**
 * @defgroup plugins_citationOutput_mla MLA Citation Format
 */

/**
 * @file plugins/citationOutput/mla/PKPMlaCitationOutputPlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPMlaCitationOutputPlugin
 * @ingroup plugins_citationOutput_mla
 *
 * @brief Cross-application MLA citation style plugin
 */


import('lib.pkp.classes.plugins.Plugin');

class PKPMlaCitationOutputPlugin extends Plugin {
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
		return 'MlaCitationOutputPlugin';
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.citationOutput.mla.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.citationOutput.mla.description');
	}
}

?>
