<?php

/**
 * @file classes/section/DAO.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2003-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DAO
 *
 * @ingroup section
 *
 * @see Section
 *
 * @brief Operations for retrieving and modifying Section objects.
 */

namespace APP\section;

use APP\facades\Repo;
use APP\submission\Submission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use PKP\services\PKPSchemaService;

class DAO extends \PKP\section\DAO
{
    /** @copydoc EntityDAO::$schema */
    public $schema = PKPSchemaService::SCHEMA_SECTION;

    /** @copydoc EntityDAO::$table */
    public $table = 'sections';

    /** @copydoc EntityDAO::$settingsTable */
    public $settingsTable = 'section_settings';

    /** @copydoc EntityDAO::$primaryKeyColumn */
    public $primaryKeyColumn = 'section_id';

    /** @copydoc EntityDAO::$primaryTableColumns */
    public $primaryTableColumns = [
        'id' => 'section_id',
        'contextId' => 'journal_id',
        'reviewFormId' => 'review_form_id',
        'sequence' => 'seq',
        'editorRestricted' => 'editor_restricted',
        'metaIndexed' => 'meta_indexed',
        'metaReviewed' => 'meta_reviewed',
        'abstractsNotRequired' => 'abstracts_not_required',
        'hideTitle' => 'hide_title',
        'hideAuthor' => 'hide_author',
        'isInactive' => 'is_inactive',
        'wordCount' => 'abstract_word_count'
    ];

    /**
     * Get the parent object ID column name
     */
    public function getParentColumn(): string
    {
        return 'journal_id';
    }

    /**
     * Retrieve all sections in which articles are currently published in
     * the given issue.
     */
    public function getByIssueId(int $issueId): LazyCollection
    {
        $issue = Repo::issue()->get($issueId);
        $allowedStatuses = [Submission::STATUS_PUBLISHED];
        if (!$issue->getPublished()) {
            $allowedStatuses[] = Submission::STATUS_SCHEDULED;
        }

        $submissionsCollector = Repo::submission()->getCollector()
            ->filterByContextIds([$issue->getJournalId()])
            ->filterByIssueIds([$issueId])
            ->filterByStatus($allowedStatuses)
            ->orderBy(\APP\submission\Collector::ORDERBY_SEQUENCE, \APP\submission\Collector::ORDER_DIR_ASC);

        // Extend the submissions query to fetch the list of section IDs instead
        $sectionIdsQuery = $submissionsCollector->getQueryBuilder()
            ->join('publications AS p', 'p.publication_id', '=', 's.current_publication_id')
            ->select('p.section_id');

        $rows = DB::table('sections', 's')
            ->select('s.*', DB::raw('COALESCE(o.seq, s.seq) AS section_seq'))
            ->leftJoin('custom_section_orders AS o', function ($join) use ($issueId) {
                $join->on('s.section_id', '=', 'o.section_id')
                    ->on('o.issue_id', '=', DB::raw($issueId));
            })
            ->whereIn('s.section_id', $sectionIdsQuery)
            ->orderBy('section_seq')
            ->get();

        return LazyCollection::make(function () use ($rows, $issueId) {
            // In case when the only article of a section the custom order exists for
            // is (re)moved from that section, the section will stay in the DB table custom_section_orders.
            // Thus, clean that now, so that other places in the code that use this function already
            // get the right/clean DB table custom_section_orders.
            // Also for the case when an article is assigned to a section the custom order does not exists for yet,
            // this will provide the right DB table custom_section_orders.
            $customOrderingExists = Repo::section()->customSectionOrderingExists($issueId);
            if ($customOrderingExists) {
                $this->deleteCustomSectionOrdering($issueId);
            }
            foreach ($rows as $i => $row) {
                if ($customOrderingExists) {
                    Repo::section()->upsertCustomSectionOrder($issueId, $row->section_id, $i);
                }
                yield $row->section_id => $this->fromRow($row);
            }
        });
    }

    /**
     * Check if an issue has custom section ordering.
     */
    public function customSectionOrderingExists(int $issueId): bool
    {
        return DB::table('custom_section_orders')
            ->where('issue_id', $issueId)
            ->exists();
    }

    /**
     * Delete the custom ordering of an issue's sections.
     */
    public function deleteCustomSectionOrdering(int $issueId): void
    {
        DB::table('custom_section_orders')
            ->where('issue_id', $issueId)
            ->delete();
    }

    /**
     * Get the custom order for sections in an issue.
     */
    public function getCustomSectionOrder(int $issueId, int $sectionId): ?int
    {
        return DB::table('custom_section_orders')
            ->where('issue_id', $issueId)
            ->where('section_id', $sectionId)
            ->value('seq');
    }

    /**
     * Delete a custom order for sections in an issue
     */
    public function deleteCustomSectionOrder(int $issueId, int $sectionId): void
    {
        $seq = $this->getCustomSectionOrder($issueId, $sectionId);

        DB::table('custom_section_orders')
            ->where('issue_id', $issueId)
            ->where('section_id', $sectionId)
            ->delete();

        // Reduce the section order of every successive section by one
        DB::table('custom_section_orders')
            ->where('issue_id', $issueId)
            ->where('seq', '>', $seq)
            ->update(['seq' => DB::raw('seq - 1')]);
    }

    /**
     * Insert or update a custom order for sections in an issue
     */
    public function upsertCustomSectionOrder(int $issueId, int $sectionId, int $seq): void
    {
        DB::table('custom_section_orders')->upsert(
            [['issue_id' => $issueId, 'section_id' => $sectionId, 'seq' => $seq]],
            ['issue_id', 'section_id'],
            ['seq']
        );
    }
}
