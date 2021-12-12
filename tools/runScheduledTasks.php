<?php

/**
 * @file tools/runScheduledTasks.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class runScheduledTasks
 * @ingroup tools
 *
 * @brief CLI tool to execute a set of scheduled tasks.
 */

require(dirname(__FILE__) . '/bootstrap.inc.php');

class runScheduledTasks extends \PKP\cliTool\ScheduledTaskTool
{
}

$tool = new runScheduledTasks($argv ?? []);
$tool->execute();
