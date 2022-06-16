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
        if (count($argv) > 2) {
            $this->context = $argv[2];
        }
        if (count($argv) > 3) {
            $this->dryrun = $argv[3];
        }
    }

    /**
     * Print command usage information.
     */
    public function usage()
    {
        echo "Permanently removes incomplete submissions.\n"
            . "Usage: {$this->scriptName} months context_id  -dryrun\n"
            . "\t\tmonths: The number of months since the submission was last active, for example 24 is all submissions older than 2 years\n"
            . "\t\tcontext_id: Limit to a given context instead of searching site wide\n"
            . "\t\-dryrun: Only list the incomplete submission id's to be removed\n";
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


        // Fetch all incomplete submission that are older than x monhts
        $contextDao = \APP\core\Application::getContextDAO();
        $contexts = $contextDao->getAll();
        while ($context = $contexts->next()) {
            $collector = Repo::submission()->getCollector()->filterByContextIds([$context->getData('id')])
                ->limit(50)
                ->offset(0);
            $collector->filterByIncomplete(true)->filterByDaysInactive($this->days);

            $submissions = Repo::submission()->getMany($collector);
            foreach ($submissions as $submission) {
                echo $submission->getId();
            }
        }





        // Loop through, apply our criteria, generate an array of id's to be removed
        // Criteria
        // 1. No files attached
        // 2. No metadata, excluding section / categories given in the beginning
        // 3.


        //






        foreach ($this->parameters as $articleId) {
            $article = Repo::submission()->get($articleId);
            if (!isset($article)) {
                printf("Error: Skipping ${articleId}. Unknown submission.\n");
                continue;
            }
            Repo::submission()->delete($article);
        }
    }
}

$tool = new IncompleteSubmissionDeletionTool($argv ?? []);
$tool->execute();
