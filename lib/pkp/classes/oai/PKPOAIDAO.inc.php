<?php

/**
 * @file classes/oai/PKPOAIDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPOAIDAO
 * @ingroup oai
 * @see OAI
 *
 * @brief Base class for DAO operations for the OAI interface.
 */

import('lib.pkp.classes.oai.OAI');

abstract class PKPOAIDAO extends DAO {

 	/** @var OAI parent OAI object */
 	var $oai;

 	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Set parent OAI object.
	 * @param JournalOAI
	 */
	function setOAI($oai) {
		$this->oai = $oai;
	}

	//
	// Records
	//
	/**
	 * Get the SQL query fragment to fetch the earliest datestamp.
	 * @return string
	 */
	abstract function getEarliestDatestampQuery();

	/**
	 * Return the *nix timestamp of the earliest published submission.
	 * @param $setIds array optional Objects ids that specify an OAI set,
	 * in hierarchical order. If empty, all records from
	 * all sets will be included.
	 * @return int
	 */
	function getEarliestDatestamp($setIds = array()) {
		$params = $this->getOrderedRecordParams(null, $setIds);

		$result = $this->retrieve(
			$this->getEarliestDatestampQuery() . ' FROM mutex m ' .
			$this->getRecordJoinClause(null, $setIds) . ' ' .
			$this->getAccessibleRecordWhereClause(),
			$params
		);

		if (isset($result->fields[0])) {
			$timestamp = strtotime($this->datetimeFromDB($result->fields[0]));
		}
		if (!isset($timestamp) || $timestamp == -1) {
			$timestamp = 0;
		}

		$result->Close();
		return $timestamp;
	}

	/**
	 * Check if a data object ID specifies a data object.
	 * @param $dataObjectId int
	 * @param $setIds array optional Objects ids that specify an OAI set,
	 * in hierarchical order. If passed, will check for the data object id
	 * only inside the specified set.
	 * @return boolean
	 */
	function recordExists($dataObjectId, $setIds = array()) {
		$params = $this->getOrderedRecordParams($dataObjectId, $setIds);

		$result = $this->retrieve(
			'SELECT	COUNT(*)
			FROM mutex m ' .
			$this->getRecordJoinClause($dataObjectId, $setIds) . ' ' .
			$this->getAccessibleRecordWhereClause(),
			$params
		);

		$returner = $result->fields[0] == 1;

		$result->Close();
		return $returner;
	}

	/**
	 * Return OAI record for specified data object.
	 * @param $dataObjectId int
	 * @param $setIds array optional Objects ids that specify an OAI set,
	 * in hierarchical order. If passed, will check for the data object id
	 * only inside the specified set.
	 * @return OAIRecord
	 */
	function getRecord($dataObjectId, $setIds = array()) {
		$params = $this->getOrderedRecordParams($dataObjectId, $setIds);

		$result = $this->retrieve(
			$this->getRecordSelectStatement() . ' FROM mutex m ' .
			$this->getRecordJoinClause($dataObjectId, $setIds) . ' ' .
			$this->getAccessibleRecordWhereClause(),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$row = $result->GetRowAssoc(false);
			$returner = $this->_returnRecordFromRow($row);
		}

		$result->Close();
		return $returner;
	}

	/**
	 * Return set of OAI records matching specified parameters.
	 * @param $setIds array Objects ids that specify an OAI set,
	 * in hierarchical order. The returned records will be part
	 * of this set.
	 * @param $from int timestamp
	 * @param $until int timestamp
	 * @param $set string setSpec
	 * @param $offset int
	 * @param $limit int
	 * @param $total int
	 * @return array OAIRecord
	 */
	function getRecords($setIds, $from, $until, $set, $offset, $limit, &$total) {
		$records = array();

		$result = $this->_getRecordsRecordSet($setIds, $from, $until, $set);

		$total = $result->RecordCount();

		$result->Move($offset);
		for ($count = 0; $count < $limit && !$result->EOF; $count++) {
			$row = $result->GetRowAssoc(false);
			$records[] = $this->_returnRecordFromRow($row);
			$result->MoveNext();
		}

		$result->Close();
		return $records;
	}

