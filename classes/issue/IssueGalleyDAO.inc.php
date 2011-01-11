<?php

/**
 * @file classes/issue/IssueGalleyDAO.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueGalleyDAO
 * @ingroup issue
 * @see IssueGalley
 *
 * @brief Operations for retrieving and modifying IssueGalley objects.
 */

import('classes.issue.IssueGalley');

class IssueGalleyDAO extends DAO {

	/**
	 * Constructor.
	 */
	function IssueGalleyDAO() {
		parent::DAO();
	}

	/**
	 * Retrieve a galley by ID.
	 * @param $galleyId int
	 * @param $issueId int optional
	 * @return IssueGalley
	 */
	function &getGalley($galleyId, $issueId = null) {
		$params = array($galleyId);
		if ($issueId !== null) $params[] = (int) $issueId;
		$result =& $this->retrieve(
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
			$returner =& $this->_returnGalleyFromRow($result->GetRowAssoc(false));
		} else {
			HookRegistry::call('IssueGalleyDAO::getGalley', array(&$galleyId, &$issueId, &$returner));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Checks if public identifier exists (other than for the specified
	 * galley ID, which is treated as an exception)
	 * @param $publicGalleyId string
	 * @param $galleyId int
	 * @param $issueId int
	 * @return boolean
	 */
	function publicGalleyIdExists($publicGalleyId, $galleyId, $issueId) {
		$result =& $this->retrieve(
			'SELECT
				COUNT(*)
			FROM issue_galleys
			WHERE
				public_galley_id = ? AND
				galley_id <> ? AND
				issue_id = ?',
			array(
				$publicGalleyId,
				(int) $galleyId,
				(int) $issueId
			)
		);
		$returner = $result->fields[0] ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve a galley by public galley ID.
	 * @param $publicGalleyId string
	 * @param $issueId int optional
	 * @return IssueGalley
	 */
	function &getGalleyByPublicGalleyId($publicGalleyId, $issueId) {
		$result =& $this->retrieve(
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
			WHERE
				g.public_galley_id = ? AND
				g.issue_id = ?',
			array($publicGalleyId, (int) $issueId)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnGalleyFromRow($result->GetRowAssoc(false));
		} else {
			HookRegistry::call('IssueGalleyDAO::getGalleyByPublicGalleyId', array(&$galleyId, &$issueId, &$returner));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve all galleys for an issue.
	 * @param $issueId int
	 * @return array IssueGalleys
	 */
	function &getGalleysByIssue($issueId) {
		$galleys = array();

		$result =& $this->retrieve(
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
			$issueId
		);

		while (!$result->EOF) {
			$galleys[] =& $this->_returnGalleyFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		HookRegistry::call('IssueGalleyDAO::getGalleysByIssue', array(&$galleys, &$issueId));

		return $galleys;
	}

	/**
	 * Retrieve issue galley by public galley id or, failing that,
	 * internal galley ID; public galley ID takes precedence.
	 * @param $galleyId string
	 * @param $issueId int
	 * @return galley object
	 */
	function &getGalleyByBestGalleyId($galleyId, $issueId) {
		if ($galleyId != '') $galley =& $this->getGalleyByPublicGalleyId($galleyId, $issueId);
		if (!isset($galley)) $galley =& $this->getGalley((int) $galleyId, $issueId);
		return $galley;
	}

	/**
	 * Internal function to return an IssueGalley object from a row.
	 * @param $row array
	 * @return IssueGalley
	 */
	function &_returnGalleyFromRow(&$row) {
		$galley = new IssueGalley();

		$galley->setId($row['galley_id']);
		$galley->setPublicGalleyId($row['public_galley_id']);
		$galley->setIssueId($row['issue_id']);
		$galley->setLocale($row['locale']);
		$galley->setFileId($row['file_id']);
		$galley->setLabel($row['label']);
		$galley->setSequence($row['seq']);
		$galley->setViews($row['views']);

		// IssueFile set methods
		$galley->setFileName($row['file_name']);
		$galley->setOriginalFileName($row['original_file_name']);
		$galley->setFileType($row['file_type']);
		$galley->setFileSize($row['file_size']);
		$galley->setContentType($row['content_type']);
		$galley->setDateModified($this->datetimeFromDB($row['date_modified']));
		$galley->setDateUploaded($this->datetimeFromDB($row['date_uploaded']));

		HookRegistry::call('IssueGalleyDAO::_returnGalleyFromRow', array(&$galley, &$row));

		return $galley;
	}

	/**
	 * Insert a new IssueGalley.
	 * @param $galley IssueGalley
	 */
	function insertGalley(&$galley) {
		$this->update(
			'INSERT INTO issue_galleys
				(public_galley_id,
				issue_id,
				file_id,
				label,
				locale,
				seq)
				VALUES
				(?, ?, ?, ?, ?, ?)',
			array(
				$galley->getPublicGalleyId(),
				$galley->getIssueId(),
				$galley->getFileId(),
				$galley->getLabel(),
				$galley->getLocale(),
				$galley->getSequence() == null ? $this->getNextGalleySequence($galley->getIssueId()) : $galley->getSequence()
			)
		);
		$galley->setId($this->getInsertGalleyId());

		HookRegistry::call('IssueGalleyDAO::insertGalley', array(&$galley, $galley->getId()));

		return $galley->getId();
	}

	/**
	 * Update an existing IssueGalley.
	 * @param $galley IssueGalley
	 */
	function updateGalley(&$galley) {
		return $this->update(
			'UPDATE issue_galleys
				SET
					public_galley_id = ?,
					file_id = ?,
					label = ?,
					locale = ?,
					seq = ?
				WHERE galley_id = ?',
			array(
				$galley->getPublicGalleyId(),
				$galley->getFileId(),
				$galley->getLabel(),
				$galley->getLocale(),
				$galley->getSequence(),
				$galley->getId()
			)
		);
	}

	/**
	 * Delete an IssueGalley.
	 * @param $galley IssueGalley
	 */
	function deleteGalley(&$galley) {
		return $this->deleteGalleyById($galley->getId(), $galley->getIssueId());
	}

	/**
	 * Delete a galley by ID.
	 * @param $galleyId int
	 * @param $issueId int optional
	 */
	function deleteGalleyById($galleyId, $issueId = null) {
		HookRegistry::call('IssueGalleyDAO::deleteGalleyById', array(&$galleyId, &$issueId));

		if (isset($issueId)) {
			return $this->update(
				'DELETE FROM issue_galleys WHERE galley_id = ? AND issue_id = ?',
				array($galleyId, $issueId)
			);
		} else {
			return $this->update(
				'DELETE FROM issue_galleys WHERE galley_id = ?', $galleyId
			);
		}

	}

	/**
	 * Delete galleys by issue.
	 * NOTE that this will not delete issue_file entities or the respective files.
	 * @param $issueId int
	 */
	function deleteGalleysByIssue($issueId) {
		$galleys =& $this->getGalleysByIssue($issueId);
		foreach ($galleys as $galley) {
			$this->deleteGalleyById($galley->getId(), $issueId);
		}
	}

	/**
	 * Check if a galley exists with the associated file ID.
	 * @param $issueId int
	 * @param $fileId int
	 * @return boolean
	 */
	function galleyExistsByFileId($issueId, $fileId) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM issue_galleys
			WHERE issue_id = ? AND file_id = ?',
			array($issueId, $fileId)
		);

		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Increment the views count for a galley.
	 * @param $galleyId int
	 */
	function incrementViews($galleyId) {
		if ( !HookRegistry::call('IssueGalleyDAO::incrementViews', array(&$galleyId)) ) {
			return $this->update(
				'UPDATE issue_galleys SET views = views + 1 WHERE galley_id = ?',
				$galleyId
			);
		} else return false;
	}

	/**
	 * Sequentially renumber galleys for an issue in their sequence order.
	 * @param $issueId int
	 */
	function resequenceGalleys($issueId) {
		$result =& $this->retrieve(
			'SELECT galley_id FROM issue_galleys WHERE issue_id = ? ORDER BY seq',
			$issueId
		);

		for ($i=1; !$result->EOF; $i++) {
			list($galleyId) = $result->fields;
			$this->update(
				'UPDATE issue_galleys SET seq = ? WHERE galley_id = ?',
				array($i, $galleyId)
			);
			$result->moveNext();
		}

		$result->close();
		unset($result);
	}

	/**
	 * Get the the next sequence number for an issue's galleys (i.e., current max + 1).
	 * @param $issueId int
	 * @return int
	 */
	function getNextGalleySequence($issueId) {
		$result =& $this->retrieve(
			'SELECT MAX(seq) + 1 FROM issue_galleys WHERE issue_id = ?',
			$issueId
		);
		$returner = floor($result->fields[0]);

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get the ID of the last inserted gallery.
	 * @return int
	 */
	function getInsertGalleyId() {
		return $this->getInsertId('issue_galleys', 'galley_id');
	}
}

?>
