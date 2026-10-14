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
 * @brief CLI tool to re-stamp the journal identity metadata onto published issues and
 *   publications. Use this to backfill historical records or to correct stamps after a
 *   journal identity change.
 */

use APP\facades\Repo;
use APP\issue\Issue;
use APP\publication\Publication;
use PKP\cliTool\StampIdentityMetadataTool;
use PKP\validation\ValidatorISSN;

require(dirname(__FILE__) . '/bootstrap.php');

class StampJournalIdentityMetadata extends StampIdentityMetadataTool
{
    /** @var ?int[] IDs of the journal's published issues */
    protected ?array $publishedIssueIds = null;

    /**
     * @copydoc StampIdentityMetadataTool::getContextNoun()
     */
    protected function getContextNoun(): string
    {
        return 'journal';
    }

    /**
     * @copydoc StampIdentityMetadataTool::getScalarOptions()
     */
    protected function getScalarOptions(): array
    {
        $options = parent::getScalarOptions();
        return [
            'primary-locale' => $options['primary-locale'],
            'print-issn' => ['field' => 'printIssn', 'label' => 'Print ISSN', 'description' => 'Print ISSN'],
            'online-issn' => ['field' => 'onlineIssn', 'label' => 'Online ISSN', 'description' => 'Online ISSN'],
            'publisher' => ['field' => 'publisher', 'label' => 'Publisher', 'description' => 'Publisher name'],
            'publisher-location' => $options['publisher-location'],
        ];
    }

    /**
     * @copydoc StampIdentityMetadataTool::parseOptions()
     *
     * Also checks the ISSNs.
     */
    protected function parseOptions(): void
    {
        parent::parseOptions();
        $validator = new ValidatorISSN();
        foreach (['printIssn' => 'print-issn', 'onlineIssn' => 'online-issn'] as $field => $option) {
            $issn = $this->scalarOverrides[$field] ?? '';
            if ($issn !== '' && !$validator->isValid($issn)) {
                $this->exitWithError("Invalid ISSN '{$issn}' in --{$option}.");
            }
        }
    }

    /**
     * @copydoc StampIdentityMetadataTool::getCommands()
     */
    protected function getCommands(): array
    {
        return ['issue_id', ...parent::getCommands()];
    }

    /**
     * @copydoc StampIdentityMetadataTool::getCommandUsage()
     */
    protected function getCommandUsage(): array
    {
        return ['issue_id <id> [<id> ...]', ...parent::getCommandUsage()];
    }

    /**
     * @copydoc StampIdentityMetadataTool::getCommandNotes()
     */
    protected function getCommandNotes(): array
    {
        return [
            "'issue_id' stamps the given published issues and all their published publications, including later versions.",
            "'publication_id' stamps the given published publications, e.g. a single version.",
            "'submission_id' stamps all published publications (versions) of the given submissions.",
            "'year' stamps the published issues of the given years with their publications, and the publications without a\n"
                . 'published issue published in those years. Year ranges are YYYY-YYYY, e.g. 2010-2020.',
            "'all' stamps all published issues and all published publications.",
        ];
    }

    /**
     * @copydoc StampIdentityMetadataTool::getIdError()
     */
    protected function getIdError(int $id): ?string
    {
        if ($this->command !== 'issue_id') {
            return parent::getIdError($id);
        }
        $issue = Repo::issue()->get($id, $this->contextId);
        if (!$issue) {
            return "Unknown issue {$id}, or it does not belong to context {$this->contextId}.";
        }
        if (!$issue->getData('published')) {
            return "Issue {$id} is not published.";
        }
        return null;
    }

    /**
     * @copydoc StampIdentityMetadataTool::runCommand()
     */
    protected function runCommand(): void
    {
        if ($this->command !== 'issue_id') {
            parent::runCommand();
            return;
        }
        foreach ($this->parameters as $issueId) {
            $this->stampIssue(Repo::issue()->get((int) $issueId, $this->contextId));
        }
    }

    /**
     * Stamp all published issues with their publications, and the publications without a published issue.
     */
    protected function stampAll(): void
    {
        $issues = Repo::issue()->getCollector()
            ->filterByContextIds([$this->contextId])
            ->filterByPublished(true)
            ->getMany();
        foreach ($issues as $issue) {
            $this->stampIssue($issue);
        }
        parent::stampAll();
    }

    /**
     * Stamp the published issues of the given years with their publications, and the publications
     * without a published issue published in those years.
     */
    protected function stampByYears(array $years): void
    {
        $issues = Repo::issue()->getCollector()
            ->filterByContextIds([$this->contextId])
            ->filterByPublished(true)
            ->filterByYears($years)
            ->getMany();
        foreach ($issues as $issue) {
            $this->stampIssue($issue);
        }
        parent::stampByYears($years);
    }

    /**
     * Publications in a published issue are stamped with their issue.
     */
    protected function includePublication(Publication $publication): bool
    {
        return !$this->isInPublishedIssue($publication);
    }

    /**
     * Whether the publication is in one of the journal's published issues.
     */
    protected function isInPublishedIssue(Publication $publication): bool
    {
        $this->publishedIssueIds ??= Repo::issue()->getCollector()
            ->filterByContextIds([$this->contextId])
            ->filterByPublished(true)
            ->getIds()
            ->all();
        return in_array($publication->getData('issueId'), $this->publishedIssueIds);
    }

    /**
     * Stamp an issue, then its published publications, which take the issue's new identity.
     */
    protected function stampIssue(Issue $issue): void
    {
        $this->stampObject(
            $issue,
            'issue',
            "issue {$issue->getId()}",
            fn () => $issue->stampContextIdentity($this->context),
            function () use ($issue) {
                Repo::issue()->edit($issue, []);
                Repo::doi()->markStale(Repo::doi()->getDoisForIssue($issue->getId()));
            }
        );

        $publications = Repo::publication()->getCollector()
            ->filterByIssueIds([$issue->getId()])
            ->filterByStatus([Publication::STATUS_PUBLISHED])
            ->getMany();
        foreach ($publications as $publication) {
            $this->stampPublication($publication);
        }
    }
}

$tool = new StampJournalIdentityMetadata($argv ?? []);
$tool->execute();
