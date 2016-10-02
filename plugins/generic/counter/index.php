<?php

/**
 * @defgroup plugins_generic_counter Counter Plugin
 */
 
/**
 * @file plugins/generic/counter/index.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_counter
 * @brief Wrapper for COUNTER stats plugin.
 *
 */

require_once('CounterPlugin.inc.php');

return new CounterPlugin();

?>
