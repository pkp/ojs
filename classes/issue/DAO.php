<?php

/**
 * @file classes/issue/DAO.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup issue
 *
 * @see Issue
 *
 * @brief Operations for retrieving and modifying Issue objects.
 */

namespace APP\issue;

use APP\facades\Repo;
use APP\plugins\PubObjectsExportPlugin;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use PKP\core\EntityDAO;
use PKP\core\traits\EntityWithParent;
use PKP\db\DAOResultFactory;
use PKP\services\PKPSchemaService;

/**
 * @extends EntityDAO<Issue>
 */
class DAO extends EntityDAO implements \PKP\plugins\PKPPubIdPluginDAO
{
    /**
     * @use EntityWithParent<Issue>
     */
    use EntityWithParent;

    /** @copydoc EntityDAO::$schema */
    public $schema = PKPSchemaService::SCHEMA_ISSUE;

    /** @copydoc EntityDAO::$table */
    public $table = 'issues';

    /** @copydoc EntityDAO::$settingsTable */
    public $settingsTable = 'issue_settings';

    /** @copydoc EntityDAO::$primaryKeyColumn */
    public $primaryKeyColumn = 'issue_id';

    /** @copydoc SchemaDAO::$primaryTableColumns */
    public $primaryTableColumns = [
        'id' => 'issue_id',
        'journalId' => 'journal_id',
        'volume' => 'volume',
        'number' => 'number',
        'year' => 'year',
        'published' => 'published',
        'datePublished' => 'date_published',
        'dateNotified' => 'date_notified',
        'lastModified' => 'last_modified',
        'accessStatus' => 'access_status',
        'openAccessDate' => 'open_access_date',
        'showVolume' => 'show_volume',
        'showNumber' => 'show_number',
        'showYear' => 'show_year',
        'showTitle' => 'show_title',
        'styleFileName' => 'style_file_name',
        'originalStyleFileName' => 'original_style_file_name',
        'urlPath' => 'url_path',
        'doiId' => 'doi_id'
    ];

    /**
     * Get the parent object ID column name
     */
    public function getParentColumn(): string
    {
        return 'journal_id';
    }

    /**
     * Instantiate a new DataObject
     */
    public function newDataObject(): Issue
    {
        return app(Issue::class);
    }

    /**
     * Get the number of announcements matching the configured query
     */
    public function getCount(Collector $query): int
    {
        return $query
            ->getQueryBuilder()
            ->getCountForPagination();
    }

    /**
     * Get a list of ids matching the configured query
     */
    public function getIds(Collector $query): Collection
    {
        return $query
            ->getQueryBuilder()
            ->select('i.' . $this->primaryKeyColumn)
            ->pluck('i.' . $this->primaryKeyColumn);
    }

    /**
     * Get a collection of issues matching the configured query
     *
     * @return LazyCollection<int,Issue>
     */
    public function getMany(Collector $query): LazyCollection
    {
        $rows = $query
            ->getQueryBuilder()
            ->get();

        return LazyCollection::make(function () use ($rows) {
            foreach ($rows as $row) {
                yield $row->issue_id => $this->fromRow($row);
            }
        });
    }

    /**
     * Get the issue id by its url path
     *
     */
    public function getIdByUrlPath(string $urlPath, int $contextId): ?int
    {
        $issue = DB::table($this->table, 'i')
            ->where('i.journal_id', '=', $contextId)
            ->where('i.url_path', '=', $urlPath)
            ->first();

        return $issue ? $issue->issue_id : null;
    }

    /** @copydoc EntityDAO::fromRow() */
    public function fromRow(object $row): Issue
    {
        $issue = parent::fromRow($row);
        $this->setDoiObject($issue);

        return $issue;
    }

    /** @copydoc EntityDAO::_insert() */
    public function insert(Issue $issue): int
    {
        $issueId = parent::_insert($issue);
        $this->resequenceCustomIssueOrders($issue->getData('journalId'));
        return $issueId;
    }

    /** @copydoc EntityDAO::_update() */
    public function update(Issue $issue)
    {
        $issue->stampModified();
        parent::_update($issue);
        $this->resequenceCustomIssueOrders($issue->getData('journalId'));
    }

