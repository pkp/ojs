<?php

/**
 * @defgroup plugins_generic_coins COinS Plugin
 */
 
/**
 * @file plugins/generic/coins/index.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_coins
 * @brief Wrapper for COinS plugin.
 *
 */

require_once('CoinsPlugin.inc.php');

return new CoinsPlugin();

?>
