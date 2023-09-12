<?php

/**
 * @file tools/stampJournalIdentityMetadata.php
 *
 * Copyright (c) 2023 Simon Fraser University
 * Copyright (c) 2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StampJournalIdentityMetadata
 *
 * @ingroup tools
 *
 * @brief CLI tool to to stamp journal identity metadata to issues and publications
 */

use APP\core\Application;
use APP\facades\Repo;
use APP\journal\Journal;
use APP\journal\JournalDAO;
use PKP\cliTool\CommandLineTool;

require(dirname(__FILE__) . '/bootstrap.php');

class StampJournalIdentityMetadata extends CommandLineTool
{
    public string $command;
    public array $parameters;

    /** Fields to be defined for stamping,
     *  use null if it should not be considered
     */
    // required fields:
    public $locale = 'en';
    public $title = [
        'en' => 'Journal Title in en',
        'de' => 'Journal Title in de'
    ];

    public $onlineIssn = '1234-1234';
    public $printIssn = '1234-1234';


    /**
     * Constructor.
     *
     * @param array $argv command-line arguments
     */
    public function __construct($argv = [])
    {
        parent::__construct($argv);
        if (count($this->argv) < 1) {
            $this->usage();
            exit();
        }
        $this->command = array_shift($this->argv);
        $this->parameters = $this->argv;
    }

    /**
     * Print command usage information.
     */
    public function usage()
    {
        echo "Adds journal identity metadata to issues and publications.\n"
            . "Usage:\n"
            . "\t{$this->scriptName} issue_id [...]\n"
            . "\t{$this->scriptName} year [...]\n";
    }

    /**
     * Delete submission data and associated files
     */
    public function execute()
    {
        if ($this->command === 'issue_id') {
            foreach ($this->parameters as $issueId) {
                $issue = Repo::issue()->get($issueId);
                if (!isset($issue)) {
                    printf("Error: Skipping {$issueId}. Unknown issue.\n");
                    continue;
                }
                /** @var JournalDAO $contextDao */
                $contextDao = Application::getContextDAO();
                /** @var Journal $context */
                $context = $contextDao->getById($issue->getData('journalId'));
                $contextPrimaryLocale = $context->getPrimaryLocale();
                $issue->setData('contextName', $this->title);
                if (!in_array($contextPrimaryLocale, array_keys($this->title))) {
                    $issue->setData('contextName', $this->title[$this->locale], $contextPrimaryLocale);
                }
                $issue->setData('onlineIssn', $this->onlineIssn);
                $issue->setData('printIssn', $this->printIssn);
                Repo::issue()->edit($issue, []);

                $submissionIds = Repo::submission()
                    ->getCollector()
                    ->filterByContextIds([$issue->getData('journalId')])
                    ->filterByIssueIds([$issueId])
                    ->getIds()
                    ->toArray();
                $publications = Repo::publication()
                    ->getCollector()
                    ->filterByContextIds([$issue->getData('journalId')])
                    ->filterBySubmissionIds($submissionIds)
                    ->getMany();
                foreach ($publications as $publication) {
                    if ($publication->getData('issueId') != $issueId) {
                        continue;
                    }
                    $publication->setData('contextName', $this->title);
                    $publicationLocale = $publication->getData('locale');
                    if (!in_array($publicationLocale, array_keys($this->title))) {
                        $issue->setData('contextName', $this->title[$this->locale], $publicationLocale);
                    }
                    $publication->setData('onlineIssn', $this->onlineIssn);
                    $publication->setData('printIssn', $this->printIssn);
                    Repo::publication()->edit($publication, []);
                }
            }
        }
    }
}

$tool = new StampJournalIdentityMetadata($argv ?? []);
$tool->execute();
