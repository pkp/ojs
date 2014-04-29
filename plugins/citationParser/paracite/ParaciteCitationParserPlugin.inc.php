<?php

/**
 * @defgroup plugins_citationParser_paracite
 */

/**
 * @file plugins/citationParser/paracite/ParaciteCitationParserPlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ParaciteCitationParserPlugin
 * @ingroup plugins_citationParser_paracite
 *
 * @brief ParaCite citation extraction connector plug-in.
 */


import('lib.pkp.plugins.citationParser.paracite.PKPParaciteCitationParserPlugin');

class ParaciteCitationParserPlugin extends PKPParaciteCitationParserPlugin {
	/**
	 * Constructor
	 */
	function ParaciteCitationParserPlugin() {
		parent::PKPParaciteCitationParserPlugin();
	}
}

?>
