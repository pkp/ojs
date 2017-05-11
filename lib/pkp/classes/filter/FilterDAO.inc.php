<?php

/**
 * @file classes/filter/FilterDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FilterDAO
 * @ingroup filter
 * @see PersistableFilter
 *
 * @brief Operations for retrieving and modifying Filter objects.
 *
 * The filter DAO acts as a filter registry. It allows filter providers to
 * register transformations and filter consumers to identify available
 * transformations that convert a given input type into a required output type.
 *
 * Transformations are defined as a combination of a filter class, a pair of
 * input/output type specifications supported by that filter implementation
 * and a set of configuration parameters that customize the filter.
 *
 * Transformations can be based on filter templates. A template is defined as
 * a filter instance without parameterization. A flag is used to distinguish
 * filter templates from fully parameterized filter instances.
 *
 * Different filters that perform semantically related transformations (e.g.
 * all citation parsers or all citation output filters) are clustered into
 * filter groups (@see FilterGroup).
 *
 * The following additional conditions apply:
 * 1) A single filter class may support several transformations, i.e. distinct
 *    combinations of input and output types or distinct parameterizations.
 *    Therefore the filter DAO must be able to handle several registry entries
 *    per filter class.
 * 2) The DAO must take care to only select such transformations that are
 *    supported by the current runtime environment.
 * 3) The DAO implementation must be scalable, fast and memory efficient.
 */

import('lib.pkp.classes.filter.Filter');

class FilterDAO extends DAO {
	/** @var array names of additional settings for the currently persisted/retrieved filter */
	var $additionalFieldNames;

	/** @var array names of localized settings for the currently persisted/retrieved filter */
	var $localeFieldNames;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Instantiates a new filter from configuration data and then
	 * installs it.
	 *
	 * @param $filterClassName string
	 * @param $filterGroupSymbolic string
	 * @param $settings array key-value pairs that can be directly written
	 *  via DataObject::setData().
	 * @param $asTemplate boolean
	 * @param $contextId integer the context the filter should be installed into
	 * @param $subFilters array sub-filters (only allowed when the filter is a CompositeFilter)
	 * @param $persist boolean whether to actually persist the filter
	 * @return PersistableFilter|boolean the new filter if installation successful, otherwise 'false'.
	 */
	function configureObject($filterClassName, $filterGroupSymbolic, $settings = array(), $asTemplate = false, $contextId = 0, $subFilters = array(), $persist = true) {
		// Retrieve the filter group from the database.
		$filterGroupDao = DAORegistry::getDAO('FilterGroupDAO'); /* @var $filterGroupDao FilterGroupDAO */
		$filterGroup = $filterGroupDao->getObjectBySymbolic($filterGroupSymbolic);
		if (!is_a($filterGroup, 'FilterGroup')) return false;

		// Instantiate the filter.
		$filter = instantiate($filterClassName, 'PersistableFilter', null, 'execute', $filterGroup); /* @var $filter PersistableFilter */
		if (!is_object($filter)) return false;

		// Is this a template?
		$filter->setIsTemplate((boolean)$asTemplate);

		// Add sub-filters (if any).
		if (!empty($subFilters)) {
			assert(is_a($filter, 'CompositeFilter'));
			assert(is_array($subFilters));
			foreach($subFilters as $subFilter) {
				$filter->addFilter($subFilter);
				unset($subFilter);
			}
		}

		// Parameterize the filter.
		assert(is_array($settings));
		foreach($settings as $key => $value) {
			$filter->setData($key, $value);
		}

		// Persist the filter.
		if ($persist) {
			$filterId = $this->insertObject($filter, $contextId);
			if (!is_integer($filterId) || $filterId == 0) return false;
		}

		return $filter;
	}

