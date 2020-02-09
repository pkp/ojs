<?php

/**
 * @file tools/runScheduledTasks.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class runScheduledTasks
 * @ingroup tools
 *
 * @brief CLI tool to execute a set of scheduled tasks.
 */

require(dirname(__FILE__) . '/bootstrap.inc.php');

import('lib.pkp.classes.cliTool.ScheduledTaskTool');

class runScheduledTasks extends ScheduledTaskTool {
	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 * 		If specified, the first parameter should be the path to
	 *		a tasks XML descriptor file (other than the default)
	 */
	function __construct($argv = array()) {
		parent::__construct($argv);
	}

}

$tool = new runScheduledTasks(isset($argv) ? $argv : array());
$tool->execute();


