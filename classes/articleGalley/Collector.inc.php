<?php

/**
 * @file classes/articleGalley/Collector.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class articleGalley
 *
 * @brief A helper class to configure a Query Builder to get a collection of article galleys
 */

namespace PKP\articleGalley;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PKP\core\interfaces\CollectorInterface;
use PKP\plugins\HookRegistry;

class Collector implements CollectorInterface
{
    /** @var DAO */
    public $dao;

    /** @var array|null */
    public $publicationIds = null;

    /**  @var array|null */
    public $contextIds = null;

    public function __construct(DAO $dao)
    {
        $this->dao = $dao;
    }

    /**
     *  Filter galleys by publicationIds
     *
     * @param $publicationIds
     */
    /**
     * Filter article galleys by one or more publication ids
     */
    public function filterByPublicationIds(array $publicationIds): self
    {
        $this->publicationIds = $publicationIds;
        return $this;
    }

    public function filterByContextIds(array $contextIds)
    {
        $this->contextIds = $contextIds;
        return $this;
    }


    public function changePubId($pubObjectId, $pubIdType, $pubId)
    {
        DB::table('publication_galley_settings')
            ->where('galley_id', (int) $pubObjectId)
            ->update(['setting_value' => (string) $pubId]);
        return $this;
    }


    /**
     * @copydoc CollectorInterface::getQueryBuilder()
     */
    public function getQueryBuilder(): Builder
    {
        $qb = DB::table($this->dao->table . ' as g');
        if (!is_null($this->publicationIds)) {
            $qb->whereIn('g.publication_id', $this->publicationIds);
        }
        if (!is_null($this->contextIds)) {
            $qb->join('publications as p', 'p.publication_id', '=', 'g.publication_id')
                ->leftJoin('submissions as s', 's.submission_id', '=', 'p.submission_id')
                ->whereIn('s.context_id', $this->contextIds);
        }
        $qb->orderBy('g.seq', 'asc');        // Add app-specific query statements
        HookRegistry::call('Galley::Collector', [&$qb, $this]);
        $qb->select(['*']);
        return $qb;
    }
}
