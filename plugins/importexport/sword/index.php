<?php

/**
 * @defgroup plugins_importexport_sword
 */
 
/**
 * @file plugins/importexport/sword/index.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_sword
 * @brief Wrapper for sword deposit plugin.
 *
 */

// $Id: index.php,v 1.10 2010/01/21 18:52:12 asmecher Exp $


require_once('SwordDepositPlugin.inc.php');

return new SwordDepositPlugin();

?>
