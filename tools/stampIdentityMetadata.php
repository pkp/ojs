<?php

/**
 * @file tools/stampIdentityMetadata.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StampJournalIdentityMetadata
 *
 * @ingroup tools
 *
 * @brief CLI tool to re-stamp journal identity metadata onto issues and their publications
 *   from the journal's current settings. Use this to backfill historical records or to
 *   correct stamps after a journal identity change.
 */

use APP\core\Application;
use APP\facades\Repo;
use APP\journal\Journal;
use APP\journal\JournalDAO;
use PKP\cliTool\CommandLineTool;

require(dirname(__FILE__) . '/bootstrap.php');

class StampJournalIdentityMetadata extends CommandLineTool
{
    // =========================================================================
    // OVERRIDE SECTION
    // Fill in these values if the data to be stamped differs from the journal's
    // current live settings. Leave as null to use the journal's current settings.
    // =========================================================================

    /**
     * Journal name per locale.
     * e.g. ['en' => 'Old Journal Name', 'de' => 'Alter Zeitschriftentitel', 'fr' => 'Ancien nom de revue']
     *
     * @var array<string,string>|null
     */
    public ?array $contextNameOverride = null;

    /** @var string|null Print ISSN, e.g. '1234-5678' */
    public ?string $printIssnOverride = null;

    /** @var string|null Online ISSN, e.g. '8765-4321' */
    public ?string $onlineIssnOverride = null;

    /** @var string|null Publisher name, e.g. 'Old Publisher Name' */
    public ?string $publisherOverride = null;

    /** @var string|null Publisher location, e.g. 'Berlin' */
    public ?string $publisherLocationOverride = null;

    // =========================================================================

