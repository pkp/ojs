<?php

/**
 * @file plugins/generic/usageStats/UsageStatsTemporaryRecordDAO.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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
	 * @param $assocType int
	 * @param $assocId int
	 * @param $day string
	 * @param $time int
	 * @param $countryCode string
	 * @param $region string
	 * @param $cityName string
	 * @param $fileType int
	 * @param $loadId string
	 * @return boolean
	 */
	function insert($assocType, $assocId, $day, $time, $countryCode, $region, $cityName, $fileType, $loadId) {
		$this->update(
				'INSERT INTO usage_stats_temporary_records
					(assoc_type, assoc_id, day, entry_time, country_id, region, city, file_type, load_id)
					VALUES
					(?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				(int) $assocType,
				(int) $assocId,
				$day,
				(int) $time,
				$countryCode,
				$region,
				$cityName,
				(int) $fileType,
				$loadId // Not number.
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
	function &getNextByLoadId($loadId) {
		$returner = false;

		if (!$this->_result || $this->_loadId != $loadId) {
			$this->_result =& $this->_getGrouped($loadId);
			$this->_loadId = $loadId;
		}

		$result =& $this->_result;

		if ($result->EOF) return $returner;
		$returner =& $result->GetRowAssoc(false);
		$result->MoveNext();
		return $returner;
	}

	/**
	 * Delete all temporary records associated
	 * with the passed load id.
	 * @param $loadId string
	 * @return boolean
	 */
	function deleteByLoadId($loadId) {
		return $this->update('DELETE from usage_stats_temporary_records WHERE load_id = ?', array($loadId)); // Not number.
	}

	/**
	 * Delete the record with the passed assoc id and type with
	 * the most recent day value.
	 * @param $assocType int
	 * @param $assocId int
	 * @param $time int
	 * @param $loadId string
	 * @return boolean
	 */
	function deleteRecord($assocType, $assocId, $time, $loadId) {
		return $this->update('DELETE from usage_stats_temporary_records
			WHERE assoc_type = ? AND assoc_id = ? AND entry_time = ? AND load_id = ?',
			array((int) $assocType, (int) $assocId, (int) $time, $loadId)); // Not number.
	}


	//
	// Private helper methods.
	//
	/**
	* Get all temporary records with the passed load id grouped.
	* @param $loadId string
	* @return ADORecordSet
	*/
	function &_getGrouped($loadId) {
		$result = $this->retrieve(
			'SELECT assoc_type, assoc_id, day, country_id, region, city, file_type, load_id, count(metric) as metric
			FROM usage_stats_temporary_records WHERE load_id = ?
			GROUP BY assoc_type, assoc_id, day, country_id, region, city, file_type, load_id',
			array($loadId) // Not number.
		);

		return $result;
	}
}

?>
