<?php
/**
 * @defgroup plugins_citationParser_freecite FreeCite Citation Parser
 */

/**
 * @file plugins/citationParser/freecite/PKPFreeciteCitationParserPlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPFreeciteCitationParserPlugin
 * @ingroup plugins_citationParser_freecite
 *
 * @brief Cross-application FreeCite citation parser
 */


import('lib.pkp.classes.plugins.Plugin');

class PKPFreeciteCitationParserPlugin extends Plugin {
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
		return 'FreeciteCitationParserPlugin';
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.citationParser.freecite.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.citationParser.freecite.description');
	}
}

?>
