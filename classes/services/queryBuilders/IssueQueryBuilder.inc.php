<?php

/**
 * @file classes/services/QueryBuilders/IssueQueryBuilder.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class
 * @ingroup query_builders
 *
 * @brief Issue list Query builder
 */

namespace APP\Services\QueryBuilders;

use Illuminate\Database\Capsule\Manager as Capsule;
use PKP\Services\QueryBuilders\Interfaces\EntityQueryBuilderInterface;

class IssueQueryBuilder implements EntityQueryBuilderInterface {

	/** @var int|string|null Context ID or CONTEXT_ID_ALL to get from all contexts */
	protected $contextId = null;

	/** @var array list of columns for query */
	protected $columns = array();

	/** @var string order by column */
	protected $orderColumn = 'i.date_published';

	/** @var string order by direction */
	protected $orderDirection = 'DESC';

	/** @var boolean return published issues */
	protected $isPublished = null;

	/** @var array list of issue ids to retrieve */
	protected $issueIds = [];

	/** @var array return issues in volume(s) */
	protected $volumes = null;

	/** @var array return issues with number(s) */
	protected $numbers = null;

	/** @var array return issues with year(s) */
	protected $years = null;

	/** @var bool whether to return only a count of results */
	protected $countOnly = null;

	/** @var int|null whether to limit the number of results returned */
	protected $limit = null;

	/** @var int whether to offset the number of results returned. Use to return a second page of results. */
	protected $offset = 0;

	/** @var string return issues which match words from this search phrase */
	protected $searchPhrase = '';

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
	 * Set issue id filter
	 *
	 * @param array $issueIds
	 *
	 * @return \APP\Services\QueryBuilders\IssueQueryBuilder
	 */
	public function filterByIds($issueIds) {
		$this->issueIds = $issueIds;
		return $this;
	}

	/**
	 * Set query search phrase
	 *
	 * @param string $phrase
	 *
	 * @return \APP\Services\QueryBuilders\IssueQueryBuilder
	 */
	public function searchPhrase($phrase) {
		$this->searchPhrase = $phrase;
		return $this;
	}

	/**
	 * Set query limit
	 *
	 * @param int $count
	 *
	 * @return \APP\Services\QueryBuilders\IssueQueryBuilder
	 */
	public function limitTo($count) {
		$this->limit = $count;
		return $this;
	}

	/**
	 * Set how many results to skip
	 *
	 * @param int $offset
	 *
	 * @return \APP\Services\QueryBuilders\IssueQueryBuilder
	 */
	public function offsetBy($offset) {
		$this->offset = $offset;
		return $this;
	}

	/**
	 * @copydoc PKP\Services\QueryBuilders\Interfaces\EntityQueryBuilderInterface::getCount()
	 */
	public function getCount() {
		return $this
			->getQuery()
			->select('i.issue_id')
			->get()
			->count();
	}

	/**
	 * @copydoc PKP\Services\QueryBuilders\Interfaces\EntityQueryBuilderInterface::getIds()
	 */
	public function getIds() {
		return $this
			->getQuery()
			->select('i.issue_id')
			->pluck('i.issue_id')
			->toArray();
	}

	/**
	 * @copydoc PKP\Services\QueryBuilders\Interfaces\EntityQueryBuilderInterface::getQuery()
	 */
	public function getQuery() {
		$this->columns[] = 'i.*';
		$q = Capsule::table('issues as i')
					->leftJoin('issue_settings as iss', 'i.issue_id', '=', 'iss.issue_id')
					->leftJoin('custom_issue_orders as o', 'o.issue_id', '=', 'i.issue_id')
					->orderBy($this->orderColumn, $this->orderDirection)
					->groupBy('i.issue_id', $this->orderColumn);

		// context
		// Never permit a query without a context_id clause unless the CONTEXT_ID_ALL wildcard
		// has been set explicitely.
		if (is_null($this->contextId)) {
			$q->where('i.journal_id', '=', CONTEXT_ID_NONE);
		} elseif ($this->contextId !== CONTEXT_ID_ALL) {
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

		// issue ids
		if (!empty($this->issueIds)) {
			$q->whereIn('i.issue_id', $this->issueIds);
		}

		// search phrase
		if (!empty($this->searchPhrase)) {
			$searchPhrase = $this->searchPhrase;

			// Add support for searching for the volume, number and year
			// using the localized issue identification formats. In
			// en_US this will match Vol. 1. No. 1 (2018) against:
			// i.volume = 1 AND i.number = 1 AND i.year = 2018
			$volume = '';
			$number = '';
			$year = '';
			$volumeRegex = '/' . preg_quote(__('issue.vol')) . '\s\S/';
			preg_match($volumeRegex, $searchPhrase, $matches);
			if (count($matches)) {
				$volume = trim(str_replace(__('issue.vol'), '', $matches[0]));
				$searchPhrase = str_replace($matches[0], '', $searchPhrase);
			}
			$numberRegex = '/' . preg_quote(__('issue.no')) . '\s\S/';
			preg_match($numberRegex, $searchPhrase, $matches);
			if (count($matches)) {
				$number = trim(str_replace(__('issue.no'), '', $matches[0]));
				$searchPhrase = str_replace($matches[0], '', $searchPhrase);
			}
			preg_match('/\(\d{4}\)\:?/', $searchPhrase, $matches);
			if (count($matches)) {
				$year = substr($matches[0], 1, 4);
				$searchPhrase = str_replace($matches[0], '', $searchPhrase);
			}
			if ($volume !== '' || $number !== '' || $year !== '') {
				$q->where(function($q) use ($volume, $number, $year) {
					if ($volume) {
						$q->where('i.volume', '=', $volume);
					}
					if ($number) {
						$q->where('i.number', '=', $number);
					}
					if ($year) {
						$q->where('i.year', '=', $year);
					}
				});
			}

			$words = array_unique(explode(' ', $searchPhrase));
			if (count($words)) {
				foreach ($words as $word) {
					$word = strtolower(addcslashes($word, '%_'));
					$q->where(function($q) use ($word)  {
						$q->where(function($q) use ($word) {
							$q->where('iss.setting_name', 'title');
							$q->where(Capsule::raw('lower(iss.setting_value)'), 'LIKE', "%$word%");
						})
						->orWhere(function($q) use ($word) {
							$q->where('iss.setting_name', 'description');
							$q->where(Capsule::raw('lower(iss.setting_value)'), 'LIKE', "%$word%");
						});

						// Match any four-digit number to the year
						if (ctype_digit($word) && strlen($word) === 4) {
							$q->orWhere('i.year', '=', $word);
						}
					});
				}
			}
		}

		// Allow third-party query statements
		\HookRegistry::call('Issue::getMany::queryObject', array(&$q, $this));

		$q->select($this->columns);

		return $q;
	}
}
