<?php

/**
 * @defgroup plugins_generic_externalFeed
 */
 
/**
 * @file plugins/generic/externalFeed/index.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_externalFeed
 * @brief Wrapper for ExternalFeed plugin.
 *
 */

require_once('ExternalFeedPlugin.inc.php');

return new ExternalFeedPlugin();

?>
