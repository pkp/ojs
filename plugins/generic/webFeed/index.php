<?php

/**
 * @defgroup plugins_generic_webFeed Web Feed Plugin
 */
 
/**
 * @file plugins/generic/webFeed/index.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_webFeed
 * @brief Wrapper for Web Feeds plugin.
 *
 */

require_once('WebFeedPlugin.inc.php');

return new WebFeedPlugin(); 
