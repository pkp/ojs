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
    public int $contextId;
    public string $command;
    public array $parameters;

    /** Fields to be defined for stamping,
     *  use null if it should not be considered
     */
    // required fields:
    public ?string $locale = 'en';
    public ?array $title = [
        'en' => 'Journal Title in en',
        'de' => 'Journal des Tests'
    ];
    public $onlineIssn = '1234-1234';
    public $printIssn = '1234-1234';
    public $country = 'CA';
    public $publisherInstitution = 'SFU Library';
    public $publisherLocation = 'Vancouver';


    /**
     * Constructor.
     *
     * @param array $argv command-line arguments
     */
    public function __construct($argv = [])
    {
        parent::__construct($argv);
        if (count($this->argv) < 2) {
            $this->usage();
            exit();
        }
        $this->contextId = (int)array_shift($this->argv);
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
            . "\t{$this->scriptName} [context_id] issue_id [...]\n"
            . "\t{$this->scriptName} [context_id] year [...]\n";
    }

    /**
     * Stamp metadata to issue and publication
     */
    public function execute()
    {
        $issueIds = [];
        if ($this->command === 'issue_id') {
            foreach ($this->parameters as $issueId) {
                $issueIds[] = $issueId;
            }
        }

        if ($this->command === 'year') {
            $allYears = [];
            foreach ($this->parameters as $year) {
                if (str_contains($year, '-')) {
                    [$start, $end] = explode('-', $year);
                    if (strlen($start) === 4 && ctype_digit($start)
                        && strlen($end) === 4 && ctype_digit($end)) {
                        $years = range((int)$start, (int)$end);
                        $missingYears = array_diff($years, $allYears);
                        $allYears = array_merge($allYears, $missingYears);
                    }
                } elseif (strlen($year) === 4 && ctype_digit($year)) {
                    if (!in_array((int)$year, $allYears)) {
                        $allYears[] = (int)$year;
                    }
                }
            }

            $issues = Repo::issue()->getCollector()
                ->filterByContextIds([$this->contextId])
                ->filterByYears($allYears)
                ->getMany();

            foreach ($issues as $issue) {
                if (!in_array($issue->getId(), $issueIds)) {
                    $issueIds[] = $issue->getId();
                }
            }
        }

        foreach ($issueIds as $issueId) {
            $issue = Repo::issue()->get($issueId);
            if (!isset($issue)) {
                printf("Error: Skipping {$issueId}. Unknown issue.\n");
                continue;
            }
            /** @var JournalDAO $contextDao */
            $contextDao = Application::getContextDAO();
            /** @var Journal $context */
            $context = $contextDao->getById($this->contextId);
            $contextPrimaryLocale = $context->getPrimaryLocale();
            $issue->setData('contextName', $this->title);
            if (!in_array($contextPrimaryLocale, array_keys($this->title))) {
                $issue->setData('contextName', $this->title[$this->locale], $contextPrimaryLocale);
            }
            $issue->setData('onlineIssn', $this->onlineIssn);
            $issue->setData('printIssn', $this->printIssn);
            $issue->setData('country', $this->country);
            $issue->setData('publisherInstitution', $this->publisherInstitution);
            $issue->setData('publisherLocation', $this->publisherLocation);
            Repo::issue()->edit($issue, []);

            $submissionIds = Repo::submission()
                ->getCollector()
                ->filterByContextIds([$this->contextId])
                ->filterByIssueIds([$issueId])
                ->getIds()
                ->toArray();
            $publications = Repo::publication()
                ->getCollector()
                ->filterByContextIds([$this->contextId])
                ->filterByIssueIds([$issueId])
                ->filterBySubmissionIds($submissionIds)
                ->getMany();
            foreach ($publications as $publication) {
                $publication->setData('contextName', $this->title);
                $publicationLocale = $publication->getData('locale');
                if (!in_array($publicationLocale, array_keys($this->title))) {
                    $publication->setData('contextName', $this->title[$this->locale], $publicationLocale);
                }
                $publication->setData('onlineIssn', $this->onlineIssn);
                $publication->setData('printIssn', $this->printIssn);
                $publication->setData('country', $this->country);
                $publication->setData('publisherInstitution', $this->publisherInstitution);
                $publication->setData('publisherLocation', $this->publisherLocation);
                Repo::publication()->edit($publication, []);
            }
        }
    }
}

$tool = new StampJournalIdentityMetadata($argv ?? []);
$tool->execute();
