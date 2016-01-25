<?php

/**
 * @defgroup plugins_generic_googleAnalytics Google Analytics Plugin
 */
 
/**
 * @file plugins/generic/googleAnalytics/index.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_googleAnalytics
 * @brief Wrapper for Google Analytics plugin.
 *
 */

require_once('GoogleAnalyticsPlugin.inc.php');

return new GoogleAnalyticsPlugin();

?>
