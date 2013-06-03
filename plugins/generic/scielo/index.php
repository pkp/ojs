<?php

/**
 * @defgroup plugins_generic_scielo
 */

/**
 * @file plugins/generic/scielo/index.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_scielo
 * @brief Wrapper for scielo statistics plugin.
 *
 */
require_once('SciELOPlugin.inc.php');

return new SciELOPlugin();

?>
