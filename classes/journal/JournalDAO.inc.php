<?php

/**
 * @file classes/journal/JournalDAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class JournalDAO
 * @ingroup journal
 *
 * @see Journal
 *
 * @brief Operations for retrieving and modifying Journal objects.
 */

namespace APP\journal;

use APP\core\Application;
use APP\facades\Repo;
use PKP\context\ContextDAO;
use PKP\db\DAORegistry;
use PKP\metadata\MetadataTypeDescription;

class JournalDAO extends ContextDAO
{
    /** @copydoc SchemaDAO::$schemaName */
    public $schemaName = 'context';

    /** @copydoc SchemaDAO::$tableName */
    public $tableName = 'journals';

    /** @copydoc SchemaDAO::$settingsTableName */
    public $settingsTableName = 'journal_settings';

    /** @copydoc SchemaDAO::$primaryKeyColumn */
    public $primaryKeyColumn = 'journal_id';

    /** @var array Maps schema properties for the primary table to their column names */
    public $primaryTableColumns = [
        'id' => 'journal_id',
        'urlPath' => 'path',
        'enabled' => 'enabled',
        'seq' => 'seq',
        'primaryLocale' => 'primary_locale',
        'currentIssueId' => 'current_issue_id'
    ];

    /**
     * Create a new DataObject of the appropriate class
     *
     * @return \PKP\core\DataObject
     */
    public function newDataObject()
    {
        return new Journal();
    }

    /**
     * Retrieve the IDs and titles of all journals in an associative array.
     *
     * @return array
     */
    public function getTitles($enabledOnly = false)
    {
        $journals = [];
        $journalIterator = $this->getAll($enabledOnly);
        while ($journal = $journalIterator->next()) {
            $journals[$journal->getId()] = $journal->getLocalizedName();
        }
        return $journals;
    }

    /**
     * Delete the public IDs of all publishing objects in a journal.
     *
     * @param int $journalId
     * @param string $pubIdType One of the NLM pub-id-type values or
     * 'other::something' if not part of the official NLM list
     * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
     */
    public function deleteAllPubIds($journalId, $pubIdType)
    {
        Repo::galley()->dao->deleteAllPubIds($journalId, $pubIdType);
        Repo::submissionFile()->dao->deleteAllPubIds($journalId, $pubIdType);
        Repo::issue()->dao->deleteAllPubIds($journalId, $pubIdType);
        Repo::publication()->dao->deleteAllPubIds($journalId, $pubIdType);
    }

    /**
     * Check whether the given public ID exists for any publishing
     * object in a journal.
     *
     * @param int $journalId
     * @param string $pubIdType One of the NLM pub-id-type values or
     * 'other::something' if not part of the official NLM list
     * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
     * @param string $pubId
     * @param int $assocType The object type of an object to be excluded from
     *  the search. Identified by one of the ASSOC_TYPE_* constants.
     * @param int $assocId The id of an object to be excluded from the search.
     * @param bool $forSameType Whether only the same objects should be considered.
     *
     * @return bool
     */
    public function anyPubIdExists(
        $journalId,
        $pubIdType,
        $pubId,
        $assocType = MetadataTypeDescription::ASSOC_TYPE_ANY,
        $assocId = 0,
        $forSameType = false
    ) {
        $pubObjectDaos = [
            ASSOC_TYPE_ISSUE => Repo::issue()->dao,
            ASSOC_TYPE_PUBLICATION => Repo::publication()->dao,
            ASSOC_TYPE_GALLEY => Application::getRepresentationDAO(),
            ASSOC_TYPE_ISSUE_GALLEY => DAORegistry::getDAO('IssueGalleyDAO'),
            ASSOC_TYPE_SUBMISSION_FILE => Repo::submissionFile()->dao,
        ];
        if ($forSameType) {
            $dao = $pubObjectDaos[$assocType];
            $excludedId = $assocId;
            if ($dao->pubIdExists($pubIdType, $pubId, $excludedId, $journalId)) {
                return true;
            }
            return false;
        }
        foreach ($pubObjectDaos as $daoAssocType => $dao) {
            if ($assocType == $daoAssocType) {
                $excludedId = $assocId;
            } else {
                $excludedId = 0;
            }
            if ($dao->pubIdExists($pubIdType, $pubId, $excludedId, $journalId)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Sets current_issue_id for context to null.
     * This is necessary because current_issue_id should explicitly be set to null rather than unset.
     *
     * @param int $contextId
     *
     * @return int
     */
    public function removeCurrentIssue($contextId)
    {
        return $this->update(
            "UPDATE {$this->tableName} SET current_issue_id = null WHERE {$this->primaryKeyColumn} = ?",
            [(int) $contextId]
        );
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\journal\JournalDAO', '\JournalDAO');
}
