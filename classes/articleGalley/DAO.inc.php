<?php
/**
 * @file classes/galley/DAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class galley
 *
 * @brief Read and write galleys to the database.
 */

namespace APP\articleGalley;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use PKP\core\EntityDAO;
use stdClass;

class DAO extends EntityDAO
{
    /** @copydoc EntityDAO::$schema */
    public $schema = \PKP\services\PKPSchemaService::SCHEMA_GALLEY;

    /** @copydoc EntityDAO::$table */
    public $table = 'publication_galleys';

    /** @copydoc EntityDAO::$settingsTable */
    public $settingsTable = 'publication_galley_settings';

    /** @copydoc EntityDAO::$primarykeyColumn */
    public $primaryKeyColumn = 'galley_id';

    /** @copydoc EntityDAO::$primaryTableColumns */
    public $primaryTableColumns = [
        'submissionFileId' => 'submission_file_id',
        'id' => 'galley_id',
        'isApproved' => 'is_approved',
        'locale' => 'locale',
        'label' => 'label',
        'publicationId' => 'publication_id',
        'seq' => 'seq',
        'urlPath' => 'url_path',
        'urlRemote' => 'remote_url',
    ];

    /**
     * Instantiate a new DataObject
     */
    public function newDataObject(): ArticleGalley
    {
        return App::make(ArticleGalley::class);
    }

    /**
     * @copydoc EntityDAO::get()
     */
    public function get(int $id): ?ArticleGalley
    {
        return parent::get($id);
    }

    /**
     * Get the number of galleys matching the configured query
     */
    public function getCount(Collector $query): int
    {
        return $query
            ->getQueryBuilder()
            ->select('g.' . $this->primaryKeyColumn)
            ->get()
            ->count();
    }

    /**
     * Get a list of ids matching the configured query
     */
    public function getIds(Collector $query): Collection
    {
        return $query
            ->getQueryBuilder()
            ->select('g.' . $this->primaryKeyColumn)
            ->pluck('g.' . $this->primaryKeyColumn);
    }

    /**
     * Get a collection of galleys matching the configured query
     */
    public function getMany(Collector $query): LazyCollection
    {
        $rows = $query
            ->getQueryBuilder()
            ->select(['g.*'])
            ->get();

        return LazyCollection::make(function () use ($rows) {
            foreach ($rows as $row) {
                yield $this->fromRow($row);
            }
        });
    }

    /**
     * Retrieve all galleys of a journal.
     *
     * @param $journalId
     */
    public function getByContextId($journalId)
    {
        $q = DB::table($this->dao->table . ' as g')
            ->join('publications as p', 'p.publication_id', '=', 'g.publication_id')
            ->leftJoin('submissions as s', 's.submission_id', '=', 'p.submission_id')
            ->leftJoin('submission_files as sf', 'sg.submission_file_id', '=', 'sf.submission_file_id')
            ->where('s.context_id', '= ?', (int)$journalId);

        return $q;
    }

    /**
     * @copydoc EntityDAO::fromRow()
     */
    public function fromRow(stdClass $row): ArticleGalley
    {
        return parent::fromRow($row);
    }

    /**
     * @copydoc EntityDAO::insert()
     */
    public function insert(ArticleGalley $articleGalley): int
    {
        return parent::_insert($articleGalley);
    }

    /**
     * @copydoc EntityDAO::update()
     */
    public function update(ArticleGalley $articleGalley)
    {
        parent::_update($articleGalley);
    }

    /**
     * @copydoc EntityDAO::delete()
     */
    public function delete(ArticleGalley $articleGalley)
    {
        parent::_delete($articleGalley);
    }
}
