<?php

/**
 * @file tools/mergeUsers.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class mergeUsers
 * @ingroup tools
 *
 * @brief CLI tool for merging two user accounts.
 */

require(dirname(__FILE__) . '/bootstrap.inc.php');

$tool = new \PKP\cliTool\MergeUsersTool($argv ?? []);
$tool->execute();
