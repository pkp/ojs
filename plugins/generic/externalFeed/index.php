<?php

/**
 * @defgroup plugins_generic_externalFeed External Feed Plugin
 */
 
/**
 * @file plugins/generic/externalFeed/index.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_externalFeed
 * @brief Wrapper for ExternalFeed plugin.
 *
 */

require_once('ExternalFeedPlugin.inc.php');

return new ExternalFeedPlugin();


