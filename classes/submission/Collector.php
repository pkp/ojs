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

use APP\core\Application;
use APP\facades\Repo;
use Illuminate\Database\Query\Builder;

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
                $query->select('p.submission_id')
                    ->from('publications as p')
                    ->whereIn('p.issue_id', $this->issueIds);
            });
        }

        // By section IDs
        if (is_array($this->sectionIds)) {
            $q->whereIn('s.submission_id', function ($query) {
                $query->select('p.submission_id')
                    ->from('publications as p')
                    ->whereIn('p.section_id', $this->sectionIds);
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

    /** @copydoc PKP/classes/submission/Collector::addFilterByAssociatedDoiIdsToQuery() */
    protected function addFilterByAssociatedDoiIdsToQuery(Builder $q)
    {
        $q->whereIn('s.submission_id', function (Builder $query) {
            $context = Application::get()->getRequest()->getContext();

            // Does two things:
            // 1 - Defaults to empty result when no DOIs are enabled.
            // 2 - Ensures that the union clause can be safely used if the query with the union clause is the only one that is executed.
            $query->selectRaw('NULL AS submission_id')->whereRaw('1 = 0');
            $query->when($context->isDoiTypeEnabled(Repo::doi()::TYPE_REPRESENTATION), function (Builder $q) {
                $q->select('p.submission_id')
                    ->from('publication_galleys AS g')
                    ->join('dois AS d', 'g.doi_id', '=', 'd.doi_id')
                    ->join('publications AS p', 'g.publication_id', '=', 'p.publication_id')
                    ->whereLike('d.doi', "{$this->searchPhrase}%");
            })
                ->when($context->isDoiTypeEnabled(Repo::doi()::TYPE_PUBLICATION), function (Builder $q) {
                    $q->union(function (Builder $q) {
                        $q->select('p.submission_id')
                            ->from('publications AS p')
                            ->join('dois AS d', 'p.doi_id', '=', 'd.doi_id')
                            ->whereLike('d.doi', "{$this->searchPhrase}%");
                    });
                });
        });
    }
}
