<?php

/**
 * @file classes/publication/Collector.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Collector
 *
 * @brief A helper class to configure a Query Builder to get a collection of announcements
 */

namespace APP\publication;

use Illuminate\Database\Query\Builder;
use PKP\publication\Collector as PKPCollector;

class Collector extends PKPCollector
{
    protected ?array $issueIds = null;

    public function filterByIssueIds(?array $issueIds): self
    {
        $this->issueIds = $issueIds;
        return $this;
    }

    public function getQueryBuilder(): Builder
    {
        $qb = parent::getQueryBuilder();

        // add OJS-specific filter
        $qb->when($this->issueIds !== null, function (Builder $qb) {
            $qb->whereIn('p.issue_id', $this->issueIds);
        });

        return $qb;
    }
}
