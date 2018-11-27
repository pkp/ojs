<?php

/**
 * @file classes/services/QueryBuilders/.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class
 * @ingroup query_builders
 *
 * @brief Issue list Query builder
 */

namespace APP\Services\QueryBuilders;

use PKP\Services\QueryBuilders\BaseQueryBuilder;
use Illuminate\Database\Capsule\Manager as Capsule;

class IssueQueryBuilder extends BaseQueryBuilder {

	/** @var int|string|null Context ID or '*' to get from all contexts */
	protected $contextId = null;

	/** @var array list of columns for query */
	protected $columns = array();

	/** @var string order by column */
	protected $orderColumn = 'i.date_published';

	/** @var string order by direction */
	protected $orderDirection = 'DESC';

	/** @var boolean return published issues */
	protected $isPublished = null;

	/** @var array return issues in volume(s) */
	protected $volumes = null;

	/** @var array return issues with number(s) */
	protected $numbers = null;

	/** @var array return issues with year(s) */
	protected $years = null;

	/** @var bool whether to return only a count of results */
	protected $countOnly = null;

	/**
	 * Set context issues filter
	 *
	 * @param int|string $contextId
	 *
	 * @return \APP\Services\QueryBuilders\IssueQueryBuilder
	 */
	public function filterByContext($contextId) {
		$this->contextId = $contextId;
		return $this;
	}

	/**
	 * Set result order column and direction
	 *
	 * @param string $column
	 * @param string $direction
	 *
	 * @return \APP\Services\QueryBuilders\IssueQueryBuilder
	 */
	public function orderBy($column, $direction = 'DESC') {
		if ($column === 'lastModified') {
			$this->orderColumn = 'i.last_modified';
		} elseif ($column === 'seq') {
			$this->orderColumn = 'o.seq';
		} else {
			$this->orderColumn = 'i.date_published';
		}
		$this->orderDirection = $direction;
		return $this;
	}

	/**
	 * Set published filter
	 *
	 * @param boolean $isPublished
	 *
	 * @return \APP\Services\QueryBuilders\IssueQueryBuilder
	 */
	public function filterByPublished($isPublished) {
		$this->isPublished = $isPublished;
		return $this;
	}

	/**
	 * Set volumes filter
	 *
	 * @param array $volumes
	 *
	 * @return \APP\Services\QueryBuilders\IssueQueryBuilder
	 */
	public function filterByVolumes($volumes) {
		if (!is_null($volumes) && !is_array($volumes)) {
			$volumes = array($volumes);
		}
		$this->volumes = $volumes;
		return $this;
	}

	/**
	 * Set numbers filter
	 *
	 * @param array $numbers
	 *
	 * @return \APP\Services\QueryBuilders\IssueQueryBuilder
	 */
	public function filterByNumbers($numbers) {
		if (!is_null($numbers) && !is_array($numbers)) {
			$numbers = array($numbers);
		}
		$this->numbers = $numbers;
		return $this;
	}

	/**
	 * Set years filter
	 *
	 * @param array $years
	 *
	 * @return \APP\Services\QueryBuilders\IssueQueryBuilder
	 */
	public function filterByYears($years) {
		if (!is_null($years) && !is_array($years)) {
			$years = array($years);
		}
		$this->years = $years;
		return $this;
	}

	/**
	 * Whether to return only a count of results
	 *
	 * @param bool $enable
	 *
	 * @return \APP\Services\QueryBuilders\IssueQueryBuilder
	 */
	public function countOnly($enable = true) {
		$this->countOnly = $enable;
		return $this;
	}

	/**
	 * Execute query builder
	 *
	 * @return object Query object
	 */
	public function get() {
		$this->columns[] = 'i.*';
		$q = Capsule::table('issues as i')
					->leftJoin('issue_settings as is', 'i.issue_id', '=', 'is.issue_id')
					->leftJoin('custom_issue_orders as o', 'o.issue_id', '=', 'i.issue_id')
					->orderBy($this->orderColumn, $this->orderDirection)
					->groupBy('i.issue_id');

		// context
		// Never permit a query without a context_id clause unless the '*' wildcard
		// has been set explicitely.
		if (is_null($this->contextId)) {
			$q->where('i.journal_id', '=', CONTEXT_ID_NONE);
		} elseif ($this->contextId !== '*') {
			$q->where('i.journal_id', '=' , $this->contextId);
		}

		// published
		if (!is_null($this->isPublished)) {
			$q->where('i.published', $this->isPublished ? 1 : 0);
		}

		// volumes
		if (!is_null($this->volumes)) {
			$q->whereIn('i.volume', $this->volumes);
		}

		// numbers
		if (!is_null($this->numbers)) {
			$q->whereIn('i.number', $this->numbers);
		}

		// years
		if (!is_null($this->years)) {
			$q->whereIn('i.year', $this->years);
		}

		// Allow third-party query statements
		\HookRegistry::call('Issue::getMany::queryObject', array(&$q, $this));

		if (!empty($this->countOnly)) {
			$q->select(Capsule::raw('count(*) as issue_count'));
		} else {
			$q->select($this->columns);
		}

		return $q;
	}
}
