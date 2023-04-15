<?php
/**
 * @file classes/submission/Collector.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Collector
 *
 * @brief A helper class to configure a Query Builder to get a collection of submissions
 */

namespace APP\submission;

use APP\facades\Repo;
use Illuminate\Database\Query\Builder;
use PKP\doi\Doi;

class Collector extends \PKP\submission\Collector
{
    public ?array $issueIds = null;

    public ?array $sectionIds = null;

    public function __construct(DAO $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Limit results to submissions assigned to these issues
     */
    public function filterByIssueIds(array $issueIds): self
    {
        $this->issueIds = $issueIds;
        return $this;
    }

    /**
     * Limit results to submissions assigned to these sections
     */
    public function filterBySectionIds(array $sectionIds): self
    {
        $this->sectionIds = $sectionIds;
        return $this;
    }

    /**
     * @copydoc CollectorInterface::getQueryBuilder()
     */
    public function getQueryBuilder(): Builder
    {
        $q = parent::getQueryBuilder();

        // By issue IDs
        if (is_array($this->issueIds)) {
            $q->whereIn('s.submission_id', function ($query) {
                $query->select('issue_p.submission_id')
                    ->from('publications AS issue_p')
                    ->join('publication_settings as issue_ps', 'issue_p.publication_id', '=', 'issue_ps.publication_id')
                    ->where('issue_ps.setting_name', '=', 'issueId')
                    ->whereIn('issue_ps.setting_value', array_map('strval', $this->issueIds));
            });
        }

        // By section IDs
        if (is_array($this->sectionIds)) {
            $q->whereIn('s.submission_id', function ($query) {
                $query->select('section_p.submission_id')
                    ->from('publications AS section_p')
                    ->whereIn('section_p.section_id', $this->sectionIds);
            });
        }

        return $q;
    }

    /**
     * Add APP-specific filtering methods for submission sub objects DOI statuses
     *
     *
     */
    protected function addDoiStatusFilterToQuery(Builder $q)
    {
        $q->whereIn('s.current_publication_id', function (Builder $q) {
            $q->select('current_p.publication_id')
                ->from('publications as current_p')
                ->leftJoin('publication_galleys as current_g', 'current_g.publication_id', '=', 'current_p.publication_id')
                ->leftJoin('dois as pd', 'pd.doi_id', '=', 'current_p.doi_id')
                ->leftJoin('dois as gd', 'gd.doi_id', '=', 'current_g.doi_id')
                ->whereIn('pd.status', $this->doiStatuses)
                ->orWhereIn('gd.status', $this->doiStatuses);
        });
    }

    /**
     * Add APP-specific filtering methods for checking if submission sub objects have DOIs assigned
     */
    protected function addHasDoisFilterToQuery(Builder $q)
    {
        $q->whereIn('s.current_publication_id', function (Builder $q) {
            $q->select('current_p.publication_id')
                ->from('publications', 'current_p')
                ->leftJoin('submissions as current_s', 'current_s.current_publication_id', '=', 'current_p.publication_id')
                ->leftJoin('publication_galleys as current_g', 'current_g.publication_id', '=', 'current_p.publication_id')
                ->where(function (Builder $q) {
                    $q->when($this->hasDois === true, function (Builder $q) {
                        $q->when(in_array(Repo::doi()::TYPE_PUBLICATION, $this->enabledDoiTypes), function (Builder $q) {
                            $q->whereNotNull('current_p.doi_id');
                        });
                        $q->when(in_array(Repo::doi()::TYPE_REPRESENTATION, $this->enabledDoiTypes), function (Builder $q) {
                            $q->orWhereNotNull('current_g.doi_id');
                        });
                    });
                    $q->when($this->hasDois === false, function (Builder $q) {
                        $q->when(in_array(Repo::doi()::TYPE_PUBLICATION, $this->enabledDoiTypes), function (Builder $q) {
                            $q->whereNull('current_p.doi_id');
                        });
                        $q->when(in_array(Repo::doi()::TYPE_REPRESENTATION, $this->enabledDoiTypes), function (Builder $q) {
                            $q->orWhere(function (Builder $q) {
                                $q->whereNull('current_g.doi_id');
                                $q->whereNotNull('current_g.galley_id');
                            });
                        });
                    });
                });
        });
    }
}
