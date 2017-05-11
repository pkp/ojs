<?php

/**
 * @file classes/controlledVocab/ControlledVocabDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ControlledVocabDAO
 * @ingroup controlled_vocab
 * @see ControlledVocab
 *
 * @brief Operations for retrieving and modifying ControlledVocab objects.
 */

import('lib.pkp.classes.controlledVocab.ControlledVocab');

class ControlledVocabDAO extends DAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Return the Controlled Vocab Entry DAO for this Controlled Vocab.
	 * Can be subclassed to provide extended DAOs.
	 */
	function getEntryDAO() {
		return DAORegistry::getDAO('ControlledVocabEntryDAO');
	}

	/**
	 * Retrieve a controlled vocab by controlled vocab ID.
	 * @param $controlledVocabId int
	 * @return ControlledVocab
	 */
	function getById($controlledVocabId) {
		$result = $this->retrieve(
			'SELECT * FROM controlled_vocabs WHERE controlled_vocab_id = ?', array((int) $controlledVocabId)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Fetch a controlled vocab by symbolic info, building it if needed.
	 * @param $symbolic string
	 * @param $assocType int
	 * @param $assocId int
	 * @return $controlledVocab
	 */
	function build($symbolic, $assocType = 0, $assocId = 0) {
		// Attempt to build a new controlled vocabulary.
		$controlledVocab = $this->newDataObject();
		$controlledVocab->setSymbolic($symbolic);
		$controlledVocab->setAssocType($assocType);
		$controlledVocab->setAssocId($assocId);
		$id = $this->insertObject($controlledVocab, false);
		if ($id !== null) return $controlledVocab;

		// Presume that an error was a duplicate insert.
		// In this case, try to fetch an existing controlled
		// vocabulary.
		return $this->getBySymbolic($symbolic, $assocType, $assocId);
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return ControlledVocabEntry
	 */
	function newDataObject() {
		return new ControlledVocab();
	}

	/**
	 * Internal function to return an ControlledVocab object from a row.
	 * @param $row array
	 * @return ControlledVocab
	 */
	function _fromRow($row) {
		$controlledVocab = $this->newDataObject();
		$controlledVocab->setId($row['controlled_vocab_id']);
		$controlledVocab->setAssocType($row['assoc_type']);
		$controlledVocab->setAssocId($row['assoc_id']);
		$controlledVocab->setSymbolic($row['symbolic']);

		return $controlledVocab;
	}

	/**
	 * Insert a new ControlledVocab.
	 * @param $controlledVocab ControlledVocab
	 * @return int? New insert ID on insert, or null on error
	 */
	function insertObject($controlledVocab, $dieOnError = true) {
		$success = $this->update(
			sprintf('INSERT INTO controlled_vocabs
				(symbolic, assoc_type, assoc_id)
				VALUES
				(?, ?, ?)'),
			array(
				$controlledVocab->getSymbolic(),
				(int) $controlledVocab->getAssocType(),
				(int) $controlledVocab->getAssocId()
			),
			true, // callHooks
			$dieOnError
		);
		if ($success) {
			$controlledVocab->setId($this->getInsertId());
			return $controlledVocab->getId();
		}
		else return null; // An error occurred on insert
	}

	/**
	 * Update an existing controlled vocab.
	 * @param $controlledVocab ControlledVocab
	 * @return boolean
	 */
	function updateObject(&$controlledVocab) {
		$returner = $this->update(
			sprintf('UPDATE	controlled_vocabs
				SET	symbolic = ?,
					assoc_type = ?,
					assoc_id = ?
				WHERE	controlled_vocab_id = ?'),
			array(
				$controlledVocab->getSymbolic(),
				(int) $controlledVocab->getAssocType(),
				(int) $controlledVocab->getAssocId(),
				(int) $controlledVocab->getId()
			)
		);
		return $returner;
	}

	/**
	 * Delete a controlled vocab.
	 * @param $controlledVocab ControlledVocab
	 * @return boolean
	 */
	function deleteObject($controlledVocab) {
		return $this->deleteObjectById($controlledVocab->getId());
	}

	/**
	 * Delete a controlled vocab by controlled vocab ID.
	 * @param $controlledVocabId int
	 * @return boolean
	 */
	function deleteObjectById($controlledVocabId) {
		$params = array((int) $controlledVocabId);
		$controlledVocabEntryDao = DAORegistry::getDAO('ControlledVocabEntryDAO');
		$controlledVocabEntries = $this->enumerate($controlledVocabId);
		foreach ($controlledVocabEntries as $controlledVocabEntryId => $controlledVocabEntryName) {
			$controlledVocabEntryDao->deleteObjectById($controlledVocabEntryId);
		}
		return $this->update('DELETE FROM controlled_vocabs WHERE controlled_vocab_id = ?', $params);
	}

	/**
	 * Retrieve an array of controlled vocabs matching the specified
	 * symbolic name and assoc info.
	 * @param $symbolic string
	 * @param $assocType int
	 * @param $assocId int
	 */
	function getBySymbolic($symbolic, $assocType = 0, $assocId = 0) {
		$result = $this->retrieve(
			'SELECT * FROM controlled_vocabs WHERE symbolic = ? AND assoc_type = ? AND assoc_id = ?',
			array($symbolic, (int) $assocType, (int) $assocId)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Get a list of controlled vocabulary options.
	 * @param $symbolic string
	 * @param $assocType int
	 * @param $assocId int
	 * @param $settingName string optional
	 * @return array $controlledVocabEntryId => $settingValue
	 */
	function enumerateBySymbolic($symbolic, $assocType, $assocId, $settingName = 'name') {
		$controlledVocab = $this->getBySymbolic($symbolic, $assocType, $assocId);
		if (!$controlledVocab) {
			$returner = array();
			return $returner;
		}
		return $controlledVocab->enumerate($settingName);
	}

	/**
	 * Get a list of controlled vocabulary options.
	 * @param $controlledVocabId int
	 * @param $settingName string optional
	 * @return array $controlledVocabEntryId => name
	 */
	function enumerate($controlledVocabId, $settingName = 'name') {
		$result = $this->retrieve(
			'SELECT	e.controlled_vocab_entry_id,
				COALESCE(l.setting_value, p.setting_value, n.setting_value) AS setting_value,
				COALESCE(l.setting_type, p.setting_type, n.setting_type) AS setting_type
			FROM	controlled_vocab_entries e
				LEFT JOIN controlled_vocab_entry_settings l ON (l.controlled_vocab_entry_id = e.controlled_vocab_entry_id AND l.setting_name = ? AND l.locale = ?)
				LEFT JOIN controlled_vocab_entry_settings p ON (p.controlled_vocab_entry_id = e.controlled_vocab_entry_id AND p.setting_name = ? AND p.locale = ?)
				LEFT JOIN controlled_vocab_entry_settings n ON (n.controlled_vocab_entry_id = e.controlled_vocab_entry_id AND n.setting_name = ? AND n.locale = ?)
			WHERE	e.controlled_vocab_id = ?
			ORDER BY e.seq',
			array(
				$settingName, AppLocale::getLocale(),		// Current locale
				$settingName, AppLocale::getPrimaryLocale(),	// Primary locale
				$settingName, '',				// No locale
				(int) $controlledVocabId
			)
		);

		$returner = array();
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[$row['controlled_vocab_entry_id']] = $this->convertFromDB(
				$row['setting_value'],
				$row['setting_type']
			);
			$result->MoveNext();
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Get the ID of the last inserted controlled vocab.
	 * @return int
	 */
	function getInsertId() {
		return parent::_getInsertId('controlled_vocabs', 'controlled_vocab_id');
	}

	/**
	 * Parse and install a controlled vocabulary from an XML file.
	 * @param $filename string Filename (including path) of XML file to install.
	 * @return array Array of parsed controlled vocabularies
	 */
	function installXML($filename) {
		$controlledVocabs = array();
		$controlledVocabEntryDao = $this->getEntryDAO();
		$controlledVocabEntrySettingsDao = $controlledVocabEntryDao->getSettingsDAO();
		$parser = new XMLParser();
		$tree = $parser->parse($filename);
		foreach ($tree->getChildren() as $controlledVocabNode) {
			assert($controlledVocabNode->getName() == 'controlled_vocab');

			// Try to fetch an existing controlled vocabulary
			$controlledVocab = $this->getBySymbolic(
				$symbolic = $controlledVocabNode->getAttribute('symbolic'),
				$assocType = (int) $controlledVocabNode->getAttribute('assoc-type'),
				$assocId = (int) $controlledVocabNode->getAttribute('assoc-id')
			);
			if ($controlledVocab) {
				$controlledVocabs[] = $controlledVocab;
				continue;
			}

			// It doesn't exist; create a new one.
			$controlledVocabs[] = $controlledVocab = $this->build($symbolic, $assocType, $assocId);
			foreach ($controlledVocabNode->getChildren() as $entryNode) {
				$seq = $entryNode->getAttribute('seq');
				if ($seq !== null) $seq = (float) $seq;

				$controlledVocabEntry = $controlledVocabEntryDao->newDataObject();
				$controlledVocabEntry->setControlledVocabId($controlledVocab->getId());
				$controlledVocabEntry->setSequence($seq);
				$controlledVocabEntryDao->insertObject($controlledVocabEntry);

				foreach ($entryNode->getChildren() as $settingNode) {
					$controlledVocabEntrySettingsDao->updateSetting(
						$controlledVocabEntry->getId(),
						$settingNode->getAttribute('name'),
						$settingNode->getValue(),
						$settingNode->getAttribute('type'),
						false // Not localized
					);
				}
			}
		}
		return $controlledVocabs;
	}
}

?>
