<?php
/**
 * @file classes/services/QueryBuilders/GalleyQueryBuilder.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class GalleyQueryBuilder
 * @ingroup query_builders
 *
 * @brief Class for building database queries for galleys
 */

namespace APP\Services\QueryBuilders;

use Illuminate\Database\Capsule\Manager as Capsule;
use PKP\Services\QueryBuilders\Interfaces\EntityQueryBuilderInterface;

class GalleyQueryBuilder extends \PKP\Services\QueryBuilders\BaseQueryBuilder implements EntityQueryBuilderInterface {
	/** @var array List of columns (see getQuery) */
	public $columns;

	/** @var array get authors for one or more publications */
	protected $publicationIds = [];

	/**
	 * Set publicationIds filter
	 *
	 * @param array|int $publicationIds
	 * @return \APP\Services\QueryBuilders\GalleyQueryBuilder
	 */
	public function filterByPublicationIds($publicationIds) {
		$this->publicationIds = is_array($publicationIds) ? $publicationIds : [$publicationIds];
		return $this;
	}

	/**
	 * @copydoc PKP\Services\QueryBuilders\Interfaces\EntityQueryBuilderInterface::getCount()
	 */
	public function getCount() {
		return $this
			->getQuery()
			->select('g.galley_id')
			->get()
			->count();
	}

	/**
	 * @copydoc PKP\Services\QueryBuilders\Interfaces\EntityQueryBuilderInterface::getCount()
	 */
	public function getIds() {
		return $this
			->getQuery()
			->select('g.galley_id')
			->pluck('g.galley_id')
			->toArray();
	}

	/**
	 * @copydoc PKP\Services\QueryBuilders\Interfaces\EntityQueryBuilderInterface::getCount()
	 */
	public function getQuery() {
		$this->columns = ['*'];
		$q = Capsule::table('publication_galleys as g');

		if (!empty($this->publicationIds)) {
			$q->whereIn('g.publication_id', $this->publicationIds);
		}

		// Add app-specific query statements
		\HookRegistry::call('Galley::getMany::queryObject', array(&$q, $this));

		$q->select($this->columns);

		return $q;
	}
}
