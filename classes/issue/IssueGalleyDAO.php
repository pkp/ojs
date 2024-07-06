<?php

/**
 * @file classes/issue/IssueGalleyDAO.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueGalleyDAO
 *
 * @see IssueGalley
 *
 * @brief Operations for retrieving and modifying IssueGalley objects.
 */

namespace APP\issue;

use APP\file\IssueFileManager;
use PKP\plugins\Hook;

class IssueGalleyDAO extends \PKP\db\DAO
{
    /**
     * Retrieve a galley by ID.
     *
     * @hook IssueGalleyDAO::getById [[&$galleyId, &$issueId, &$returner]]
     */
    public function getById(int $galleyId, ?int $issueId = null): ?IssueGalley
    {
        $params = [$galleyId];
        if ($issueId !== null) {
            $params[] = $issueId;
        }
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
            ($issueId !== null ? ' AND g.issue_id = ?' : ''),
            $params
        );

        $returner = null;
        if ($row = $result->current()) {
            $returner = $this->_fromRow((array) $row);
        } else {
            Hook::call('IssueGalleyDAO::getById', [&$galleyId, &$issueId, &$returner]);
        }
        return $returner;
    }

    /**
     * Checks if public identifier exists (other than for the specified
     * galley ID, which is treated as an exception).
     *
     * @param string $pubIdType One of the NLM pub-id-type values or
     * 'other::something' if not part of the official NLM list
     * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
     */
    public function pubIdExists(string $pubIdType, string $pubId, int $excludeGalleyId, int $journalId): bool
    {
        $result = $this->retrieve(
            'SELECT COUNT(*) AS row_count
			FROM issue_galley_settings igs
				INNER JOIN issue_galleys ig ON igs.galley_id = ig.galley_id
				INNER JOIN issues i ON ig.issue_id = i.issue_id
			WHERE igs.setting_name = ? AND igs.setting_value = ? AND igs.galley_id <> ? AND i.journal_id = ?',
            [
                'pub-id::' . $pubIdType,
                $pubId,
                $excludeGalleyId,
                $journalId
            ]
        );
        $row = $result->current();
        return $row ? (bool) $row->row_count : false;
    }

    /**
     * Retrieve a galley by ID.
     *
     * @param string $pubIdType One of the NLM pub-id-type values or
     * 'other::something' if not part of the official NLM list
     * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
     *
     * @hook IssueGalleyDAO::getByPubId [[&$pubIdType, &$pubId, &$issueId, &$returner]]
     */
    public function getByPubId(string $pubIdType, string $pubId, int $issueId): ?IssueGalley
    {
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
            ['pub-id::' . $pubIdType, (string) $pubId, (int) $issueId]
        );
        $row = $result->current();
        $returner = null;
        if ($row) {
            $returner = $this->_fromRow((array) $row);
        } else {
            Hook::call('IssueGalleyDAO::getByPubId', [&$pubIdType, &$pubId, &$issueId, &$returner]);
        }
        return $returner;
    }

    /**
     * Retrieve an associative array of galleys for an issue by issue ID.
     *
     * @hook IssueGalleyDAO::getGalleysByIssue [[&$galleys, &$issueId]]
     */
    public function getByIssueId(int $issueId): array
    {
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
            [$issueId]
        );

        $galleys = [];
        foreach ($result as $row) {
            $issueGalley = $this->_fromRow((array) $row);
            $galleys[$issueGalley->getId()] = $issueGalley;
        }
        Hook::call('IssueGalleyDAO::getGalleysByIssue', [&$galleys, &$issueId]);
        return $galleys;
    }

    /**
     * Retrieve issue galley by urlPath or, failing that,
     * internal galley ID; urlPath takes precedence.
     *
     * @return IssueGalley object
     */
    public function getByBestId(int|string $galleyId, int $issueId)
    {
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
			WHERE	g.url_path = ? AND
				g.issue_id = ?',
            [$galleyId, $issueId]
        );
        if ($row = $result->current()) {
            return $this->_fromRow((array) $row);
        } elseif (($galleyId = filter_var($galleyId, FILTER_VALIDATE_INT)) !== false) {
            return $this->getById($galleyId, $issueId);
        }
        return null;
    }

    /**
     * Get the list of fields for which data is localized.
     */
    public function getLocaleFieldNames(): array
    {
        return [];
    }

    /**
     * Get a list of additional fields that do not have
     * dedicated accessors.
     */
    public function getAdditionalFieldNames(): array
    {
        $additionalFields = parent::getAdditionalFieldNames();
        // FIXME: Move this to a PID plug-in.
        $additionalFields[] = 'pub-id::publisher-id';
        return $additionalFields;
    }

    /**
     * Update the localized fields for this galley.
     */
    public function updateLocaleFields(IssueGalley $galley): void
    {
        $this->updateDataObjectSettings('issue_galley_settings', $galley, [
            'galley_id' => $galley->getId()
        ]);
    }

    /**
     * Construct a new issue galley.
     */
    public function newDataObject(): IssueGalley
    {
        return new IssueGalley();
    }

    /**
     * Internal function to return an IssueGalley object from a row.
     *
     * @hook IssueGalleyDAO::_fromRow [[&$galley, &$row]]
     */
    public function _fromRow(array $row): IssueGalley
    {
        $galley = $this->newDataObject();

        $galley->setId($row['galley_id']);
        $galley->setIssueId($row['issue_id']);
        $galley->setLocale($row['locale']);
        $galley->setFileId($row['file_id']);
        $galley->setLabel($row['label']);
        $galley->setSequence($row['seq']);
        $galley->setData('urlPath', $row['url_path']);

        // IssueFile set methods
        $galley->setServerFileName($row['file_name']);
        $galley->setOriginalFileName($row['original_file_name']);
        $galley->setFileType($row['file_type']);
        $galley->setFileSize($row['file_size']);
        $galley->setContentType($row['content_type']);
        $galley->setDateModified($this->datetimeFromDB($row['date_modified']));
        $galley->setDateUploaded($this->datetimeFromDB($row['date_uploaded']));

        $this->getDataObjectSettings('issue_galley_settings', 'galley_id', $row['galley_id'], $galley);

        Hook::call('IssueGalleyDAO::_fromRow', [&$galley, &$row]);

        return $galley;
    }

    /**
     * Insert a new IssueGalley.
     *
     * @hook IssueGalleyDAO::insertObject [[&$galley, $galley->getId()]]
     */
    public function insertObject(IssueGalley $galley): int
    {
        $this->update(
            'INSERT INTO issue_galleys
				(issue_id,
				file_id,
				label,
				locale,
				seq,
				url_path)
				VALUES
				(?, ?, ?, ?, ?, ?)',
            [
                $galley->getIssueId(),
                $galley->getFileId(),
                $galley->getLabel(),
                $galley->getLocale(),
                $galley->getSequence() == null ? $this->getNextGalleySequence($galley->getIssueId()) : $galley->getSequence(),
                $galley->getData('urlPath'),
            ]
        );
        $galley->setId($this->getInsertId());
        $this->updateLocaleFields($galley);

        Hook::call('IssueGalleyDAO::insertObject', [&$galley, $galley->getId()]);

        return $galley->getId();
    }

    /**
     * Update an existing IssueGalley.
     */
    public function updateObject(IssueGalley $galley): void
    {
        $this->update(
            'UPDATE issue_galleys
				SET
					file_id = ?,
					label = ?,
					locale = ?,
					seq = ?,
					url_path = ?
				WHERE galley_id = ?',
            [
                $galley->getFileId(),
                $galley->getLabel(),
                $galley->getLocale(),
                $galley->getSequence(),
                $galley->getData('urlPath'),
                $galley->getId()
            ]
        );
        $this->updateLocaleFields($galley);
    }

    /**
     * Delete an IssueGalley.
     */
    public function deleteObject(IssueGalley $galley): void
    {
        $this->deleteById($galley->getId(), $galley->getIssueId());
    }

    /**
     * Delete a galley by ID.
     *
     * @hook IssueGalleyDAO::deleteById [[&$galleyId, &$issueId]]
     */
    public function deleteById(int $galleyId, ?int $issueId = null): int
    {
        Hook::call('IssueGalleyDAO::deleteById', [&$galleyId, &$issueId]);

        if ($issueId !== null) {
            // Delete the file
            $issueGalley = $this->getById($galleyId);
            $issueFileManager = new IssueFileManager($issueId);
            $issueFileManager->deleteById($issueGalley->getFileId());

            $affectedRows = $this->update(
                'DELETE FROM issue_galleys WHERE galley_id = ? AND issue_id = ?',
                [$galleyId, $issueId]
            );
        } else {
            $affectedRows = $this->update(
                'DELETE FROM issue_galleys WHERE galley_id = ?',
                [$galleyId]
            );
        }
        if ($affectedRows) {
            $this->update('DELETE FROM issue_galley_settings WHERE galley_id = ?', [$galleyId]);
        }
        return $affectedRows;
    }

    /**
     * Delete galleys by issue.
     * NOTE that this will not delete issue_file entities or the respective files.
     *
     */
    public function deleteByIssueId(int $issueId): void
    {
        $galleys = $this->getByIssueId($issueId);
        foreach ($galleys as $galley) {
            $this->deleteById($galley->getId(), $issueId);
        }
    }

    /**
     * Sequentially renumber galleys for an issue in their sequence order.
     */
    public function resequence(int $issueId): void
    {
        $result = $this->retrieve('SELECT galley_id FROM issue_galleys WHERE issue_id = ? ORDER BY seq', [$issueId]);
        for ($i = 1; $row = $result->current(); $i++) {
            $this->update('UPDATE issue_galleys SET seq = ? WHERE galley_id = ?', [$i, $row->galley_id]);
            $result->next();
        }
    }

    /**
     * Get the the next sequence number for an issue's galleys (i.e., current max + 1).
     */
    public function getNextGalleySequence(int $issueId): int
    {
        $result = $this->retrieve('SELECT COALESCE(MAX(seq), 0) + 1 AS next_sequence FROM issue_galleys WHERE issue_id = ?', [$issueId]);
        $row = $result->current();
        return $row->next_sequence;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\issue\IssueGalleyDAO', '\IssueGalleyDAO');
}
