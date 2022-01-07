<?php

/**
 * @file tools/bootstrap.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup tools
 *
 * @brief application-specific configuration common to all tools (corresponds
 *  to index.php for web requests).
 */

define('INDEX_FILE_LOCATION', dirname(__FILE__, 2) . '/index.php');
require dirname(__FILE__, 2) . '/lib/pkp/classes/cliTool/CommandLineTool.inc.php';
