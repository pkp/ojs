<?php

/**
 * @defgroup plugins_citationOutput_abnt
 */

/**
 * @file plugins/citationOutput/abnt/AbntCitationOutputPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AbntCitationOutputPlugin
 * @ingroup plugins_citationOutput_abnt
 *
 * @brief ABNT citation style plug-in.
 */


import('lib.pkp.plugins.citationOutput.abnt.PKPAbntCitationOutputPlugin');

class AbntCitationOutputPlugin extends PKPAbntCitationOutputPlugin {
	/**
	 * Constructor
	 */
	function AbntCitationOutputPlugin() {
		parent::PKPAbntCitationOutputPlugin();
	}
}

?>
