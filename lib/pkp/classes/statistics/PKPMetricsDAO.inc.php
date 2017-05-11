<?php
/**
 * @defgroup lib_pkp_classes_statistics
 */

/**
 * @file lib/pkp/classes/statistics/PKPMetricsDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPMetricsDAO
 * @ingroup lib_pkp_classes_statistics
 *
 * @brief Class with basic operations for retrieving and adding statistics data.
 */


class PKPMetricsDAO extends DAO {

	/**
	 * Retrieve a range of aggregate, filtered, ordered metric values, i.e.
	 * a statistics report.
	 *
	 * @see <http://pkp.sfu.ca/wiki/index.php/OJSdeStatisticsConcept#Input_and_Output_Formats_.28Aggregation.2C_Filters.2C_Metrics_Data.29>
	 * for a full specification of the input and output format of this method.
	 *
	 * @param $metricType string|array metrics selection
	 * @param $columns string|array column (aggregation level) selection
	 * @param $filters array report-level filter selection
	 * @param $orderBy array order criteria
	 * @param $range null|DBResultRange paging specification
	 * @param $nonAdditive boolean (optional) Whether the metric type dimension
	 * will be additive or not. This must be used with care, different metric types
	 * should not be additive because they may diverge in ways of counting usage events.
	 *
	 * @return null|array The selected data as a simple tabular result set or
	 *  null if metrics are not supported, the specified report
	 *  is invalid or cannot be produced or another error occurred.
	 */
	function &getMetrics($metricType, $columns = array(), $filters = array(), $orderBy = array(), $range = null, $nonAdditive = true) {
		// Return by reference.
		$nullVar = null;

		// Canonicalize and validate parameter format.
		if (is_scalar($metricType)) $metricType = array($metricType);
		if (is_scalar($columns)) $columns = array($columns);
		if (!(is_array($filters) && is_array($orderBy))) return $nullVar;

		// Validate parameter content.
		foreach ($metricType as $metricTypeElement) {
			if (!is_string($metricTypeElement)) return $nullVar;
		}
		$validColumns = array(
			STATISTICS_DIMENSION_CONTEXT_ID,
			STATISTICS_DIMENSION_PKP_SECTION_ID,
			STATISTICS_DIMENSION_ASSOC_OBJECT_ID,
			STATISTICS_DIMENSION_ASSOC_OBJECT_TYPE,
			STATISTICS_DIMENSION_SUBMISSION_ID,
			STATISTICS_DIMENSION_REPRESENTATION_ID,
			STATISTICS_DIMENSION_FILE_TYPE,
			STATISTICS_DIMENSION_ASSOC_TYPE,
			STATISTICS_DIMENSION_ASSOC_ID,
			STATISTICS_DIMENSION_COUNTRY,
			STATISTICS_DIMENSION_REGION,
			STATISTICS_DIMENSION_CITY,
			STATISTICS_DIMENSION_MONTH,
			STATISTICS_DIMENSION_DAY,
			STATISTICS_DIMENSION_METRIC_TYPE
		);

		// If the metric column was defined, remove it. We've already
		// add that below.
		$metricKey = array_search(STATISTICS_METRIC, $columns);
		if ($metricKey !== false) unset($columns[$metricKey]);

		if (count(array_diff($columns, $validColumns)) > 0) return $nullVar;
		$validColumns[] = STATISTICS_METRIC;
		foreach ($filters as $filterColumn => $value) {
			if (!in_array($filterColumn, $validColumns)) return $nullVar;
		}
		$validDirections = array(STATISTICS_ORDER_ASC, STATISTICS_ORDER_DESC);
		foreach ($orderBy as $orderColumn => $direction) {
			if (!in_array($orderColumn, $validColumns)) return $nullVar;
			if (!in_array($direction, $validDirections)) return $nullVar;
		}

		// Validate correct use of the (non-additive) metric type dimension. We
		// either require a filter on a single metric type or the metric type
		// must be present as a column.
		if (empty($metricType)) return $nullVar;
		if (count($metricType) !== 1 && $nonAdditive) {
			if (!in_array(STATISTICS_DIMENSION_METRIC_TYPE, $columns)) {
				array_push($columns, STATISTICS_DIMENSION_METRIC_TYPE);
			}
		}

		// Add the metric type as filter.
		$filters[STATISTICS_DIMENSION_METRIC_TYPE] = $metricType;

		// Build the select and group by clauses.
		if (empty($columns)) {
			$selectClause = 'SELECT SUM(metric) AS metric';
			$groupByClause = '';
		} else {
			$selectedColumns = implode(', ', $columns);
			$selectClause = "SELECT $selectedColumns, SUM(metric) AS metric";
			$groupByClause = "GROUP BY $selectedColumns";
		}

		// Build the where and having clauses.
		$params = array();
		$whereClause = '';
		$havingClause = '';
		$isFirst = true;
		foreach ($filters as $column => $values) {
			// The filter array contains STATISTICS_* constants for the filtered
			// hierarchy aggregation level as keys.
			if ($column === STATISTICS_METRIC) {
				$havingClause = 'HAVING ';
				$currentClause =& $havingClause; // Reference required.
			} else {
				if ($isFirst && $column) {
					$whereClause = 'WHERE ';
					$isFirst = false;
				} else {
					$whereClause .= ' AND ';
				}
				$currentClause =& $whereClause; // Reference required.
			}

			if (is_array($values) && isset($values['from'])) {
				// Range filter: The value is a hashed array with from/to entries.
				if (!isset($values['to'])) return $nullVar;
				$currentClause .= "($column BETWEEN ? AND ?)";
				$params[] = $values['from'];
				$params[] = $values['to'];
			} else {
				// Element selection filter: The value is a scalar or an
				// unordered array of one or more hierarchy element IDs.
				if (is_array($values) && count($values) === 1) {
					$values = array_pop($values);
				}
				if (is_scalar($values)) {
					$currentClause .= "$column = ?";
					$params[] = $values;
				} else {
					$placeholders = array_pad(array(), count($values), '?');
					$placeholders = implode(', ', $placeholders);
					$currentClause .= "$column IN ($placeholders)";
					foreach ($values as $value) {
						$params[] = $value;
					}
				}
			}

			unset($currentClause);
		}

		// Replace the current time constant by time values
		// inside the parameters array.
		$currentTime = array(
			STATISTICS_YESTERDAY => date('Ymd', strtotime('-1 day', time())),
			STATISTICS_CURRENT_MONTH => date('Ym', time()));
		foreach ($currentTime as $constant => $time) {
			$currentTimeKeys = array_keys($params, $constant);
			foreach ($currentTimeKeys as $key) {
				$params[$key] = $time;
			}
		}

		// Build the order-by clause.
		$orderByClause = '';
		if (count($orderBy) > 0) {
			$isFirst = true;
			foreach ($orderBy as $orderColumn => $direction) {
				if ($isFirst) {
					$orderByClause = 'ORDER BY ';
				} else {
					$orderByClause .= ', ';
				}
				$orderByClause .= "$orderColumn $direction";
				$isFirst = false;
			}
		}

		// Build the report.
		$sql = "$selectClause FROM metrics $whereClause $groupByClause $havingClause $orderByClause";
		if (is_a($range, 'DBResultRange')) {
			if ($range->getCount() > STATISTICS_MAX_ROWS) {
				$range->setCount(STATISTICS_MAX_ROWS);
			}
			$result = $this->retrieveRange($sql, $params, $range);
		} else {
			$result = $this->retrieveLimit($sql, $params, STATISTICS_MAX_ROWS);
		}

		// Return the report.
		$returner = $result->GetAll();
		return $returner;
	}