    /** @copydoc EntityDAO::_delete() */
    public function delete(Issue $issue)
    {
        parent::_delete($issue);
        $this->resequenceCustomIssueOrders($issue->getData('journalId'));
    }

    /**
     * Deletes any custom issue ordering for a given context
     *
     */
    public function deleteCustomIssueOrdering(int $issueId)
    {
        DB::table('custom_issue_orders')
            ->where('issue_id', '=', $issueId)
            ->delete();
    }

    /**
     * Sequentially renumber custom issue orderings in their sequence order.
     *
     */
    public function resequenceCustomIssueOrders(int $contextId)
    {
        // If no custom issue ordering already exists, there is nothing to do
        if (!$this->customIssueOrderingExists($contextId)) {
            return;
        }

        $results = DB::table($this->table, 'i')
            ->leftJoin('custom_issue_orders as o', 'o.issue_id', '=', 'i.issue_id')
            ->where('i.journal_id', '=', $contextId)
            // TODO: Previous behaviour would resequence all issues, including those that haven't been published (cont.)
            // artificially giving them a position in the custom_issue_orders table before they've been published.
            ->where('i.published', '=', 1)
            ->orderBy('o.seq')
            ->select('i.issue_id')
            ->get();
        $results->each(function ($item, $key) use ($contextId) {
            $newSeq = $key + 1;
            DB::table('custom_issue_orders')
                ->updateOrInsert(
                    [
                        'issue_id' => $item->issue_id,
                    ],
                    [
                        'issue_id' => $item->issue_id,
                        'journal_id' => $contextId,
                        'seq' => $newSeq
                    ],
                );
        });
    }

    /**
     * Check if a journal has custom issue ordering
     *
     */
    public function customIssueOrderingExists(int $contextId): bool
    {
        return DB::table('custom_issue_orders', 'o')
            ->where('o.journal_id', '=', $contextId)
            ->getCountForPagination() > 0;
    }

    /**
     * Get the custom issue order of a journal
     *
     */
    public function getCustomIssueOrder(int $contextId, int $issueId): ?int
    {
        $results = DB::table('custom_issue_orders')
            ->where('journal_id', '=', $contextId)
            ->where('issue_id', '=', $issueId);

        $row = $results->first();
        return $row ? (int) $row->seq : null;
    }

    /**
     * Gets a list of all the years in which issues have been published
     *
     */
    public function getYearsIssuesPublished(int $contextId): Collection
    {
        $collector = Repo::issue()->getCollector();
        $q = $collector->filterByContextIds([$contextId])
            ->filterByPublished(true)
            ->getQueryBuilder();

        return $q->select('i.year')
            ->groupBy('i.year')
            ->orderBy('i.year', 'DESC')
            ->pluck('i.year');
    }

    /**
     * INTERNAL USE ONLY: Insert a custom issue ordering
     * TODO: See if should be protected/private
     *
     */
    public function insertCustomIssueOrder(int $contextId, int $issueId, int $seq)
    {
        DB::table('custom_issue_orders')
            ->insert(
                [
                    'issue_id' => $issueId,
                    'journal_id' => $contextId,
                    'seq' => $seq
                ]
            );
    }

    /**
     * Move a custom issue ordering up or down, resequencing as necessary.
     *
     * @param int $newPos The new position (0-based) of this section
     */
    public function moveCustomIssueOrder(int $contextId, int $issueId, int $newPos)
    {
        DB::table('custom_issue_orders')
            ->updateOrInsert(
                [
                    'journal_id' => $contextId,
                    'issue_id' => $issueId
                ],
                [
                    'seq' => $newPos
                ]
            );
    }

    /**
     * @copydoc PKPPubIdPluginDAO::pubIdExists()
     *
     * From legacy IssueDAO
     */
    public function pubIdExists(string $pubIdType, string $pubId, int $excludePubObjectId, int $contextId): bool
    {
        return DB::table('issue_settings AS ist')
            ->join('issues AS i', 'ist.issue_id', '=', 'i.issue_id')
            ->where('ist.setting_name', '=', "pub-id::{$pubIdType}")
            ->where('ist.setting_value', '=', $pubId)
            ->where('i.issue_id', '<>', $excludePubObjectId)
            ->where('i.journal_id', '=', $contextId)
            ->count() > 0;
    }

