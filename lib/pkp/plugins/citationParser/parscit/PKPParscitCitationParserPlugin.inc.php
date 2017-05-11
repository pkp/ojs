<?php
/**
 * @defgroup plugins_citationParser_parscit ParsCit Citation Parser
 */

/**
 * @file plugins/citationParser/parscit/PKPParscitCitationParserPlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPParscitCitationParserPlugin
 * @ingroup plugins_citationParser_parscit
 *
 * @brief Cross-application ParsCit citation parser
 */


import('lib.pkp.classes.plugins.Plugin');

class PKPParscitCitationParserPlugin extends Plugin {
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
		return 'ParscitCitationParserPlugin';
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.citationParser.parscit.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.citationParser.parscit.description');
	}
}

?>