	/**
	 * Get all load ids that are associated
	 * with records filtered by the passed
	 * arguments.
	 * @param $assocType int
	 * @param $assocId int
	 * @param $metricType string
	 * @return array
	 */
	function getLoadId($assocType, $assocId, $metricType) {
		$params = array($assocType, $assocId, $metricType);
		$result = $this->retrieve('SELECT load_id FROM metrics WHERE assoc_type = ? AND assoc_id = ? AND metric_type = ? GROUP BY load_id', $params);

		$loadIds = array();
		while (!$result->EOF) {
			$row = $result->FetchRow();
			$loadIds[] = $row['load_id'];
		}

		return $loadIds;
	}

	/**
	 * Check for the presence of any record
	 * that has the passed metric type.
	 * @param $metricType string
	 * @return boolean
	 */
	function hasRecord($metricType) {
		$result = $this->retrieve('SELECT load_id FROM metrics WHERE metric_type = ? LIMIT 1', array($metricType));
		$row = $result->GetRowAssoc();
		if ($row) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Purge a load batch.
	 *
	 * @param $loadId string
	 */
	function purgeLoadBatch($loadId) {
		$this->update('DELETE FROM metrics WHERE load_id = ?', $loadId); // Not a number.
	}

	/**
	 * Purge all records associated with the passed metric type
	 * until the passed date.
	 * @param $metricType string
	 * @param $toDate string
	 */
	function purgeRecords($metricType, $toDate) {
		$this->update('DELETE FROM metrics WHERE metric_type = ? AND day IS NOT NULL AND day <= ?', array($metricType, $toDate));
	}

	/**
	 * Insert an entry into metrics table.
	 *
	 * @param $record array
	 * @param $errorMsg string
	 */
	function insertRecord($record) {
		$recordToStore = array();

		// Required dimensions.
		$requiredDimensions = array('load_id', 'assoc_type', 'assoc_id', 'metric_type');
		foreach ($requiredDimensions as $requiredDimension) {
			if (!isset($record[$requiredDimension])) {
				throw new Exception('Cannot load record: missing dimension "' . $requiredDimension . '".');
			}
			$recordToStore[$requiredDimension] = $record[$requiredDimension];
		}
		$assocType = $recordToStore['assoc_type'] = (int)$recordToStore['assoc_type'];
		$assocId = $recordToStore['assoc_id'] = (int)$recordToStore['assoc_id'];

		list($contextId, $pkpSectionId, $assocObjType,
			$assocObjId, $submissionId, $representationId) = $this->foreignKeyLookup($assocType, $assocId);

		$recordToStore['context_id'] = $contextId;
		$recordToStore['pkp_section_id'] = $pkpSectionId;
		$recordToStore['assoc_object_type'] = $assocObjType;
		$recordToStore['assoc_object_id'] = $assocObjId;
		$recordToStore['submission_id'] = $submissionId;
		$recordToStore['representation_id'] = $representationId;

		// File type is optional.
		if (isset($record['file_type']) && $record['file_type']) $recordToStore['file_type'] = (int)$record['file_type'];

		// We require either month or day in the time dimension.
		if (isset($record['day'])) {
			if (!PKPString::regexp_match('/[0-9]{8}/', $record['day'])) {
				throw new Exception('Cannot load record: invalid date.');
			}
			$recordToStore['day'] = $record['day'];
			$recordToStore['month'] = substr($record['day'], 0, 6);
			if (isset($record['month']) && $recordToStore['month'] != $record['month']) {
				throw new Exception('Cannot load record: invalid month.');
			}
		} elseif (isset($record['month'])) {
			if (!PKPString::regexp_match('/[0-9]{6}/', $record['month'])) {
				throw new Exception('Cannot load record: invalid month.');
			}
			$recordToStore['month'] = $record['month'];
		} else {
			throw new Exception('Cannot load record: Missing time dimension.');
		}

		// Geolocation is optional.
		if (isset($record['country_id'])) $recordToStore['country_id'] = (string)$record['country_id'];
		if (isset($record['region'])) $recordToStore['region'] = (string)$record['region'];
		if (isset($record['city'])) $recordToStore['city'] = (string)$record['city'];

		// The metric must be set. If it is 0 we ignore the record.
		if (!isset($record['metric'])) {
			throw new Exception('Cannot load record: metric is missing.');
		}
		if (!is_numeric($record['metric'])) {
			throw new Exception('Cannot load record: invalid metric.');
		}
		$recordToStore['metric'] = (int) $record['metric'];

		// Save the record to the database.
		$fields = implode(', ', array_keys($recordToStore));
		$placeholders = implode(', ', array_pad(array(), count($recordToStore), '?'));
		$params = array_values($recordToStore);
		return $this->update("INSERT INTO metrics ($fields) VALUES ($placeholders)", $params);
	}


	//
	// Protected methods.
	//
	/**
	 * Foreign key lookup for the published object dimension.
	 * @param $assocType int
	 * @param $assocId int
	 * @return array Values must be foreign keys relative to the
	 * context, pkp section, associated object (type and id), submission
	 * and representation.
	 */
	protected function foreignKeyLookup($assocType, $assocId) {
		$contextId = $sectionId = $submissionId = $assocObjectType = $assocObjectId = $representationId = null;

		$isFile = false;
		$isRepresentation = false;

		switch($assocType) {
			case ASSOC_TYPE_SUBMISSION_FILE:
				$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
				$submissionFile = $submissionFileDao->getLatestRevision($assocId);
				if ($submissionFile) {
					$isFile = true;
					$submissionId = $submissionFile->getSubmissionId();
					if ($submissionFile->getAssocType() == ASSOC_TYPE_REPRESENTATION) {
						$representationId = $submissionFile->getAssocId();
					} else {
						throw new Exception('Cannot load record: submission file is not associated with a representation object.');
					}
				} else {
					throw new Exception('Cannot load record: invalid submission file id.');
				}
				// Don't break but go on to retrieve the representation.
			case ASSOC_TYPE_REPRESENTATION:
				if (!$isFile) $representationId = $assocId;
				$representationDao = Application::getRepresentationDAO(); /* @var $representationDao RepresentationDAO */
				$representation = $representationDao->getById($representationId); /* @var $representation Representation */
				if ($representation) {
					if (!$isFile) $isRepresentation = true;

					$contextId = $representation->getContextId();
					$submissionId = $representation->getSubmissionId();
				} else {
					throw new Exception('Cannot load record: invalid representation id.');
				}
				// Don't break but go on to retrieve the submission.
			case ASSOC_TYPE_SUBMISSION:
				if (!$isFile && !$isRepresentation) $submissionId = $assocId;
				$submissionDao = Application::getSubmissionDAO(); /* @var $submissionDao SubmissionDAO */
				$submission = $submissionDao->getById($submissionId);
				if ($submission) {
					$contextId = $submission->getContextId();
					$submissionId = $submission->getId();
					$sectionId = $submission->getSectionId();
				} else {
					throw new Exception('Cannot load record: invalid submission id.');
				}
				list($assocObjectType, $assocObjectId) = $this->getAssocObjectInfo($submissionId, $contextId);
				break;
			case ASSOC_TYPE_SECTION:
				$sectionDao = Application::getSectionDAO();
				$section = $sectionDao->getById($assocId); /* @var $section PKPSection */
				if ($section) {
					$sectionId = $section->getId();
					$contextId = $section->getContextId();
				} else {
					throw new Exception('Cannot load record: invalid section id.');
				}
				break;
			case Application::getContextAssocType():
				$contextDao = Application::getContextDAO(); /* @var $contextDao ContextDAO */
				$context = $contextDao->getById($assocId);
				if (!$context) {
					throw new Exception('Cannot load record: invalid context id.');
				}
				$contextId = $assocId;
				break;
		}

		return array($contextId, $sectionId, $assocObjectType, $assocObjectId, $submissionId, $representationId);
	}

	/**
	 * Get the id and type of the object that
	 * the passed submission info is associated with.
	 * Default implementation returns null, subclasses
	 * have to implement it.
	 * @param $submissionId Submission id.
	 * @param $contextId The submission context id.
	 * @return array Assoc type and id of the object.
	 */
	protected function getAssocObjectInfo($submissionId, $contextId) {
		return array(null, null);
	}
}
?>
