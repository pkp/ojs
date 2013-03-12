<?php
/**
 * @defgroup classes_statistics
 */

/**
 * @file classes/statistics/MetricsDAO.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MetricsDAO
 * @ingroup classes_statistics
 *
 * @brief Operations for retrieving and adding statistics data.
 */


class MetricsDAO extends DAO {

	/**
	 * Purge a load batch before re-loading it.
	 *
	 * @param $loadId string
	 */
	function purgeLoadBatch($loadId) {
		$this->update('DELETE FROM metrics WHERE load_id = ?', $loadId);
	}

	/**
	 * Insert an entry into metrics table.
	 *
	 * @param $record array
	 */
	function insertRecord(&$record) {
		$recordToStore = array();

		// Required dimensions.
		$requiredDimensions = array('load_id', 'assoc_type', 'assoc_id', 'metric_type');
		foreach ($requiredDimensions as $requiredDimension) {
			if (!isset($record[$requiredDimension])) {
				throw new Exception('Cannot load record: missing dimension "' . $requiredDimension . '".');
			}
			$recordToStore[$requiredDimension] = $record[$requiredDimension];
		}
		$recordToStore['assoc_type'] = (int)$recordToStore['assoc_type'];
		$recordToStore['assoc_id'] = (int)$recordToStore['assoc_id'];

		// We require either month or day in the time dimension.
		if (isset($record['day'])) {
			if (!String::regexp_match('/[0-9]{8}/', $record['day'])) {
				throw new Exception('Cannot load record: invalid date.');
			}
			$recordToStore['day'] = $record['day'];
			$recordToStore['month'] = substr($record['day'], 0, 6);
			if (isset($record['month']) && $recordToStore['month'] != $record['month']) {
				throw new Exception('Cannot load record: invalid month.');
			}
		} elseif (isset($record['month'])) {
			if (!String::regexp_match('/[0-9]{6}/', $record['month'])) {
				throw new Exception('Cannot load record: invalid month.');
			}
			$recordToStore['month'] = $record['month'];
		} else {
			throw new Exception('Cannot load record: Missing time dimension.');
		}

		// Country is optional.
		if (isset($record['country_id'])) $recordToStore['country_id'] = (int)$record['country_id'];

		// The metric must be set. If it is 0 we ignore the record.
		if (!isset($record['metric'])) {
			throw new Exception('Cannot load record: metric is missing.');
		}
		if (!is_numeric($record['metric'])) {
			throw new Exception('Cannot load record: invalid metric.');
		}
		$recordToStore['metric'] = (int) $record['metric'];
		if ($recordToStore['metric'] == 0) return;

		// Save the record to the database.
		$fields = implode(', ', array_keys($recordToStore));
		$placeholders = implode(', ', array_pad(array(), count($recordToStore), '?'));
		$params = array_values($recordToStore);
		$this->update("INSERT INTO metrics ($fields) VALUES ($placeholders)", $params);
	}
}

?>
