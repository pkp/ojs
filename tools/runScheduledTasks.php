<?php

/**
 * @file tools/runScheduledTasks.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class runScheduledTasks
 *
 * @ingroup tools
 *
 * @brief CLI tool to execute a set of scheduled tasks.
 *
 * @deprecated 3.5.0    use the command line tool `lib/pkp/tools/scheduler.php run` to set
 *                      in the crontab to run schedule tasks.
 */

require(dirname(__FILE__) . '/bootstrap.php');

// We need to push the appropriate option to the command line tool and in this case
// as we intend to run the schedule tasks, it's `run`
array_push($argv, 'run');

require(BASE_SYS_DIR . '/lib/pkp/tools/scheduler.php');
