<?php

/**
 * @file classes/submission/GenreDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GenreDAO
 * @ingroup submission
 * @see Genre
 *
 * @brief Operations for retrieving and modifying Genre objects.
 */

import('lib.pkp.classes.submission.Genre');
import('lib.pkp.classes.db.DAO');

class GenreDAO extends DAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Retrieve a genre by type id.
	 * @param $genreId int
	 * @return Genre
	 */
	function getById($genreId, $contextId = null) {
		$params = array((int) $genreId);
		if ($contextId) $params[] = (int) $contextId;

		$result = $this->retrieve(
			'SELECT * FROM genres WHERE genre_id = ?' .
			($contextId ? ' AND context_id = ?' : '') .
			' ORDER BY seq',
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
	 * Retrieve all genres
	 * @param $contextId int
	 * @param $enabledOnly boolean optional
	 * @param $rangeInfo object optional
	 * @return DAOResultFactory containing matching genres
	 */
	function getEnabledByContextId($contextId, $rangeInfo = null) {
		$params = array(1, (int) $contextId);

		$result = $this->retrieveRange(
			'SELECT * FROM genres
			WHERE	enabled = ? AND context_id = ?
			ORDER BY seq',
			$params, $rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow', array('id'));
	}

	/**
	 * Retrieve genres based on whether they are dependent or not.
	 * @param $dependentFilesOnly boolean
	 * @param $contextId int
	 * @param $rangeInfo object optional
	 * @return DAOResultFactory containing matching genres
	 */
	function getByDependenceAndContextId($dependentFilesOnly, $contextId, $rangeInfo = null) {
		$result = $this->retrieveRange(
			'SELECT * FROM genres
			WHERE enabled = ? AND context_id = ? AND dependent = ?
			ORDER BY seq',
			array(1, (int) $contextId, (int) $dependentFilesOnly),
			$rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow', array('id'));
	}

	/**
	 * Retrieve all genres
	 * @param $contextId int
	 * @param $rangeInfo object optional
	 * @return DAOResultFactory containing matching genres
	 */
	function getByContextId($contextId, $rangeInfo = null) {
		$result = $this->retrieveRange(
			'SELECT * FROM genres WHERE context_id = ? ORDER BY seq',
			array((int) $contextId),
			$rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow', array('id'));
	}

	/**
	 * Retrieves the genre associated with a key.
	 * @param $key String the entry key
	 * @param $contextId int Optional context ID
	 * @return Genre
	 */
	function getByKey($key, $contextId = null) {
		$params = array($key);
		if ($contextId) $params[] = (int) $contextId;

		$sql = 'SELECT * FROM genres WHERE entry_key = ? ' .
		($contextId ? ' AND context_id = ?' : '');

		$result = $this->retrieve($sql, $params);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Get a list of field names for which data is localized.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('name');
	}

	/**
	 * Update the settings for this object
	 * @param $genre object
	 */
	function updateLocaleFields($genre) {
		$this->updateDataObjectSettings(
			'genre_settings', $genre,
			array('genre_id' => $genre->getId())
		);
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return Genre
	 */
	function newDataObject() {
		return new Genre();
	}

	/**
	 * Internal function to return a Genre object from a row.
	 * @param $row array
	 * @return Genre
	 */
	function _fromRow($row) {
		$genre = $this->newDataObject();
		$genre->setId($row['genre_id']);
		$genre->getKey($row['entry_key']);
		$genre->setContextId($row['context_id']);
		$genre->setSortable($row['sortable']);
		$genre->setCategory($row['category']);
		$genre->setDependent($row['dependent']);
		$genre->setSupplementary($row['supplementary']);
		$genre->setSequence($row['seq']);
		$genre->setEnabled($row['enabled']);

		$this->getDataObjectSettings('genre_settings', 'genre_id', $row['genre_id'], $genre);

		HookRegistry::call('GenreDAO::_fromRow', array(&$genre, &$row));

		return $genre;
	}

	/**
	 * Insert a new genre.
	 * @param $genre Genre
	 * @return int Inserted genre ID
	 */
	function insertObject($genre) {
		$this->update(
			'INSERT INTO genres
				(entry_key, seq, sortable, context_id, category, dependent, supplementary)
			VALUES
				(?, ?, ?, ?, ?, ?, ?)',
			array(
				$genre->getKey(),
				(float) $genre->getSequence(),
				$genre->getSortable() ? 1 : 0,
				(int) $genre->getContextId(),
				(int) $genre->getCategory(),
				$genre->getDependent() ? 1 : 0,
				$genre->getSupplementary() ? 1 : 0,
			)
		);

		$genre->setId($this->getInsertId());
		$this->updateLocaleFields($genre);
		return $genre->getId();
	}

	/**
	 * Update an existing genre.
	 * @param $genre Genre
	 */
	function updateObject($genre) {
		$this->update(
			'UPDATE genres
			SET	entry_key = ?,
				seq = ?,
				sortable = ?,
				dependent = ?,
				supplementary = ?,
				enabled = ?,
				category = ?
			WHERE	genre_id = ?',
			array(
				$genre->getKey(),
				(float) $genre->getSequence(),
				$genre->getSortable() ? 1 : 0,
				$genre->getDependent() ? 1 : 0,
				$genre->getSupplementary() ? 1 : 0,
				$genre->getEnabled() ? 1 : 0,
				$genre->getCategory(),
				(int) $genre->getId(),
			)
		);
		$this->updateLocaleFields($genre);
	}

	/**
	 * Delete a genre by id.
	 * @param $genre Genre
	 */
	function deleteObject($genre) {
		return $this->deleteById($genre->getId());
	}

	/**
	 * Soft delete a genre by id.
	 * @param $genreId int Genre ID
	 */
	function deleteById($genreId) {
		return $this->update(
			'UPDATE genres SET enabled = ? WHERE genre_id = ?',
			array(0, (int) $genreId)
		);
	}

	/**
	 * Delete the genre entries associated with a context.
	 * Called when deleting a Context in ContextDAO.
	 * @param $contextId int Context ID
	 */
	function deleteByContextId($contextId) {
		$genres = $this->getByContextId($contextId);
		while ($genre = $genres->next()) {
			$this->update('DELETE FROM genre_settings WHERE genre_id = ?', (int) $genre->getId());
		}
		$this->update(
			'DELETE FROM genres WHERE context_id = ?', (int) $contextId
		);
	}

	/**
	 * Get the ID of the last inserted genre.
	 * @return int Inserted genre ID
	 */
	function getInsertId() {
		return $this->_getInsertId('genres', 'genre_id');
	}

	/**
	 * Install default data for settings.
	 * @param $contextId int Context ID
	 * @param $locales array List of locale codes
	 */
	function installDefaults($contextId, $locales) {
		// Load all the necessary locales.
		foreach ($locales as $locale) AppLocale::requireComponents(LOCALE_COMPONENT_APP_DEFAULT, LOCALE_COMPONENT_PKP_DEFAULT, $locale);

		$xmlDao = new XMLDAO();
		$data = $xmlDao->parseStruct('registry/genres.xml', array('genre'));
		if (!isset($data['genre'])) return false;
		$seq = 0;

		foreach ($data['genre'] as $entry) {
			$attrs = $entry['attributes'];
			// attempt to retrieve an installed Genre with this key.
			// Do this to preserve the genreId.
			$genre = $this->getByKey($attrs['key'], $contextId);
			if (!$genre) $genre = $this->newDataObject();
			$genre->setContextId($contextId);
			$genre->setKey($attrs['key']);
			$genre->setSortable($attrs['sortable']);
			$genre->setCategory($attrs['category']);
			$genre->setDependent($attrs['dependent']);
			$genre->setSupplementary($attrs['supplementary']);
			$genre->setSequence($seq++);
			foreach ($locales as $locale) {
				$genre->setName(__($attrs['localeKey'], array(), $locale), $locale);
			}

			if ($genre->getId() > 0) { // existing genre.
				$this->updateObject($genre);
			} else {
				$this->insertObject($genre);
			}
		}
	}

	/**
	 * Remove all settings associated with a locale
	 * @param $locale string Locale code
	 */
	function deleteSettingsByLocale($locale) {
		$this->update('DELETE FROM genre_settings WHERE locale = ?', $locale);
	}
}

?>
