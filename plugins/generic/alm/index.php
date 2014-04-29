<?php

/**
 * @defgroup plugins_generic_alm
 */

/**
 * @file plugins/generic/alm/index.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_alm
 * @brief Wrapper for ALM plugin.
 *
 */


require_once('AlmPlugin.inc.php');

return new AlmPlugin();

?>
