<?php

/**
 * @defgroup plugins_generic_sword
 */

/**
 * @file plugins/generic/sword/index.php
 *
 * Copyright (c) 2013-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_sword
 * @brief Wrapper for sword deposit plugin.
 *
 */


require_once('SwordPlugin.inc.php');

return new SwordPlugin();

?>
