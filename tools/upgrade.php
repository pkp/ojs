<?php

/**
 * @file tools/upgrade.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class upgradeTool
 * @ingroup tools
 *
 * @brief CLI tool for upgrading OJS.
 *
 * Note: Some functions require fopen wrappers to be enabled.
 */

require(dirname(__FILE__) . '/bootstrap.inc.php');
import('lib.pkp.classes.cliTool.UpgradeTool');

$tool = new UpgradeTool(isset($argv) ? $argv : array());
$tool->execute();

