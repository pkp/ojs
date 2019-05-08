<?php

/**
 * @file classes/issue/IssueGalleyDAO.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueGalleyDAO
 * @ingroup issue_galley
 * @see IssueGalley
 *
 * @brief Operations for retrieving and modifying IssueGalley objects.
 */

import('classes.issue.IssueGalley');

class IssueGalleyDAO extends DAO {

	/**
	 * Retrieve a galley by ID.
	 * @param $galleyId int
	 * @param $issueId int optional
	 * @return IssueGalley
	 */
	function getById($galleyId, $issueId = null) {
		$params = array((int) $galleyId);
		if ($issueId !== null) $params[] = (int) $issueId;
		$result = $this->retrieve(
			'SELECT
				g.*,
				f.file_name,
				f.original_file_name,
				f.file_type,
				f.file_size,
				f.content_type,
				f.date_uploaded,
				f.date_modified
			FROM issue_galleys g
				LEFT JOIN issue_files f ON (g.file_id = f.file_id)
			WHERE g.galley_id = ?' .
			($issueId !== null?' AND g.issue_id = ?':''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		} else {
			HookRegistry::call('IssueGalleyDAO::getById', array(&$galleyId, &$issueId, &$returner));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Checks if public identifier exists (other than for the specified
	 * galley ID, which is treated as an exception).
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @param $pubId string
	 * @param $galleyId int An ID to be excluded from the search.
	 * @param $journalId int
	 * @return boolean
	 */
	function pubIdExists($pubIdType, $pubId, $galleyId, $journalId) {
		$result = $this->retrieve(
			'SELECT COUNT(*)
			FROM issue_galley_settings igs
				INNER JOIN issue_galleys ig ON igs.galley_id = ig.galley_id
				INNER JOIN issues i ON ig.issue_id = i.issue_id
			WHERE igs.setting_name = ? AND igs.setting_value = ? AND igs.galley_id <> ? AND i.journal_id = ?',
			array(
				'pub-id::'.$pubIdType,
				$pubId,
				(int) $galleyId,
				(int) $journalId
			)
		);
		$returner = $result->fields[0] ? true : false;
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve a galley by ID.
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @param $pubId string
	 * @param $issueId int
	 * @return IssueGalley
	 */
	function getByPubId($pubIdType, $pubId, $issueId) {
		$result = $this->retrieve(
			'SELECT
				g.*,
				f.file_name,
				f.original_file_name,
				f.file_type,
				f.file_size,
				f.content_type,
				f.date_uploaded,
				f.date_modified
			FROM issue_galleys g
				INNER JOIN issue_galley_settings gs ON g.galley_id = gs.galley_id
				LEFT JOIN issue_files f ON (g.file_id = f.file_id)
			WHERE	gs.setting_name = ? AND
				gs.setting_value = ? AND
				g.issue_id = ?',
			array('pub-id::'.$pubIdType, (string) $pubId, (int) $issueId)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		} else {
			HookRegistry::call('IssueGalleyDAO::getByPubId', array(&$pubIdType, &$pubId, &$issueId, &$returner));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve all galleys for an issue.
	 * @param $issueId int
	 * @return array IssueGalleys
	 */
	function getByIssueId($issueId) {
		$galleys = array();

		$result = $this->retrieve(
			'SELECT
				g.*,
				f.file_name,
				f.original_file_name,
				f.file_type,
				f.file_size,
				f.content_type,
				f.date_uploaded,
				f.date_modified
			FROM issue_galleys g
				LEFT JOIN issue_files f ON (g.file_id = f.file_id)
			WHERE g.issue_id = ? ORDER BY g.seq',
			(int) $issueId
		);

		while (!$result->EOF) {
			$issueGalley = $this->_fromRow($result->GetRowAssoc(false));
			$galleys[$issueGalley->getId()] = $issueGalley;
			$result->MoveNext();
		}

		$result->Close();
		HookRegistry::call('IssueGalleyDAO::getGalleysByIssue', array(&$galleys, &$issueId));
		return $galleys;
	}

	/**
	 * Retrieve issue galley by public galley id or, failing that,
	 * internal galley ID; public galley ID takes precedence.
	 * @param $galleyId string
	 * @param $issueId int
	 * @return ArticleGalley object
	 */
	function getByBestId($galleyId, $issueId) {
		if ($galleyId != '') $galley =& $this->getByPubId('publisher-id', $galleyId, $issueId);
		if (!isset($galley) && ctype_digit("$galleyId")) $galley = $this->getById((int) $galleyId, $issueId);
		return $galley;
	}

	/**
	 * Get the list of fields for which data is localized.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array();
	}

	/**
	 * Get a list of additional fields that do not have
	 * dedicated accessors.
	 * @return array
	 */
	function getAdditionalFieldNames() {
		$additionalFields = parent::getAdditionalFieldNames();
		// FIXME: Move this to a PID plug-in.
		$additionalFields[] = 'pub-id::publisher-id';
		return $additionalFields;
	}

	/**
	 * Update the localized fields for this galley.
	 * @param $galley
	 */
	function updateLocaleFields($galley) {
		$this->updateDataObjectSettings('issue_galley_settings', $galley, array(
			'galley_id' => $galley->getId()
		));
	}

	/**
	 * Construct a new issue galley.
	 * @return IssueGalley
	 */
	function newDataObject() {
		return new IssueGalley();
	}

	/**
	 * Internal function to return an IssueGalley object from a row.
	 * @param $row array
	 * @return IssueGalley
	 */
	function _fromRow($row) {
		$galley = $this->newDataObject();

		$galley->setId($row['galley_id']);
		$galley->setIssueId($row['issue_id']);
		$galley->setLocale($row['locale']);
		$galley->setFileId($row['file_id']);
		$galley->setLabel($row['label']);
		$galley->setSequence($row['seq']);

		// IssueFile set methods
		$galley->setServerFileName($row['file_name']);
		$galley->setOriginalFileName($row['original_file_name']);
		$galley->setFileType($row['file_type']);
		$galley->setFileSize($row['file_size']);
		$galley->setContentType($row['content_type']);
		$galley->setDateModified($this->datetimeFromDB($row['date_modified']));
		$galley->setDateUploaded($this->datetimeFromDB($row['date_uploaded']));

		$this->getDataObjectSettings('issue_galley_settings', 'galley_id', $row['galley_id'], $galley);

		HookRegistry::call('IssueGalleyDAO::_fromRow', array(&$galley, &$row));

		return $galley;
	}

	/**
	 * Insert a new IssueGalley.
	 * @param $galley IssueGalley
	 */
	function insertObject($galley) {
		$this->update(
			'INSERT INTO issue_galleys
				(issue_id,
				file_id,
				label,
				locale,
				seq)
				VALUES
				(?, ?, ?, ?, ?)',
			array(
				(int) $galley->getIssueId(),
				(int) $galley->getFileId(),
				$galley->getLabel(),
				$galley->getLocale(),
				$galley->getSequence() == null ? $this->getNextGalleySequence($galley->getIssueId()) : $galley->getSequence()
			)
		);
		$galley->setId($this->getInsertId());
		$this->updateLocaleFields($galley);

		HookRegistry::call('IssueGalleyDAO::insertObject', array(&$galley, $galley->getId()));

		return $galley->getId();
	}

	/**
	 * Update an existing IssueGalley.
	 * @param $galley IssueGalley
	 */
	function updateObject($galley) {
		$this->update(
			'UPDATE issue_galleys
				SET
					file_id = ?,
					label = ?,
					locale = ?,
					seq = ?
				WHERE galley_id = ?',
			array(
				(int) $galley->getFileId(),
				$galley->getLabel(),
				$galley->getLocale(),
				$galley->getSequence(),
				(int) $galley->getId()
			)
		);
		$this->updateLocaleFields($galley);
	}

	/**
	 * Delete an IssueGalley.
	 * @param $galley IssueGalley
	 */
	function deleteObject($galley) {
		return $this->deleteById($galley->getId(), $galley->getIssueId());
	}

	/**
	 * Delete a galley by ID.
	 * @param $galleyId int
	 * @param $issueId int optional
	 */
	function deleteById($galleyId, $issueId = null) {
		HookRegistry::call('IssueGalleyDAO::deleteById', array(&$galleyId, &$issueId));

		if (isset($issueId)) {
			$this->update(
				'DELETE FROM issue_galleys WHERE galley_id = ? AND issue_id = ?',
				array((int) $galleyId, (int) $issueId)
			);
		} else {
			$this->update(
				'DELETE FROM issue_galleys WHERE galley_id = ?', (int) $galleyId
			);
		}
		if ($this->getAffectedRows()) {
			$this->update('DELETE FROM issue_galley_settings WHERE galley_id = ?', array((int) $galleyId));
		}
	}

	/**
	 * Delete galleys by issue.
	 * NOTE that this will not delete issue_file entities or the respective files.
	 * @param $issueId int
	 */
	function deleteByIssueId($issueId) {
		$galleys = $this->getByIssueId($issueId);
		foreach ($galleys as $galley) {
			$this->deleteById($galley->getId(), $issueId);
		}
	}

	/**
	 * Sequentially renumber galleys for an issue in their sequence order.
	 * @param $issueId int
	 */
	function resequence($issueId) {
		$result = $this->retrieve(
			'SELECT galley_id FROM issue_galleys WHERE issue_id = ? ORDER BY seq',
			(int) $issueId
		);

		for ($i=1; !$result->EOF; $i++) {
			list($galleyId) = $result->fields;
			$this->update(
				'UPDATE issue_galleys SET seq = ? WHERE galley_id = ?',
				array($i, $galleyId)
			);
			$result->MoveNext();
		}

		$result->Close();
	}

	/**
	 * Get the the next sequence number for an issue's galleys (i.e., current max + 1).
	 * @param $issueId int
	 * @return int
	 */
	function getNextGalleySequence($issueId) {
		$result = $this->retrieve(
			'SELECT MAX(seq) + 1 FROM issue_galleys WHERE issue_id = ?',
			(int) $issueId
		);
		$returner = floor($result->fields[0]);
		$result->Close();
		return $returner;
	}

	/**
	 * Get the ID of the last inserted gallery.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('issue_galleys', 'galley_id');
	}
}


