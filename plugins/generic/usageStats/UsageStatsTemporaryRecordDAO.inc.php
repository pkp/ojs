<?php

/**
 * @file plugins/generic/usageStats/UsageStatsTemporaryRecordDAO.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UsageStatsTemporaryRecordDAO
 * @ingroup plugins_generic_usageStats
 *
 * @brief Operations for retrieving and adding temporary usage statistics records.
 */


class UsageStatsTemporaryRecordDAO extends DAO {

	/** @var $_result ADORecordSet */
	var $_result;

	/** @var $_loadId string */
	var $_loadId;

	/**
	 * Constructor
	 */
	function UsageStatsTemporaryRecordDAO() {
		parent::DAO();

		$this->_result = false;
		$this->_loadId = null;
	}

	/**
	 * Add the passed usage statistic record.
	 * @param $record array
	 * @return boolean
	 */
	function insert($assocType, $assocId, $day, $countryCode, $region, $cityName, $fileType, $loadId) {
		$this->update(
				'INSERT INTO usage_stats_temporary_records
					(assoc_type, assoc_id, day, country_id, region, city, file_type, load_id)
					VALUES
					(?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$assocType,
				$assocId,
				$day,
				$countryCode,
				$region,
				$cityName,
				$fileType,
				$loadId
			)
		);

		return true;
	}

	/**
	 * Get next temporary stats record by load id.
	 * @param $loadId string
	 * @return mixed array or false if the end of
	 * records is reached.
	 */
	function getNextByLoadId($loadId) {
		if (!$this->_result || $this->_loadId != $loadId) {
			$this->_result = $this->_getGrouped($loadId);
			$this->_loadId = $loadId;
		}

		if ($this->_result->EOF) return false;
		$row = $this->_result->GetRowAssoc(false);
		$this->_result->MoveNext();
		return $row;
	}

	/**
	 * Delete all temporary records associated
	 * with the passed load id.
	 * @param $loadId string
	 * @return boolean
	 */
	function deleteByLoadId($loadId) {
		return $this->update('DELETE from usage_stats_temporary_records WHERE load_id = ?', array($loadId));
	}

	/**
	 * Delete the record with the passed assoc id and type with
	 * the most recent day value.
	 * @param $assocType int
	 * @param $assocId int
	 * @param $loadId string
	 * @return boolean
	 */
	function deleteRecord($assocType, $assocId, $loadId) {
		return $this->update('DELETE from usage_stats_temporary_records
			WHERE assoc_type = ? AND assoc_id = ? AND load_id = ?
			ORDER BY day DESC
			LIMIT 1',
			array($assocType, $assocId, $loadId));
	}


	//
	// Private helper methods.
	//
	/**
	* Get all temporary records with the passed load id grouped.
	* @param $loadId string
	* @return ADORecordSet
	*/
	function _getGrouped($loadId) {
		return $this->retrieve(
			'SELECT assoc_type, assoc_id, day, country_id, region, city, file_type, load_id, count(metric) as metric
			FROM usage_stats_temporary_records WHERE load_id = ?
			GROUP BY assoc_type, assoc_id, day, country_id, region, city, file_type, load_id',
			array($loadId)
		);
	}
}

?>
