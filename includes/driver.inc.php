<?php

/**
 * @defgroup index
 */
 
/**
 * @file driver.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup index
 *
 * @brief Core system initialization code.
 * This file is loaded before any others.
 * Any system-wide imports or initialization code should be placed here. 
 */

// $Id$



/**
 * Basic initialization (pre-classloading).
 */

// Update include path
define('ENV_SEPARATOR', strtolower(substr(PHP_OS, 0, 3)) == 'win' ? ';' : ':');
if (!defined('DIRECTORY_SEPARATOR')) {
	// Older versions of PHP do not define this
	define('DIRECTORY_SEPARATOR', strtolower(substr(PHP_OS, 0, 3)) == 'win' ? '\\' : '/');
}
define('BASE_SYS_DIR', dirname(dirname(__FILE__)));
ini_set('include_path', '.'
	. ENV_SEPARATOR . BASE_SYS_DIR . '/includes'
	. ENV_SEPARATOR . BASE_SYS_DIR . '/classes'
	. ENV_SEPARATOR . BASE_SYS_DIR . '/pages'
	. ENV_SEPARATOR . BASE_SYS_DIR . '/lib/pkp/classes'
	. ENV_SEPARATOR . BASE_SYS_DIR . '/lib/pkp/lib/adodb'
	. ENV_SEPARATOR . BASE_SYS_DIR . '/lib/pkp/lib/smarty'
	. ENV_SEPARATOR . ini_get('include_path')
);

// System-wide functions
require('functions.inc.php');

/**
 * System initialization (post-classloading).
 */

import('core.OJSApplication');
$ojsApplication =& new OJSApplication();
PKPApplication::initialize($ojsApplication);

?>