	/**
	 * Return set of OAI identifiers matching specified parameters.
	 * @param $setIds array Objects ids that specify an OAI set,
	 * in hierarchical order. The returned records will be part
	 * of this set.
	 * @param $from int timestamp
	 * @param $until int timestamp
	 * @param $set string setSpec
	 * @param $offset int
	 * @param $limit int
	 * @param $total int
	 * @return array OAIIdentifier
	 */
	function getIdentifiers($setIds, $from, $until, $set, $offset, $limit, &$total) {
		$records = array();

		$result = $this->_getRecordsRecordSet($setIds, $from, $until, $set);

		$total = $result->RecordCount();

		$result->Move($offset);
		for ($count = 0; $count < $limit && !$result->EOF; $count++) {
			$row = $result->GetRowAssoc(false);
			$records[] = $this->_returnIdentifierFromRow($row);
			$result->MoveNext();
		}

		$result->Close();
		return $records;
	}


	//
	// Resumption tokens
	//
	/**
	 * Clear stale resumption tokens.
	 */
	function clearTokens() {
		$this->update(
			'DELETE FROM oai_resumption_tokens WHERE expire < ?', time()
		);
	}

	/**
	 * Retrieve a resumption token.
	 * @return OAIResumptionToken
	 */
	function getToken($tokenId) {
		$result = $this->retrieve(
			'SELECT * FROM oai_resumption_tokens WHERE token = ?',
			array($tokenId)
		);

		if ($result->RecordCount() == 0) {
			$token = null;

		} else {
			$row = $result->getRowAssoc(false);
			$token = new OAIResumptionToken($row['token'], $row['record_offset'], unserialize($row['params']), $row['expire']);
		}

		$result->Close();
		return $token;
	}

	/**
	 * Insert an OAI resumption token, generating a new ID.
	 * @param $token OAIResumptionToken
	 * @return OAIResumptionToken
	 */
	function insertToken($token) {
		do {
			// Generate unique token ID
			$token->id = md5(uniqid(mt_rand(), true));
			$result = $this->retrieve(
				'SELECT COUNT(*) FROM oai_resumption_tokens WHERE token = ?',
				array($token->id)
			);
			$val = $result->fields[0];

			$result->Close();
		} while($val != 0);

		$this->update(
			'INSERT INTO oai_resumption_tokens (token, record_offset, params, expire)
			VALUES
			(?, ?, ?, ?)',
			array($token->id, $token->offset, serialize($token->params), $token->expire)
		);

		return $token;
	}


	//
	// Protected methods.
	//
	/**
	 * Get an array with the parameters in the correct
	 * order to be used by the get record join sql. If
	 * you need a different order, override this method.
	 * @param $dataObjectId int
	 * @param $setIds array Objects ids that specify an OAI set,
	 * in hierarchical order.
	 * @param $set String
	 * @return array
	 */
	function getOrderedRecordParams($dataObjectId = null, $setIds = array(), $set = null) {
		$params = array();

		if (isset($dataObjectId)) {
			$params[] = $dataObjectId;
		}

		$notNullSetIds = array();
		if (is_array($setIds) && !empty($setIds)) {
			foreach($setIds as $id) {
				// Avoid null values.
				if (is_null($id)) continue;
				$notNullSetIds[] = (int) $id;
				$params[] = (int) $id;
			}
		}

		// Add the data object id again.
		if (isset($dataObjectId)) {
			$params[] = $dataObjectId;
		}

		// Add the set specification, if any.
		if (isset($set)) {
			$params[] = $set;
		}

		// Add the set ids again, so they can be used in the tombstone JOIN part of the sql too.
		$params = array_merge($params, $notNullSetIds);

		return $params;
	}

	/**
	 * Return the string defining the SELECT part of an sql
	 * that will select all the necessary fields to build a record
	 * object.
	 *
	 * Must be implemented by subclasses.
	 *
	 * @return string
	 */
	abstract function getRecordSelectStatement();

