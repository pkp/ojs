<?php

/**
 * @file tools/bootstrap.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup tools
 *
 * @brief application-specific configuration common to all tools (corresponds
 *  to index.php for web requests).
 *
 * FIXME: Write a PKPCliRequest and PKPCliRouter class and use the dispatcher
 *  to bootstrap and route tool requests.
 */

// $Id$


define('INDEX_FILE_LOCATION', dirname(dirname(__FILE__)) . '/index.php');
require(dirname(dirname(__FILE__)) . '/lib/pkp/classes/cliTool/CliTool.inc.php');

// Initialize the application environment
import('core.OJSApplication');
$application = new OJSApplication();

// Initialize the a request object with a page router
import('core.PKPPageRouter');
$request =& $application->getRequest();
$router =& new PKPPageRouter();
$router->setApplication($application);
$request->setRouter($router);

// Initialize the locale and load generic plugins.
Locale::initialize();
PluginRegistry::loadCategory('generic');
?>
