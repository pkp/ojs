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

use PKP\cliTool\CommandLineTool;
use PKP\controlledVocab\ControlledVocab;
use PKP\controlledVocab\ControlledVocabEntryDAO;
use PKP\db\DAORegistry;
use PKP\user\UserInterest;

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
        if (!count($orphans)) {
            echo "No user interests to remove.\n";
            exit(0);
        }

        $command = $this->parameters[0];
        switch ($command) {
            case '--show':
                $interests = array_map(function ($entry) {
                    return $entry->getData(UserInterest::CONTROLLED_VOCAB_INTEREST);
                }, $orphans);
                echo "Below are the user interests that are not referenced by any user account.\n";
                echo "\t" . join("\n\t", $interests) . "\n";
                break;

            case '--remove':
                /** @var ControlledVocabEntryDAO */
                $vocabEntryDao = DAORegistry::getDAO('ControlledVocabEntryDAO');
                foreach ($orphans as $orphanVocab) {
                    $vocabEntryDao->deleteObject($orphanVocab);
                }
                echo count($orphans) . " entries deleted\n";
                break;

            default:
                echo "Invalid command.\n";
                $this->usage();
                exit(2);
        }
    }

    /**
     * Returns user interests that are not referenced
     *
     * @return array array of ControlledVocabEntry object
     */
    protected function _getOrphanVocabInterests(): array
    {
        /** @var ControlledVocabEntryDAO $vocabEntryDao */
        $vocabEntryDao = DAORegistry::getDAO('ControlledVocabEntryDAO');
        $interestVocab = ControlledVocab::withSymbolic(UserInterest::CONTROLLED_VOCAB_INTEREST)
            ->withAssoc(0, 0)
            ->first();
        $vocabEntryIterator = $vocabEntryDao->getByControlledVocabId($interestVocab->id);
        $vocabEntryList = $vocabEntryIterator->toArray();

        // list of vocab interests in db
        $allInterestVocabIds = array_map(fn ($entry) => $entry->getId(), $vocabEntryList);

        // list of vocabs associated to users
        $interests = UserInterest::getAllInterests();
        $userInterestVocabIds = array_map(
            fn ($interest) => $interest->getId(),
            $interests->toArray()
        );

        // get the difference
        $diff = array_diff($allInterestVocabIds, $userInterestVocabIds);

        $orphans = array_filter(
            $vocabEntryList,
            function ($entry) use ($diff) {
                return in_array($entry->getId(), $diff);
            }
        );

        return $orphans;
    }
}

$tool = new ReviewerInterestsDeletionTool($argv ?? []);
$tool->execute();