	/**
	 * Insert a new filter instance (transformation).
	 *
	 * @param $filter PersistableFilter The configured filter instance to be persisted
	 * @param $contextId integer
	 * @return integer the new filter id
	 */
	function insertObject($filter, $contextId = CONTEXT_ID_NONE) {
		$filterGroup = $filter->getFilterGroup();
		assert($filterGroup->getSymbolic() != FILTER_GROUP_TEMPORARY_ONLY);

		$this->update(
			sprintf('INSERT INTO filters
				(filter_group_id, context_id, display_name, class_name, is_template, parent_filter_id, seq)
				VALUES (?, ?, ?, ?, ?, ?, ?)'),
			array(
				(int) $filterGroup->getId(),
				(int) $contextId,
				$filter->getDisplayName(),
				$filter->getClassName(),
				$filter->getIsTemplate()?1:0,
				(int) $filter->getParentFilterId(),
				(int) $filter->getSequence()
			)
		);
		$filter->setId((int)$this->getInsertId());
		$this->updateDataObjectSettings(
			'filter_settings', $filter,
			array('filter_id' => $filter->getId())
		);

		// Recursively insert sub-filters.
		$this->_insertSubFilters($filter);

		return $filter->getId();
	}

	/**
	 * Retrieve a configured filter instance (transformation)
	 * @param $filter PersistableFilter
	 * @return PersistableFilter
	 */
	function getObject($filter) {
		return $this->getObjectById($filter->getId());
	}

	/**
	 * Retrieve a configured filter instance (transformation) by id.
	 * @param $filterId integer
	 * @param $allowSubfilter boolean
	 * @return PersistableFilter
	 */
	function getObjectById($filterId, $allowSubfilter = false) {
		$result = $this->retrieve(
			'SELECT * FROM filters
			 WHERE '.($allowSubfilter ? '' : 'parent_filter_id = 0 AND '). '
			 filter_id = ?',
			(int) $filterId
		);

		$filter = null;
		if ($result->RecordCount() != 0) {
			$filter = $this->_fromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		return $filter;
	}

	/**
	 * Retrieve a result set with all filter instances
	 * (transformations) that are based on the given class.
	 * @param $className string
	 * @param $contextId integer
	 * @param $getTemplates boolean set true if you want filter templates
	 *  rather than actual transformations
	 * @param $allowSubfilters boolean
	 * @return DAOResultFactory
	 */
	function getObjectsByClass($className, $contextId = CONTEXT_ID_NONE, $getTemplates = false, $allowSubfilters = false) {
		$result = $this->retrieve(
			'SELECT	* FROM filters
			 WHERE	context_id = ? AND
				class_name = ? AND
			' . ($allowSubfilters ? '' : ' parent_filter_id = 0 AND ') . '
			' . ($getTemplates ? ' is_template = 1' : ' is_template = 0'),
			array((int) $contextId, $className)
		);

		$daoResultFactory = new DAOResultFactory($result, $this, '_fromRow', array('filter_id'));
		return $daoResultFactory;
	}

	/**
	 * Retrieve a result set with all filter instances
	 * (transformations) within a given group that are
	 * based on the given class.
	 * @param $groupSymbolic string
	 * @param $className string
	 * @param $contextId integer
	 * @param $getTemplates boolean set true if you want filter templates
	 *  rather than actual transformations
	 * @param $allowSubfilters boolean
	 * @return DAOResultFactory
	 */
	function getObjectsByGroupAndClass($groupSymbolic, $className, $contextId = CONTEXT_ID_NONE, $getTemplates = false, $allowSubfilters = false) {
		$result = $this->retrieve(
			'SELECT f.* FROM filters f'.
			' INNER JOIN filter_groups fg ON f.filter_group_id = fg.filter_group_id'.
			' WHERE fg.symbolic = ? AND f.context_id = ? AND f.class_name = ?'.
			' '.($allowSubfilters ? '' : 'AND f.parent_filter_id = 0').
			' AND '.($getTemplates ? 'f.is_template = 1' : 'f.is_template = 0'),
			array($groupSymbolic, (int) $contextId, $className)
		);

		return new DAOResultFactory($result, $this, '_fromRow', array('filter_id'));
	}

	/**
	 * Retrieve filters based on the supported input/output type.
	 *
	 * @param $inputTypeDescription a type description that has to match the input type
	 * @param $outputTypeDescription a type description that has to match the output type
	 *  NB: input and output type description can contain wildcards.
	 * @param $data mixed the data to be matched by the filter. If no data is given then
	 *  all filters will be matched.
	 * @param $dataIsInput boolean true if the given data object is to be checked as
	 *  input type, false to check against the output type.
	 * @return array a list of matched filters.
	 */
	function getObjectsByTypeDescription($inputTypeDescription, $outputTypeDescription, $data = null, $dataIsInput = true) {
		static $filterCache = array();
		static $objectFilterCache = array();

		// We do not yet support array data types. Implement when required.
		assert(!is_array($data));

		// Build the adapter cache.
		$filterCacheKey = md5($inputTypeDescription.'=>'.$outputTypeDescription);
		if (!isset($filterCache[$filterCacheKey])) {
			// Get all adapter filters.
			$result = $this->retrieve(
				'SELECT f.*'.
				' FROM filters f'.
				'  INNER JOIN filter_groups fg ON f.filter_group_id = fg.filter_group_id'.
				' WHERE fg.input_type like ?'.
				'  AND fg.output_type like ?'.
				'  AND f.parent_filter_id = 0 AND f.is_template = 0',
				array($inputTypeDescription, $outputTypeDescription)
			);

			// Instantiate all filters.
			$filterFactory = new DAOResultFactory($result, $this, '_fromRow', array('filter_id'));
			$filterCache[$filterCacheKey] = $filterFactory->toAssociativeArray();
		}

		// Return all filter candidates if no data is given to check against.
		if (is_null($data)) return $filterCache[$filterCacheKey];

		// Build the object-specific adapter cache.
		$objectFilterCacheKey = md5($filterCacheKey.(is_object($data)?get_class($data):"'$data'").($dataIsInput?'in':'out'));
		if (!isset($objectFilterCache[$objectFilterCacheKey])) {
			$objectFilterCache[$objectFilterCacheKey] = array();
			foreach($filterCache[$filterCacheKey] as $filterCandidateId => $filterCandidate) { /* @var $filterCandidate PersistableFilter */
				// Check whether the given object can be transformed
				// with this filter.
				if ($dataIsInput) {
					$filterDataType = $filterCandidate->getInputType();
				} else {
					$filterDataType = $filterCandidate->getOutputType();
				}
				if ($filterDataType->checkType($data)) $objectFilterCache[$objectFilterCacheKey][$filterCandidateId] = $filterCandidate;
				unset($filterCandidate);
			}
		}

		return $objectFilterCache[$objectFilterCacheKey];
	}

	/**
	 * Retrieve filter instances configured for a given context
	 * that belong to a given filter group.
	 *
	 * Only filters supported by the current run-time environment
	 * will be returned when $checkRuntimeEnvironment is set to 'true'.
	 *
	 * @param $groupSymbolic string
	 * @param $contextId integer returns filters from context 0 and
	 *  the given filters of all contexts if set to null
	 * @param $getTemplates boolean set true if you want filter templates
	 *  rather than actual transformations
	 * @param $checkRuntimeEnvironment boolean whether to remove filters
	 *  from the result set that do not match the current run-time environment.
	 * @return array filter instances (transformations) in the given group
	 */
	function getObjectsByGroup($groupSymbolic, $contextId = CONTEXT_ID_NONE, $getTemplates = false, $checkRuntimeEnvironment = true) {
		// 1) Get all available transformations in the group.
		$result = $this->retrieve(
			'SELECT f.* FROM filters f'.
			' INNER JOIN filter_groups fg ON f.filter_group_id = fg.filter_group_id'.
			' WHERE fg.symbolic = ? AND '.($getTemplates ? 'f.is_template = 1' : 'f.is_template = 0').
			'  '.(is_null($contextId) ? '' : 'AND f.context_id in (0, '.(int)$contextId.')').
			'  AND f.parent_filter_id = 0',
			$groupSymbolic
		);


		// 2) Instantiate and return all transformations in the
		//    result set that comply with the current runtime
		//    environment.
		$matchingFilters = array();
		foreach($result->GetAssoc() as $filterRow) {
			$filterInstance = $this->_fromRow($filterRow);
			if (!$checkRuntimeEnvironment || $filterInstance->isCompatibleWithRuntimeEnvironment()) {
				$matchingFilters[$filterInstance->getId()] = $filterInstance;
			}
			unset($filterInstance);
		}

		return $matchingFilters;
	}

	/**
	 * Update an existing filter instance (transformation).
	 * @param $filter PersistableFilter
	 */
	function updateObject($filter) {
		$filterGroup = $filter->getFilterGroup();
		assert($filterGroup->getSymbolic() != FILTER_GROUP_TEMPORARY_ONLY);

		$this->update(
			'UPDATE	filters
			SET	filter_group_id = ?,
				display_name = ?,
				class_name = ?,
				is_template = ?,
				parent_filter_id = ?,
				seq = ?
			WHERE filter_id = ?',
			array(
				(int) $filterGroup->getId(),
				$filter->getDisplayName(),
				$filter->getClassName(),
				$filter->getIsTemplate()?1:0,
				(int) $filter->getParentFilterId(),
				(int) $filter->getSequence(),
				(int) $filter->getId()
			)
		);
		$this->updateDataObjectSettings(
			'filter_settings', $filter,
			array('filter_id' => $filter->getId())
		);

		// Do we update a composite filter?
		if (is_a($filter, 'CompositeFilter')) {
			// Delete all sub-filters
			$this->_deleteSubFiltersByParentFilterId($filter->getId());

			// Re-insert sub-filters
			$this->_insertSubFilters($filter);
		}
	}

	/**
	 * Delete a filter instance (transformation).
	 * @param $filter PersistableFilter
	 * @return boolean
	 */
	function deleteObject($filter) {
		return $this->deleteObjectById($filter->getId());
	}

	/**
	 * Delete a filter instance (transformation) by id.
	 * @param $filterId int
	 * @return boolean
	 */
	function deleteObjectById($filterId) {
		$filterId = (int)$filterId;
		$this->update('DELETE FROM filters WHERE filter_id = ?', (int) $filterId);
		$this->update('DELETE FROM filter_settings WHERE filter_id = ?', (int) $filterId);
		$this->_deleteSubFiltersByParentFilterId($filterId);
		return true;
	}


	//
	// Overridden methods from DAO
	//
	/**
	 * @see DAO::updateDataObjectSettings()
	 */
	function updateDataObjectSettings($tableName, $dataObject, $idArray) {
		// Make sure that the update function finds the filter settings
		$this->additionalFieldNames = $dataObject->getSettingNames();
		$this->localeFieldNames = $dataObject->getLocalizedSettingNames();

		// Add runtime settings
		foreach($dataObject->supportedRuntimeEnvironmentSettings() as $runtimeSetting => $defaultValue) {
			if ($dataObject->hasData($runtimeSetting)) $this->additionalFieldNames[] = $runtimeSetting;
		}

		// Update the filter settings
		parent::updateDataObjectSettings($tableName, $dataObject, $idArray);

		// Reset the internal fields
		unset($this->additionalFieldNames, $this->localeFieldNames);
	}


	//
	// Implement template methods from DAO
	//
	/**
	 * @see DAO::getAdditionalFieldNames()
	 */
	function getAdditionalFieldNames() {
		assert(is_array($this->additionalFieldNames));
		return $this->additionalFieldNames;
	}

	/**
	 * @see DAO::getLocaleFieldNames()
	 */
	function getLocaleFieldNames() {
		assert(is_array($this->localeFieldNames));
		return $this->localeFieldNames;
	}


	//
	// Protected helper methods
	//
	/**
	 * Get the ID of the last inserted filter instance (transformation).
	 * @return int
	 */
	function getInsertId() {
		return parent::_getInsertId('filters', 'filter_id');
	}


	//
	// Private helper methods
	//
	/**
	 * Construct a new configured filter instance (transformation).
	 * @param $filterClassName string a fully qualified class name
	 * @param $filterGroupId integer
	 * @return PersistableFilter
	 */
	function _newDataObject($filterClassName, $filterGroupId) {
		// Instantiate the filter group.
		$filterGroupDao = DAORegistry::getDAO('FilterGroupDAO'); /* @var $filterGroupDao FilterGroupDAO */
		$filterGroup = $filterGroupDao->getObjectById($filterGroupId);
		assert(is_a($filterGroup, 'FilterGroup'));

		// Instantiate the filter
		$filter = instantiate($filterClassName, 'PersistableFilter', null, 'execute', $filterGroup); /* @var $filter PersistableFilter */
		if (!is_object($filter)) fatalError('Error while instantiating class "'.$filterClassName.'" as filter!');

		return $filter;
	}

	/**
	 * Internal function to return a filter instance (transformation)
	 * object from a row.
	 *
	 * @param $row array
	 * @return PersistableFilter
	 */
	function _fromRow($row) {
		static $lockedFilters = array();
		$filterId = $row['filter_id'];

		// Check the filter lock (to detect loops).
		// NB: This is very important otherwise the request
		// could eat up lots of memory if the PHP memory max was
		// set too high.
		if (isset($lockedFilters[$filterId])) fatalError('Detected a loop in the definition of the filter with id '.$filterId.'!');

		// Lock the filter id.
		$lockedFilters[$filterId] = true;

		// Instantiate the filter.
		$filter = $this->_newDataObject($row['class_name'], (integer)$row['filter_group_id']);

		// Configure the filter instance
		$filter->setId((int)$row['filter_id']);
		$filter->setDisplayName($row['display_name']);
		$filter->setIsTemplate((boolean)$row['is_template']);
		$filter->setParentFilterId((int)$row['parent_filter_id']);
		$filter->setSequence((int)$row['seq']);
		$this->getDataObjectSettings('filter_settings', 'filter_id', $row['filter_id'], $filter);

		// Recursively retrieve sub-filters of this filter.
		$this->_populateSubFilters($filter);

		// Release the lock on the filter id.
		unset($lockedFilters[$filterId]);

		return $filter;
	}

	/**
	 * Populate the sub-filters (if any) for the
	 * given parent filter.
	 * @param $parentFilter PersistableFilter
	 */
	function _populateSubFilters($parentFilter) {
		if (!is_a($parentFilter, 'CompositeFilter')) {
			// Nothing to do. Only composite filters
			// can have sub-filters.
			return;
		}

		// Retrieve the sub-filters from the database.
		$parentFilterId = $parentFilter->getId();
		$result = $this->retrieve(
			'SELECT * FROM filters WHERE parent_filter_id = ? ORDER BY seq',
			(int) $parentFilterId
		);
		$daoResultFactory = new DAOResultFactory($result, $this, '_fromRow', array('filter_id'));

		// Add sub-filters.
		while (!$daoResultFactory->eof()) {
			// Retrieve the sub filter.
			// NB: This recursively loads sub-filters
			// of this filter via _fromRow().
			$subFilter = $daoResultFactory->next();

			// Add the sub-filter to the filter list
			// of its parent filter.
			$parentFilter->addFilter($subFilter);
		}
	}

	/**
	 * Recursively insert sub-filters of
	 * the given parent filter.
	 * @param $parentFilter Filter
	 */
	function _insertSubFilters($parentFilter) {
		if (!is_a($parentFilter, 'CompositeFilter')) {
			// Nothing to do. Only composite filters
			// can have sub-filters.
			return;
		}

		// Recursively insert sub-filters
		foreach($parentFilter->getFilters() as $subFilter) {
			$subFilter->setParentFilterId($parentFilter->getId());
			$subfilterId = $this->insertObject($subFilter);
			assert(is_numeric($subfilterId));
		}
	}

	/**
	 * Recursively delete all sub-filters for
	 * the given parent filter.
	 * @param $parentFilterId integer
	 */
	function _deleteSubFiltersByParentFilterId($parentFilterId) {
		$parentFilterId = (int)$parentFilterId;

		// Identify sub-filters.
		$result = $this->retrieve(
			'SELECT * FROM filters WHERE parent_filter_id = ?',
			(int) $parentFilterId
		);

		$allSubFilterRows = $result->GetArray();
		foreach($allSubFilterRows as $subFilterRow) {
			// Delete sub-filters
			// NB: We need to do this before we delete
			// sub-sub-filters to avoid loops.
			$subFilterId = $subFilterRow['filter_id'];
			$this->deleteObjectById($subFilterId);

			// Recursively delete sub-sub-filters.
			$this->_deleteSubFiltersByParentFilterId($subFilterId);
		}
	}
}

?>
