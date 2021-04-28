<?php

/**
 * @file tools/runQueuedJobs.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class runQueuedJobs
 * @ingroup tools
 *
 * @brief CLI tool to manually run queued jobs
 */

use APP\core\Application;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\DB;

use PKP\core\PKPContainer;
use PKP\handler\PKPHandler;

require(dirname(__FILE__) . '/bootstrap.inc.php');

class runQueuedJobs extends CommandLineTool
{
    /**
     * Constructor.
     *
     * @param array $argv
     */
    public function __construct($argv = [])
    {
        parent::__construct($argv);
        if (sizeof($this->argv) != 1) {
            $this->usage();
            exit(1);
        }

        // -- User authentication for CLI tools -- //

        // Get the JWT from one of the CLI arguments
        $jwt = array_shift($this->argv);

        // Get the router and give it a new handler,
        // as there will not be one by default when running a CLI tool
        $router = & Application::get()->getRequest()->getRouter();
        $router->setHandler(new PKPHandler());

        // Decode JWT and set to handler
        $apiToken = JWT::decode($jwt, Config::getVar('security', 'api_key_secret', ''), ['HS256']);
        $router->getHandler()->setApiToken($apiToken);
    }

    /**
     * Print command usage information.
     */
    public function usage()
    {
        echo "Script to manually run queued jobs\n"
            . "Usage:\n"
            . "\t{$this->scriptName} <apiKey> \n";
    }

    /**
     * Manually run all queued jobs.
     */
    public function execute()
    {
        $countRunning = DB::table('jobs')
            ->where('queue', '=', 'default')
            ->whereNotNull('reserved_at')
            ->count();
        $countPending = $this->countPending();

        // Don't run another job if one is already running.
        // This should ensure jobs are run one after the other and
        // prevent long-running jobs from running simultaneously
        // and piling onto the server like a DDOS attack.
        if (!$countRunning && $countPending) {
            $laravelContainer = PKPContainer::getInstance();
            $options = new Illuminate\Queue\WorkerOptions();
            $laravelContainer['queue.worker']->runNextJob('database', 'default', $options);

            // Update count of pending Jobs
            $countPending = $this->countPending();
        }
    }

    private function countPending(): int
    {
        return DB::table('jobs')
            ->where('queue', '=', 'default')
            ->count();
    }
}

$tool = new runQueuedJobs($argv ?? []);
$tool->execute();
