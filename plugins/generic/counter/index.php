<?php

/**
 * @defgroup plugins_generic_counter Counter Plugin
 */
 
/**
 * @file plugins/generic/counter/index.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_counter
 * @brief Wrapper for COUNTER stats plugin.
 *
 */

require_once('CounterPlugin.inc.php');

return new CounterPlugin();

?>
