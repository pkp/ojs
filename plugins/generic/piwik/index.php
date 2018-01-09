<?php

/**
 * @defgroup plugins_generic_piwik
 */

/**
 * @file plugins/generic/piwik/index.php
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_piwik
 * @brief Wrapper for Piwik plugin.
 *
 */


require_once('PiwikPlugin.inc.php');

return new PiwikPlugin();

?>
