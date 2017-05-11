<?php

/**
 * @file classes/controlledVocab/ControlledVocabEntryDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ControlledVocabEntryDAO
 * @ingroup controlled_vocab
 * @see ControlledVocabEntry
 *
 * @brief Operations for retrieving and modifying ControlledVocabEntry objects
 */

import('lib.pkp.classes.controlledVocab.ControlledVocabEntry');

class ControlledVocabEntryDAO extends DAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Return the entry settings DAO.
	 * Can be subclassed to provide extended DAOs.
	 */
	function getSettingsDAO() {
		return DAORegistry::getDAO('ControlledVocabEntrySettingsDAO');
	}

	/**
	 * Retrieve a controlled vocab entry by controlled vocab entry ID.
	 * @param $controlledVocabEntryId int
	 * @param $controlledVocabEntry int optional
	 * @return ControlledVocabEntry
	 */
	function getById($controlledVocabEntryId, $controlledVocabId = null) {
		$params = array((int) $controlledVocabEntryId);
		if (!empty($controlledVocabId)) $params[] = (int) $controlledVocabId;

		$result = $this->retrieve(
			'SELECT * FROM controlled_vocab_entries WHERE controlled_vocab_entry_id = ?' .
			(!empty($controlledVocabId)?' AND controlled_vocab_id = ?':''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve a controlled vocab entry by resolving one of its settings
	 * to the corresponding entry id.
	 * @param $settingValue string the setting value to be searched for
	 * @param $symbolic string the vocabulary to be searched, identified by its symbolic name
	 * @param $assocType integer
	 * @param $assocId integer
	 * @param $settingName string the setting to be searched
	 * @param $locale string
	 * @return ControlledVocabEntry
	 */
	function getBySetting($settingValue, $symbolic, $assocType = 0, $assocId = 0, $settingName = 'name', $locale = '') {
		$result = $this->retrieve(
			'SELECT cve.*
			 FROM controlled_vocabs cv
			 INNER JOIN controlled_vocab_entries cve ON cv.controlled_vocab_id = cve.controlled_vocab_id
			 INNER JOIN controlled_vocab_entry_settings cves ON cve.controlled_vocab_entry_id = cves.controlled_vocab_entry_id
			 WHERE	cves.setting_name = ? AND
				cves.locale = ? AND
				cves.setting_value = ? AND
				cv.symbolic = ? AND
				cv.assoc_type = ? AND
				cv.assoc_id = ?',
			array($settingName, $locale, $settingValue, $symbolic, $assocType, $assocId)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return ControlledVocabEntry
	 */
	function newDataObject() {
		return new ControlledVocabEntry();
	}

	/**
	 * Internal function to return an ControlledVocabEntry object from a
	 * row.
	 * @param $row array
	 * @return ControlledVocabEntry
	 */
	function _fromRow($row) {
		$controlledVocabEntry = $this->newDataObject();
		$controlledVocabEntry->setControlledVocabId($row['controlled_vocab_id']);
		$controlledVocabEntry->setId($row['controlled_vocab_entry_id']);
		$controlledVocabEntry->setSequence($row['seq']);

		$this->getDataObjectSettings('controlled_vocab_entry_settings', 'controlled_vocab_entry_id', $row['controlled_vocab_entry_id'], $controlledVocabEntry);

		return $controlledVocabEntry;
	}

	/**
	 * Get the list of fields for which data can be localized.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('name');
	}

	/**
	 * Update the localized fields for this table
	 * @param $controlledVocabEntry object
	 */
	function updateLocaleFields($controlledVocabEntry) {
		$this->updateDataObjectSettings('controlled_vocab_entry_settings', $controlledVocabEntry, array(
			'controlled_vocab_entry_id' => $controlledVocabEntry->getId()
		));
	}

	/**
	 * Insert a new ControlledVocabEntry.
	 * @param $controlledVocabEntry ControlledVocabEntry
	 * @return int Inserted controlled vocabulary entry ID
	 */
	function insertObject($controlledVocabEntry) {
		$this->update(
			'INSERT INTO controlled_vocab_entries (controlled_vocab_id, seq)
			VALUES (?, ?)',
			array(
				(int) $controlledVocabEntry->getControlledVocabId(),
				(float) $controlledVocabEntry->getSequence()
			)
		);
		$controlledVocabEntry->setId($this->getInsertId());
		$this->updateLocaleFields($controlledVocabEntry);
		return (int)$controlledVocabEntry->getId();
	}

	/**
	 * Delete a controlled vocab entry.
	 * @param $controlledVocabEntry ControlledVocabEntry
	 */
	function deleteObject($controlledVocabEntry) {
		$this->deleteObjectById($controlledVocabEntry->getId());
	}

	/**
	 * Delete a controlled vocab entry by controlled vocab entry ID.
	 * @param $controlledVocabEntryId int
	 */
	function deleteObjectById($controlledVocabEntryId) {
		$params = array((int) $controlledVocabEntryId);
		$this->update('DELETE FROM controlled_vocab_entry_settings WHERE controlled_vocab_entry_id = ?', $params);
		$this->update('DELETE FROM controlled_vocab_entries WHERE controlled_vocab_entry_id = ?', $params);
	}

	/**
	 * Retrieve an iterator of controlled vocabulary entries matching a
	 * particular controlled vocabulary ID.
	 * @param $controlledVocabId int
	 * @return object DAOResultFactory containing matching CVE objects
	 */
	function getByControlledVocabId($controlledVocabId, $rangeInfo = null, $filter = null) {
		$params = array((int) $controlledVocabId);
		if (!empty($filter)) $params[] = "%$filter%";

		$result = $this->retrieveRange(
			'SELECT *
			 FROM controlled_vocab_entries cve '.
			 (!empty($filter) ? 'INNER JOIN controlled_vocab_entry_settings cves ON cve.controlled_vocab_entry_id = cves.controlled_vocab_entry_id ' : '') .
			'WHERE controlled_vocab_id = ? ' .
			 (!empty($filter) ? 'AND cves.setting_value LIKE ? ' : '') .
			'ORDER BY seq',
			$params,
			$rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Update an existing review form element.
	 * @param $controlledVocabEntry ControlledVocabEntry
	 */
	function updateObject($controlledVocabEntry) {
		$this->update(
			'UPDATE	controlled_vocab_entries
			SET	controlled_vocab_id = ?,
				seq = ?
			WHERE	controlled_vocab_entry_id = ?',
			array(
				(int) $controlledVocabEntry->getControlledVocabId(),
				(float) $controlledVocabEntry->getSequence(),
				(int) $controlledVocabEntry->getId()
			)
		);
		$this->updateLocaleFields($controlledVocabEntry);
	}

	/**
	 * Sequentially renumber entries in their sequence order.
	 * @param $controlledVocabId int Controlled vocabulary ID
	 */
	function resequence($controlledVocabId) {
		$result = $this->retrieve(
			'SELECT controlled_vocab_entry_id FROM controlled_vocab_entries WHERE controlled_vocab_id = ? ORDER BY seq',
			array((int) $controlledVocabId)
		);

		for ($i=1; !$result->EOF; $i++) {
			list($controlledVocabEntryId) = $result->fields;
			$this->update(
				'UPDATE controlled_vocab_entries SET seq = ? WHERE controlled_vocab_entry_id = ?',
				array(
					(int) $i,
					(int) $controlledVocabEntryId
				)
			);

			$result->MoveNext();
		}

		$result->Close();
	}

	/**
	 * Get the ID of the last inserted controlled vocab.
	 * @return int
	 */
	function getInsertId() {
		return parent::_getInsertId('controlled_vocab_entries', 'controlled_vocab_entry_id');
	}
}

?>
