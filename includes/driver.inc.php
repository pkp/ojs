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

// Useful for debugging purposes -- may want to disable for release version?
error_reporting(E_ALL);


// Update include path
define('BASE_SYS_DIR', dirname(dirname(__FILE__)));
ini_set('include_path', BASE_SYS_DIR . '/includes'
	. ':' . BASE_SYS_DIR . '/classes'
	. ':' . BASE_SYS_DIR . '/pages'
	. ':' . BASE_SYS_DIR . '/lib'
	. ':' . BASE_SYS_DIR . '/lib/smarty'
	. ':' . ini_get('include_path')
);


// System-wide functions
require('functions.inc.php');


// System-wide imports here for now
import('core.Core');
import('core.Request');
import('core.DataObject');
import('core.Handler');

import('config.Config');

import('db.DBConnection');
import('db.DAO');
import('db.XMLDAO');
import('db.DAORegistry'); 

import('form.Form');

import('i18n.Locale');

import('journal.Journal');
import('journal.JournalDAO');
import('journal.JournalSettingsDAO');
import('journal.Section');
import('journal.SectionDAO');

import('security.Role');
import('security.RoleDAO');
import('security.Validation');

import('session.SessionManager');
import('session.Session');
import('session.SessionDAO');

import('site.Site');
import('site.SiteDAO');

import('user.User');
import('user.UserDAO');

import('template.TemplateManager');

?>
