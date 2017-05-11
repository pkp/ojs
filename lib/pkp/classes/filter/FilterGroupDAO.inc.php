<?php

/**
 * @file classes/filter/FilterGroupDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FilterGroupDAO
 * @ingroup filter
 * @see FilterGroup
 *
 * @brief Operations for retrieving and modifying FilterGroup objects.
 */

import('lib.pkp.classes.filter.FilterGroup');

class FilterGroupDAO extends DAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Insert a new filter group.
	 *
	 * @param $filterGroup FilterGroup
	 * @return integer the new filter group id
	 */
	function insertObject(&$filterGroup) {
		$this->update(
			sprintf('INSERT INTO filter_groups
				(symbolic, display_name, description, input_type, output_type)
				VALUES (?, ?, ?, ?, ?)'),
			array(
				$filterGroup->getSymbolic(),
				$filterGroup->getDisplayName(),
				$filterGroup->getDescription(),
				$filterGroup->getInputType(),
				$filterGroup->getOutputType()
			)
		);
		$filterGroup->setId((int)$this->getInsertId());
		return $filterGroup->getId();
	}

	/**
	 * Retrieve a filter group
	 * @param $filterGroup FilterGroup
	 * @return FilterGroup
	 */
	function &getObject(&$filterGroup) {
		return $this->getObjectById($filterGroup->getId());
	}

	/**
	 * Retrieve a configured filter group by id.
	 * @param $filterGroupId integer
	 * @return FilterGroup
	 */
	function &getObjectById($filterGroupId) {
		$result = $this->retrieve(
				'SELECT * FROM filter_groups'.
				' WHERE filter_group_id = ?', $filterGroupId);

		$filterGroup = null;
		if ($result->RecordCount() != 0) {
			$filterGroup = $this->_fromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		return $filterGroup;
	}

	/**
	 * Retrieve a configured filter group by its symbolic representation.
	 * @param $filterGroupSymbolic string
	 * @return FilterGroup
	 */
	function &getObjectBySymbolic($filterGroupSymbolic) {
		$result = $this->retrieve(
				'SELECT * FROM filter_groups'.
				' WHERE symbolic = ?', $filterGroupSymbolic);

		$filterGroup = null;
		if ($result->RecordCount() != 0) {
			$filterGroup = $this->_fromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		return $filterGroup;
	}

	/**
	 * Update an existing filter group.
	 * @param $filterGroup FilterGroup
	 */
	function updateObject(&$filterGroup) {
		$this->update(
			'UPDATE	filter_groups
			SET	symbolic = ?,
				display_name = ?,
				description = ?,
				input_type = ?,
				output_type = ?
			WHERE	filter_group_id = ?',
			array(
				$filterGroup->getSymbolic(),
				$filterGroup->getDisplayName(),
				$filterGroup->getDescription(),
				$filterGroup->getInputType(),
				$filterGroup->getOutputType(),
				(integer)$filterGroup->getId()
			)
		);
	}

	/**
	 * Delete a filter group (only works if there are not more filters in this group).
	 * @param $filterGroup FilterGroup
	 * @return boolean
	 */
	function deleteObject(&$filterGroup) {
		$filterDao = DAORegistry::getDAO('FilterDAO'); /* @var $filterDao FilterDAO */

		// Check whether there are still templates saved for this filter group.
		$filterTemplates = $filterDao->getObjectsByGroup($filterGroup->getSymbolic(), null, true, false);
		if (!empty($filterTemplates)) return false;

		// Check whether there are still filters saved for this filter group.
		$filters = $filterDao->getObjectsByGroup($filterGroup->getSymbolic(), null, false, false);
		if (!empty($filters)) return false;

		// Delete the group if it's empty.
		$this->update('DELETE FROM filter_groups WHERE filter_group_id = ?', $filterGroup->getId());

		return true;
	}

	/**
	 * Delete a filter group by id.
	 * @param $filterGroupId int
	 * @return boolean
	 */
	function deleteObjectById($filterGroupId) {
		$filterGroupId = (int)$filterGroupId;
		$filterGroup =& $this->getObjectById($filterGroupId);
		if (!is_a($filterGroup, 'FilterGroup')) return false;
		return $this->deleteObject($filterGroup);
	}

	/**
	 * Delete a filter group by symbolic name.
	 * @param $filterGroupSymbolic string
	 * @return boolean
	 */
	function deleteObjectBySymbolic($filterGroupSymbolic) {
		$filterGroup =& $this->getObjectBySymbolic($filterGroupSymbolic);
		if (!is_a($filterGroup, 'FilterGroup')) return false;
		return $this->deleteObject($filterGroup);
	}


	//
	// Protected helper methods
	//
	/**
	 * Get the ID of the last inserted filter group.
	 * @return int
	 */
	function getInsertId() {
		return parent::_getInsertId('filter_groups', 'filter_group_id');
	}

	/**
	 * Construct and return a new data object
	 * @return DataObject
	 */
	function newDataObject() {
		return new FilterGroup();
	}


	//
	// Private helper methods
	//
	/**
	 * Internal function to return a filter group
	 * object from a row.
	 *
	 * @param $row array
	 * @return FilterGroup
	 */
	function _fromRow($row) {
		// Instantiate the filter group.
		$filterGroup = $this->newDataObject();

		// Configure the filter group.
		$filterGroup->setId((int)$row['filter_group_id']);
		$filterGroup->setSymbolic($row['symbolic']);
		$filterGroup->setDisplayName($row['display_name']);
		$filterGroup->setDescription($row['description']);
		$filterGroup->setInputType($row['input_type']);
		$filterGroup->setOutputType($row['output_type']);

		return $filterGroup;
	}
}

?>