	/**
	 * Return the string defining the JOIN part of an sql
	 * that will join all necessary tables to make available all
	 * fields selected on the getRecordSelectStatement().
	 *
	 * Must be implemented by subclasses.
	 *
	 * @param $dataObjectId int
	 * @param $setIds array Objects ids that specify an OAI set,
	 * in hierarchical order.
	 * @param $set string
	 * @return string
	 */
	abstract function getRecordJoinClause($dataObjectId = null, $setIds = array(), $set = null);

	/**
	 * Return the string defining the WHERE part of
	 * an sql that will filter only accessible OAI records.
	 *
	 * Must be implemented by subclasses.
	 * @return string
	 */
	abstract function getAccessibleRecordWhereClause();

	/**
	 * Return the string defining the WHERE part of
	 * an sql that will filter records in an specific
	 * date range.
	 *
	 * Must be implemented by subclasses.
	 * @param $from int/string *nix timestamp or ISO datetime string
	 * @param $until int/string *nix timestamp or ISO datetime string
	 * @return string
	 */
	abstract function getDateRangeWhereClause($from, $until);

	/**
	 * Set application specific data to OAIRecord and OAIIdentifier objects.
	 *
	 * Must be implemented by subclasses.
	 * @param $record OAIIdentifier/OAIRecord
	 * @param $row array
	 * @param $isRecord boolean Is the object an OAIRecord? If true, specific
	 * OAIRecord data can be set.
	 * @return OAIIdentifier/OAIRecord
	 */
	abstract function setOAIData($record, $row, $isRecord);


	//
	// Private helper methods.
	//
	/**
	 * Return OAIRecord object from database row.
	 * @param $row array
	 * @return OAIRecord
	 */
	function _returnRecordFromRow($row) {
		$record = new OAIRecord();
		$record = $this->_doCommonOAIFromRowOperations($record, $row);

		HookRegistry::call('OAIDAO::_returnRecordFromRow', array(&$record, &$row));

		return $record;
	}

	/**
	 * Return OAIIdentifier object from database row.
	 * @param $row array
	 * @return OAIIdentifier
	 */
	function _returnIdentifierFromRow($row) {
		$record = new OAIIdentifier();
		$record = $this->_doCommonOAIFromRowOperations($record, $row);

		HookRegistry::call('OAIDAO::_returnIdentifierFromRow', array(&$record, &$row));

		return $record;
	}

	/**
	 * Common operations for OAIRecord and OAIIdentifier object data set.
	 * @param $record OAIRecord/OAIIdentifier
	 * @param $row array
	 * @return OAIRecord/OAIIdentifier
	 */
	function _doCommonOAIFromRowOperations($record, $row) {
		$record->datestamp = OAIUtils::UTCDate(strtotime($this->datetimeFromDB($row['last_modified'])));

		if (isset($row['tombstone_id'])) {
			$record->identifier = $row['oai_identifier'];
			$record->sets = array($row['set_spec']);
			$record->status = OAIRECORD_STATUS_DELETED;
		} else {
			$record->status = OAIRECORD_STATUS_ALIVE;
			$record = $this->setOAIData($record, $row, is_a($record, 'OAIRecord'));
		}

		return $record;
	}

	/**
	 * Get a OAI records record set.
	 * @param $setIds array Objects ids that specify an OAI set,
	 * in hierarchical order.
	 * @param $from int/string *nix timestamp or ISO datetime string
	 * @param $until int/string *nix timestamp or ISO datetime string
	 * @param $set string
	 * @return ADORecordSet
	 */
	function _getRecordsRecordSet($setIds, $from, $until, $set) {
		$params = $this->getOrderedRecordParams(null, $setIds, $set);

		$result = $this->retrieve(
			$this->getRecordSelectStatement() . ' FROM mutex m ' .
			$this->getRecordJoinClause(null, $setIds, $set) . ' ' .
			$this->getAccessibleRecordWhereClause() .
			$this->getDateRangeWhereClause($from, $until),
			$params
		);

		return $result;
	}
}

?>
