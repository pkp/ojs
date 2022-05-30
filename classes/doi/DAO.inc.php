<?php

/**
 * @file classes/doi/DAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DoiDAO
 * @ingroup doi
 *
 * @see Doi
 *
 * @brief Operations for retrieving and modifying Doi objects.
 */

namespace APP\doi;

use APP\facades\Repo;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PKP\context\Context;
use PKP\doi\Doi;
use PKP\submission\PKPSubmission;

class DAO extends \PKP\doi\DAO
{
    /**
     * Gets all depositable submission IDs along with all associated DOI IDs for use in DOI bulk deposit jobs.
     * This method is used to collect all valid submissions/IDs in a single query specifically for use with
     * queued jobs for depositing DOIs with a registration agency.
     *
     */
    public function getAllDepositableSubmissionIds(Context $context): Collection
    {
        $enabledDoiTypes = $context->getData(Context::SETTING_ENABLED_DOI_TYPES) ?? [];

        $q = DB::table($this->table, 'd')
            ->leftJoin('publications as p', 'd.doi_id', '=', 'p.doi_id')
            ->leftJoin('submissions as s', 'p.publication_id', '=', 's.current_publication_id')
            ->where('d.context_id', '=', $context->getId())
            ->where(function (Builder $q) use ($enabledDoiTypes) {
                // Publication DOIs
                $q->when(in_array(Repo::doi()::TYPE_PUBLICATION, $enabledDoiTypes), function (Builder $q) {
                    $q->whereIn('d.doi_id', function (Builder $q) {
                        $q->select('p.doi_id')
                            ->from('publications', 'p')
                            ->leftJoin('submissions as s', 'p.publication_id', '=', 's.current_publication_id')
                            ->whereColumn('p.publication_id', '=', 's.current_publication_id')
                            ->whereNotNull('p.doi_id')
                            ->where('p.status', '=', PKPSubmission::STATUS_PUBLISHED);
                    });
                })
                    // Galley DOIs
                    ->when(in_array(Repo::doi()::TYPE_REPRESENTATION, $enabledDoiTypes), function (Builder $q) {
                        $q->orWhereIn('d.doi_id', function (Builder $q) {
                            $q->select('g.doi_id')
                                ->from('publication_galleys', 'g')
                                ->leftJoin('publications as p', 'g.publication_id', '=', 'p.publication_id')
                                ->leftJoin('submissions as s', 'p.publication_id', '=', 's.current_publication_id')
                                ->whereColumn('p.publication_id', '=', 's.current_publication_id')
                                ->whereNotNull('g.doi_id')
                                ->where('p.status', '=', PKPSubmission::STATUS_PUBLISHED);
                        });
                    });
            });
        $q->whereIn('d.status', [Doi::STATUS_UNREGISTERED, Doi::STATUS_ERROR, Doi::STATUS_STALE]);
        return $q->get(['s.submission_id', 'd.doi_id']);
    }

    /**
     * Gets all depositable issues IDs along with all associated DOI IDs for use in DOI bulk deposit jobs.
     * This method is used to collect all valid issues/IDs in a single query specifically for use with
     * queued jobs for depositing DOIs with a registration agency.
     *
     */
    public function getAllDepositableIssueIds(Context $context): Collection
    {
        $q = DB::table($this->table, 'd')
            ->leftJoin('issues as i', 'i.doi_id', '=', 'd.doi_id')
            ->where('i.journal_id', '=', $context->getId())
            ->whereNotNull('i.doi_id')
            ->where('i.published', '=', 1)
            ->whereIn('d.status', [Doi::STATUS_UNREGISTERED, Doi::STATUS_ERROR, Doi::STATUS_STALE]);

        return $q->get(['i.issue_id', 'i.doi_id']);
    }
}
