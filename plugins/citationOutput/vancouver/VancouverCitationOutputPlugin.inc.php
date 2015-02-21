<?php

/**
 * @defgroup plugins_citationOutput_vancouver
 */

/**
 * @file plugins/citationOutput/vancouver/VancouverCitationOutputPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class VancouverCitationOutputPlugin
 * @ingroup plugins_citationOutput_vancouver
 *
 * @brief Vancouver citation style plug-in.
 */


import('lib.pkp.plugins.citationOutput.vancouver.PKPVancouverCitationOutputPlugin');

class VancouverCitationOutputPlugin extends PKPVancouverCitationOutputPlugin {
	/**
	 * Constructor
	 */
	function VancouverCitationOutputPlugin() {
		parent::PKPVancouverCitationOutputPlugin();
	}
}

?>