    public int $contextId;
    public string $command;
    public array $parameters;

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
        echo "Re-stamps journal identity metadata (name, ISSNs, publisher) onto issues\n"
            . "and their publications from the journal's current settings.\n"
            . "To stamp with different values, edit the override variables at the top of this file.\n"
            . "Usage:\n"
            . "\t{$this->scriptName} <context_id> issue_id <id> [<id> ...]\n"
            . "\t{$this->scriptName} <context_id> year <year_or_range> [<year_or_range> ...]\n"
            . "\t{$this->scriptName} <context_id> all\n"
            . "Year ranges are specified as YYYY-YYYY, e.g. 2010-2020.\n"
            . "'year' stamps matching issues and also any publications without an issue assignment published in those years.\n"
            . "Use 'all' to stamp everything: all issues, all publications, including those not assigned to an issue.\n";
    }

    /**
     * Re-stamp identity metadata on the targeted issues and all their publications.
     */
    public function execute()
    {
        /** @var JournalDAO $contextDao */
        $contextDao = Application::getContextDAO();
        /** @var Journal $context */
        $context = $contextDao->getById($this->contextId);
        if (!$context) {
            printf("Error: Unknown context ID %d.\n", $this->contextId);
            exit(1);
        }

        if ($this->command === 'all') {
            $this->stampAll($context);
            return;
        }

        if ($this->command === 'issue_id') {
            $issueIds = array_map('intval', $this->parameters);
            if (empty($issueIds)) {
                echo "Error: No issue IDs provided.\n";
                $this->usage();
                exit(1);
            }
            foreach ($issueIds as $issueId) {
                $this->stampIssue($issueId, $context);
            }
            return;
        }

        if ($this->command === 'year') {
            $years = $this->parseYears($this->parameters);
            if (empty($years)) {
                echo "Error: No valid years or year ranges provided.\n";
                exit(1);
            }
            $issueIds = Repo::issue()->getCollector()
                ->filterByContextIds([$this->contextId])
                ->filterByYears($years)
                ->getMany()
                ->map(fn ($issue) => $issue->getId())
                ->values()
                ->all();
            foreach ($issueIds as $issueId) {
                $this->stampIssue($issueId, $context);
            }
            $this->stampIssueFreePubsByYear($context, $years);
            return;
        }

        printf("Error: Unknown command '%s'. Expected 'issue_id', 'year', or 'all'.\n", $this->command);
        $this->usage();
        exit(1);
    }

    /**
     * Stamp all issues and all publications in the context, including those not assigned to an issue.
     */
    protected function stampAll(Journal $context): void
    {
        $issues = Repo::issue()->getCollector()
            ->filterByContextIds([$this->contextId])
            ->getMany();

        foreach ($issues as $issue) {
            $this->stampIssue($issue->getId(), $context);
        }

        // Stamp publications not assigned to any issue (issue-free journals or
        // articles published without an issue assignment).
        $issueFreePubCount = 0;
        $submissions = Repo::submission()->getCollector()
            ->filterByContextIds([$this->contextId])
            ->getMany();

        foreach ($submissions as $submission) {
            $publications = Repo::publication()->getCollector()
                ->filterBySubmissionIds([$submission->getId()])
                ->getMany();

            foreach ($publications as $publication) {
                if (!$publication->getData('issueId')) {
                    $publication->stampContextIdentity($context);
                    $this->applyOverrides($publication);
                    Repo::publication()->edit($publication, []);
                    $issueFreePubCount++;
                }
            }
        }

        if ($issueFreePubCount > 0) {
            printf("Stamped %d publication(s) not assigned to an issue.\n", $issueFreePubCount);
        }
    }

    /**
     * Stamp a single issue and all its publications.
     */
    protected function stampIssue(int $issueId, Journal $context): void
    {
        $issue = Repo::issue()->get($issueId, $this->contextId);
        if (!$issue) {
            printf("Error: Skipping issue %d — unknown or does not belong to context %d.\n", $issueId, $this->contextId);
            return;
        }

        $issue->stampContextIdentity();
        $this->applyOverrides($issue);
        Repo::issue()->edit($issue, []);

        $publications = Repo::publication()
            ->getCollector()
            ->filterByIssueIds([$issue->getId()])
            ->getMany();

        $publicationCount = 0;
        foreach ($publications as $publication) {
            $publication->stampContextIdentity($context);
            $this->applyOverrides($publication);
            Repo::publication()->edit($publication, []);
            $publicationCount++;
        }

        printf("Stamped issue %d and %d publication(s).\n", $issueId, $publicationCount);
    }

    /**
     * Apply any user-specified overrides to an issue or publication after stampContextIdentity().
     * Only non-null overrides are applied; null means "use whatever stampContextIdentity() set".
     */
    protected function applyOverrides(object $object): void
    {
        if ($this->contextNameOverride !== null) {
            foreach ($this->contextNameOverride as $locale => $name) {
                $object->setData('contextName', $name, $locale);
            }
        }
        if ($this->printIssnOverride !== null) {
            $object->setData('printIssn', $this->printIssnOverride);
        }
        if ($this->onlineIssnOverride !== null) {
            $object->setData('onlineIssn', $this->onlineIssnOverride);
        }
        if ($this->publisherOverride !== null) {
            $object->setData('publisher', $this->publisherOverride);
        }
        if ($this->publisherLocationOverride !== null) {
            $object->setData('publisherLocation', $this->publisherLocationOverride);
        }
    }

    /**
     * Stamp publications not assigned to any issue, filtered by their datePublished year.
     * Used for journals that do not use issues.
     */
    protected function stampIssueFreePubsByYear(Journal $context, array $years): void
    {
        $count = 0;
        $submissions = Repo::submission()->getCollector()
            ->filterByContextIds([$this->contextId])
            ->getMany();

        foreach ($submissions as $submission) {
            $publications = Repo::publication()->getCollector()
                ->filterBySubmissionIds([$submission->getId()])
                ->getMany();

            foreach ($publications as $publication) {
                $datePublished = $publication->getData('datePublished');
                if (!$datePublished) {
                    continue;
                }
                if (!in_array((int) date('Y', strtotime($datePublished)), $years)) {
                    continue;
                }
                $publication->stampContextIdentity($context);
                $this->applyOverrides($publication);
                Repo::publication()->edit($publication, []);
                $count++;
            }
        }

        if ($count > 0) {
            printf("Stamped %d publication(s) published in the given year(s).\n", $count);
        } else {
            echo "No publications found published in the given year(s).\n";
        }
    }

    /**
     * Parse a list of year strings (e.g. '2010', '2015-2020') into a flat array of integers.
     */
    protected function parseYears(array $params): array
    {
        $years = [];
        foreach ($params as $param) {
            if (str_contains($param, '-')) {
                [$start, $end] = explode('-', $param, 2);
                if (strlen($start) === 4 && ctype_digit($start) && strlen($end) === 4 && ctype_digit($end)) {
                    foreach (range((int)$start, (int)$end) as $year) {
                        $years[$year] = true;
                    }
                } else {
                    printf("Warning: Skipping invalid year range '%s'.\n", $param);
                }
            } elseif (strlen($param) === 4 && ctype_digit($param)) {
                $years[(int)$param] = true;
            } else {
                printf("Warning: Skipping invalid year '%s'.\n", $param);
            }
        }
        return array_keys($years);
    }
}

$tool = new StampJournalIdentityMetadata($argv ?? []);
$tool->execute();
