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

// Useful for debugging purposes -- may want to disable for release version?
error_reporting(E_ALL);

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

define('REALLY_BIG_NUMBER', 10000);

// Seed random number generator
mt_srand(((double) microtime()) * 1000000);

// System-wide functions
require('functions.inc.php');


/**
 * System class imports.
 * Only classes used system-wide should be included here.
 */
import('core.Core');
import('core.Request');
import('core.DataObject');
import('core.Handler');
import('core.String');
import('core.Registry');
import('core.ArrayItemIterator');
import('core.VirtualArrayIterator');

import('config.Config');

import('db.DBConnection');
import('db.DAO');
import('db.DAOResultFactory');
import('db.DBRowIterator');
import('db.XMLDAO');
import('db.DAORegistry');

import('i18n.Locale');

import('security.Validation');
import('session.SessionManager');
import('template.TemplateManager');

import('submission.common.Action');

import('help.Help');

import('plugins.PluginRegistry');
import('plugins.HookRegistry');

/**
 * System initialization (post-classloading).
 */

// Initialize string wrapper library
String::init();

// Can we serve a cached response?
if (Request::isCacheable()) {
	if (Request::displayCached()) exit(); // Success
	ob_start(array('Request', 'cacheContent'));
}

// Load the main locale file
Locale::initialize();

// Load the generic plugins
PluginRegistry::loadCategory('generic');

?>
