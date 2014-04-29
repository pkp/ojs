<?php

/**
 * @defgroup plugins_generic_usageEvent
 */

/**
 * @file plugins/generic/usageEvent/index.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_usageEvent
 * @brief Wrapper for usage event plugin.
 *
 */
require_once('UsageEventPlugin.inc.php');

return new UsageEventPlugin();

?>
