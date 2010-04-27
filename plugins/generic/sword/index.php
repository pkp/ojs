<?php

/**
 * @defgroup plugins_generic_sword
 */
 
/**
 * @file plugins/generic/sword/index.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_sword
 * @brief Wrapper for sword deposit plugin.
 *
 */

// $Id: index.php,v 1.10 2010/01/21 18:52:12 asmecher Exp $


require_once('SwordPlugin.inc.php');

return new SwordPlugin();

?>
