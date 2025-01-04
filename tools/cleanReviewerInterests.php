<?php

/**
 * @file tools/cleanReviewerInterests.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ReviewerInterestsDeletionTool
 *
 * @ingroup tools
 *
 * @brief CLI tool to remove user interests that are not referenced by any user accounts.
 */

require(dirname(__FILE__) . '/bootstrap.php');

use APP\core\Application;
use APP\facades\Repo;
use Illuminate\Support\Collection;
use PKP\cliTool\CommandLineTool;
use PKP\controlledVocab\ControlledVocabEntry;
use PKP\user\interest\UserInterest;

class ReviewerInterestsDeletionTool extends CommandLineTool
{
    public array $parameters;

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

        $this->parameters = $this->argv;
    }

    /**
     * Print command usage information.
     */
    public function usage()
    {
        echo "Permanently removes user interests that are not referenced by any user accounts.  USE WITH CARE.\n"
            . "Usage:\n"
            . "\t{$this->scriptName} --show : Display user interests not referenced\n"
            . "\t{$this->scriptName} --remove  : Permanently delete user interests not referenced\n";
    }

    /**
     * Remove user interests that are not referenced by any user account
     */
    public function execute(): void
    {
        $orphans = $this->_getOrphanVocabInterests();
        if ($orphans->count() === 0) {
            echo "No user interests to remove.\n";
            exit(0);
        }

        $command = $this->parameters[0];
        switch ($command) {
            case '--show':
                $interests = $orphans->pluck(UserInterest::CONTROLLED_VOCAB_INTEREST)->toArray();
                echo "Below are the user interests that are not referenced by any user account.\n";
                echo "\t" . join("\n\t", $interests) . "\n";
                break;

            case '--remove':
                echo $orphans->toQuery()->delete() . " entries deleted\n";
                break;

            default:
                echo "Invalid command.\n";
                $this->usage();
                exit(2);
        }
    }

    /**
     * Returns user interests collection that are not referenced
     */
    protected function _getOrphanVocabInterests(): Collection
    {
        $controlledVocab = Repo::controlledVocab()->build(
            UserInterest::CONTROLLED_VOCAB_INTEREST,
            Application::ASSOC_TYPE_SITE,
            Application::SITE_CONTEXT_ID,
        );

        return ControlledVocabEntry::query()
            ->withControlledVocabId($controlledVocab->id)
            ->whereDoesntHave('userInterest')
            ->get();
    }
}

$tool = new ReviewerInterestsDeletionTool($argv ?? []);
$tool->execute();
