<?php

/**
 * @file tools/deleteSubmissions.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class deleteSubmissions
 * @ingroup tools
 *
 * @brief CLI tool to delete submissions
 */

use APP\facades\Repo;

require(dirname(__FILE__) . '/bootstrap.inc.php');

class SubmissionDeletionTool extends CommandLineTool
{
    public $articleIds;

    /**
     * Constructor.
     *
     * @param array $argv command-line arguments
     */
    public function __construct($argv = [])
    {
        parent::__construct($argv);

        if (!sizeof($this->argv)) {
            $this->usage();
            exit(1);
        }


        // Time limit, how old incomplete submissions shoudl be deleted. Enter the amount months
        // "24" means all older than 2 years

        $this->parameters = $this->argv;
    }

    /**
     * Print command usage information.
     */
    public function usage()
    {
        echo "Permanently removes submission(s) and associated information.  USE WITH CARE.\n"
            . "Usage: {$this->scriptName} submission_id [...]\n";
    }

    /**
     * Delete submission data and associated files
     */
    public function execute()
    {


        // Fetch all incomplete submission that are older than x monhts


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

$tool = new SubmissionDeletionTool($argv ?? []);
$tool->execute();
