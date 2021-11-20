<?php
/**
 * @file classes/publication/DAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class publication
 *
 * @brief Read and write publications to the database.
 */

namespace APP\publication;

use APP\core\Services;
use APP\facades\Repo;
use stdClass;

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
    ];

    /**
     * @copydoc SchemaDAO::_fromRow()
     */
    public function fromRow(stdClass $primaryRow): Publication
    {
        $publication = parent::fromRow($primaryRow);
        $publication->setData('galleys', iterator_to_array(
            Services::get('galley')->getMany(['publicationIds' => $publication->getId()])
        ));
        $publication->setData('categoryIds', iterator_to_array(Repo::category()->getIds(Repo::category()->getCollector()
            ->filterByPublicationIds([$publication->getId()]))));
        return $publication;
    }
}
