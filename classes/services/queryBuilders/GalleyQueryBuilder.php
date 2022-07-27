<?php
/**
 * @file classes/services/QueryBuilders/GalleyQueryBuilder.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class GalleyQueryBuilder
 * @ingroup query_builders
 *
 * @brief Class for building database queries for galleys
 */

namespace APP\services\queryBuilders;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

use PKP\plugins\HookRegistry;
use PKP\services\queryBuilders\interfaces\EntityQueryBuilderInterface;

class GalleyQueryBuilder implements EntityQueryBuilderInterface
{
    /** @var array List of columns (see getQuery) */
    public $columns;

    /** @var array get authors for one or more publications */
    protected $publicationIds = [];

    public ?array $contextIds = null;

    /**
     * Set publicationIds filter
     *
     * @param array|int $publicationIds
     *
     * @return \APP\services\queryBuilders\GalleyQueryBuilder
     */
    public function filterByPublicationIds($publicationIds)
    {
        $this->publicationIds = is_array($publicationIds) ? $publicationIds : [$publicationIds];
        return $this;
    }

    public function filterByContexts(array $contextIds): self
    {
        $this->contextIds = $contextIds;
        return $this;
    }

    /**
     * @copydoc PKP\services\queryBuilders\interfaces\EntityQueryBuilderInterface::getCount()
     */
    public function getCount()
    {
        return $this
            ->getQuery()
            ->select('g.galley_id')
            ->get()
            ->count();
    }

    /**
     * @copydoc PKP\services\queryBuilders\interfaces\EntityQueryBuilderInterface::getCount()
     */
    public function getIds()
    {
        return $this
            ->getQuery()
            ->select('g.galley_id')
            ->pluck('g.galley_id')
            ->toArray();
    }

    /**
     * @copydoc PKP\services\queryBuilders\interfaces\EntityQueryBuilderInterface::getCount()
     */
    public function getQuery()
    {
        $this->columns = ['*'];
        $q = DB::table('publication_galleys as g');

        if (!empty($this->publicationIds)) {
            $q->whereIn('g.publication_id', $this->publicationIds);
        }

        // Contexts
        $q->when($this->contextIds !== null, function (Builder $q) {
            $q->whereIn('g.galley_id', function (Builder $q) {
                $q->select('g.galley_id')
                    ->from('publication_galleys as g')
                    ->leftJoin('publications as p', 'p.publication_id', '=', 'g.publication_id')
                    ->leftJoin('submissions as s', 's.submission_id', '=', 'p.submission_id')
                    ->whereIn('s.context_id', $this->contextIds);
            });
        });

        $q->orderBy('g.seq', 'asc');

        // Add app-specific query statements
        HookRegistry::call('Galley::getMany::queryObject', [&$q, $this]);

        $q->select($this->columns);

        return $q;
    }
}
