<?php

/**
 * driver.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Core system initialization code.
 * This file is loaded before any others.
 * Any system-wide imports or initialization code should be placed here. 
 *
 * $Id$
 */


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
ini_set('include_path', BASE_SYS_DIR . '/includes'
	. ENV_SEPARATOR . BASE_SYS_DIR . '/classes'
	. ENV_SEPARATOR . BASE_SYS_DIR . '/pages'
	. ENV_SEPARATOR . BASE_SYS_DIR . '/lib'
	. ENV_SEPARATOR . BASE_SYS_DIR . '/lib/smarty'
	. ENV_SEPARATOR . ini_get('include_path')
);


// Seed random number generator
mt_srand(((double) microtime()) * 1000000);

// System-wide functions
require('functions.inc.php');

/**
 * System class imports.
 * Only classes used system-wide should be included here.
 */

// Only system-wide includes should be here

import('core.Core');
import('core.Request');
import('core.DataObject');
import('core.Handler');
import('core.String');
import('core.Registry');

import('config.Config');

import('db.DBConnection');
import('db.DAO');
import('db.XMLDAO');
import('db.DAORegistry');

import('i18n.Locale');

import('security.Validation');
import('session.SessionManager');
import('template.TemplateManager');

import('submission.common.Action');

import('help.Help');

import('search.ArticleSearchIndex');

/**
 * System initialization (post-classloading).
 */

// Initialize string wrapper library
String::init();
?>
