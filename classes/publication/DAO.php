<?php
/**
 * @file classes/publication/DAO.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DAO
 *
 * @brief Read and write publications to the database.
 */

namespace APP\publication;

use APP\facades\Repo;

class DAO extends \PKP\publication\DAO
{
    /** @copydoc EntityDAO::$primaryTableColumns */
    public $primaryTableColumns = [
        'id' => 'publication_id',
        'accessStatus' => 'access_status',
        'datePublished' => 'date_published',
        'lastModified' => 'last_modified',
        'primaryContactId' => 'primary_contact_id',
        'sectionId' => 'section_id',
        'seq' => 'seq',
        'submissionId' => 'submission_id',
        'status' => 'status',
        'urlPath' => 'url_path',
        'version' => 'version',
        'doiId' => 'doi_id'
    ];

    /**
     * @copydoc SchemaDAO::_fromRow()
     */
    public function fromRow(object $primaryRow): Publication
    {
        $publication = parent::fromRow($primaryRow);

        $publication->setData(
            'galleys',
            Repo::galley()->getCollector()
                ->filterByPublicationIds([$publication->getId()])
                ->getMany()
        );

        return $publication;
    }
}
