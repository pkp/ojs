<?php

/**
 * @file tools/deleteIncompleteSubmissions.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2003-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class deleteIncompleteSubmissions
 * @ingroup tools
 *
 * @brief CLI tool to delete incomplete submissions
 */

use APP\facades\Repo;

require(dirname(__FILE__) . '/bootstrap.inc.php');

class IncompleteSubmissionDeletionTool extends CommandLineTool
{
    /**
     * Constructor.
     *
     * @param array $argv command-line arguments
     */
    public function __construct($argv = [])
    {
        parent::__construct($argv);

        // If no parameters, show usage
        if (!sizeof($this->argv)) {
            $this->usage();
            exit(1);
        }

        $this->days = $argv[1];
        $this->dryrun = false;

        if (count($argv) > 2) {
            if ($argv[2] === '--dryrun') {
                $this->dryrun = $argv[2];
            } else {
                echo "Unknown argument\n";
                exit;
            }
        }
    }

    /**
     * Print command usage information.
     */
    public function usage()
    {
        echo "Permanently removes incomplete submissions.\n"
            . "Usage: {$this->scriptName} days [--dryrun]\n"
            . "\t\tdays: The number of days since the submission was last active, for example 365 is all submissions older than 1 year\n"
            . "\t\t--dryrun: Only list the incomplete submission id's to be removed\n";
    }

    /**
     * Delete submission data and associated files
     */
    public function execute()
    {
        if (!is_numeric($this->days)) {
            echo "Number of days has to be numeric\n";
            exit(1);
        }


        // Fetch all incomplete submission that are older than $this->days days
        $contextDao = \APP\core\Application::getContextDAO();
        $contexts = $contextDao->getAll();
        while ($context = $contexts->next()) {
            $collector = Repo::submission()->getCollector()->filterByContextIds([$context->getData('id')])
                ->limit(50)
                ->offset(0);
            $collector->filterByIncomplete(true)->filterByDaysInactive($this->days);
            $this->deleteArticles($collector);
        }
    }

    public function deleteArticles(\APP\submission\Collector $collector)
    {
        $submissions = Repo::submission()->getMany($collector);
        foreach ($submissions as $submission) {
            $article = Repo::submission()->get($submission->getId());
            if (!isset($article)) {
                printf('Error: Skipping ' . $submission->getId() . "Unknown submission.\n");
                continue;
            }
            if ($this->dryrun) {
                echo 'Found incomplete submission: ' . $submission->getData('id') . "\n";
            } else {
                echo 'Deleting submission: ' . $submission->getData('id') . "\n";
                Repo::submission()->delete($article);
            }
        }
    }
}

$tool = new IncompleteSubmissionDeletionTool($argv ?? []);
$tool->execute();