    /**
     * @copydoc PKPPubIdPluginDAO::changePubId()
     *
     * From legacy IssueDAO
     */
    public function changePubId($pubObjectId, $pubIdType, $pubId)
    {
        DB::table('issue_settings')->updateOrInsert(
            ['issue_id' => (int) $pubObjectId, 'locale' => '', 'setting_name' => 'pub-id::' . $pubIdType],
            ['setting_value' => (string) $pubId]
        );
    }

    /**
     * @copydoc PKPPubIdPluginDAO::deletePubId()
     *
     * From legacy IssueDAO
     */
    public function deletePubId(int $pubObjectId, string $pubIdType): int
    {
        return DB::table('issue_settings')
            ->where('setting_name', '=', "pub-id::{$pubIdType}")
            ->where('issue_id', '=', $pubObjectId)
            ->delete();
    }

    /**
     * @copydoc PKPPubIdPluginDAO::deleteAllPubIds()
     */
    public function deleteAllPubIds(int $contextId, string $pubIdType): int
    {
        $affectedRows = 0;
        $issues = Repo::issue()->getCollector()->filterByContextIds([$contextId])->getMany();
        foreach ($issues as $issue) {
            $affectedRows += $this->deletePubId($issue->getId(), $pubIdType);
        }
        return $affectedRows;
    }

    /**
     * Get all published issues (eventually with a pubId assigned and) matching the specified settings.
     *
     * From legacy IssueDAO
     *
     * @param int $contextId
     * @param string $pubIdType
     * @param string $pubIdSettingName optional
     * (e.g. crossref::registeredDoi)
     * @param string $pubIdSettingValue optional
     * @param ?\PKP\db\DBResultRange $rangeInfo optional
     *
     * @return DAOResultFactory<Issue>
     */
    public function getExportable($contextId, $pubIdType = null, $pubIdSettingName = null, $pubIdSettingValue = null, $rangeInfo = null)
    {
        $q = DB::table('issues', 'i')
            ->leftJoin('custom_issue_orders AS o', 'o.issue_id', '=', 'i.issue_id')
            ->when($pubIdType != null, fn (Builder $q) => $q->leftJoin('issue_settings AS ist', 'i.issue_id', '=', 'ist.issue_id'))
            ->when($pubIdSettingName, fn (Builder $q) => $q->leftJoin('issue_settings AS iss', fn (JoinClause $j) => $j->on('i.issue_id', '=', 'iss.issue_id')->where('iss.setting_name', '=', $pubIdSettingName)))
            ->where('i.published', '=', 1)
            ->where('i.journal_id', '=', (int) $contextId)
            ->when($pubIdType != null, fn (Builder $q) => $q->where('ist.setting_name', '=', "pub-id::{$pubIdType}")->whereNotNull('ist.setting_value'))
            ->when(
                $pubIdSettingName,
                fn (Builder $q) => $q->when(
                    $pubIdSettingValue === null,
                    fn (Builder $q) => $q->whereRaw("COALESCE(iss.setting_value, '') = ''"),
                    fn (Builder $q) => $q->when(
                        $pubIdSettingValue != PubObjectsExportPlugin::EXPORT_STATUS_NOT_DEPOSITED,
                        fn (Builder $q) => $q->where('iss.setting_value', '=', $pubIdSettingValue),
                        fn (Builder $q) => $q->whereNull('iss.setting_value')
                    )
                )
            )
            ->orderByDesc('i.date_published')
            ->select('i.*');

        $result = $this->deprecatedDao->retrieveRange($q, [], $rangeInfo);
        return new DAOResultFactory($result, $this, 'fromRow', [], $q, [], $rangeInfo);
    }

    /**
     * Set the DOI object
     *
     */
    protected function setDoiObject(Issue $issue)
    {
        if (!empty($issue->getData('doiId'))) {
            $issue->setData('doiObject', Repo::doi()->get($issue->getData('doiId')));
        }
    }
}
