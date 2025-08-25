<?php

/**
 * @file classes/publication/DAO.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2000-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DAO
 *
 * @brief Read and write publications to the database.
 */

namespace APP\publication;

use APP\facades\Repo;
use APP\plugins\PubObjectsExportPlugin;
use APP\publication\enums\VersionStage;
use APP\submission\Submission;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use PKP\db\DAOResultFactory;
use PKP\db\DBResultRange;
use PKP\identity\Identity;

class DAO extends \PKP\publication\DAO
{
    /** @copydoc EntityDAO::$primaryTableColumns */
    public $primaryTableColumns = [
        'id' => 'publication_id',
        'accessStatus' => 'access_status',
        'datePublished' => 'date_published',
        'published' => 'published',
        'lastModified' => 'last_modified',
        'primaryContactId' => 'primary_contact_id',
        'sectionId' => 'section_id',
        'seq' => 'seq',
        'submissionId' => 'submission_id',
        'status' => 'status',
        'urlPath' => 'url_path',
        'doiId' => 'doi_id',
        'issueId' => 'issue_id',
        'versionStage' => 'version_stage',
        'versionMinor' => 'version_minor',
        'versionMajor' => 'version_major',
        'createdAt' => 'created_at',
        'sourcePublicationId' => 'source_publication_id'
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

    /**
     * Get all published VoR versions (eventually with a pubId assigned and) matching the specified settings.
     * Only the latest minor version of a major version will be considered.
     */
    public function getExportable(int $contextId, ?string $pubIdType = null, ?string $title = null, ?string $author = null, ?int $issueId = null, ?string $settingName = null, ?string $settingValue = null, ?DBResultRange $rangeInfo = null): DAOResultFactory
    {
        $q = DB::table('publications', 'p')
            ->join('submissions AS s', 's.submission_id', '=', 'p.submission_id')
            ->leftJoin(
                'publications AS p2',
                fn (JoinClause $j) => $j->on('p2.submission_id', '=', 'p.submission_id')
                    ->on('p2.version_stage', '=', 'p.version_stage')
                    ->on('p2.version_major', '=', 'p.version_major')
                    ->on('p.version_minor', '<', 'p2.version_minor')
                    ->where('p2.status', '=', Submission::STATUS_PUBLISHED)
            )
            ->when(
                $pubIdType != null,
                fn (Builder $q) => $q->leftJoin('publication_settings AS pspidt', 'p.publication_id', '=', 'pspidt.publication_id')
            )
            ->when($title != null, fn (Builder $q) => $q->leftJoin('publication_settings AS pst', 'p.publication_id', '=', 'pst.publication_id'))
            ->when(
                $author != null,
                fn (Builder $q) => $q->leftJoin('authors AS au', 'p.publication_id', '=', 'au.publication_id')
                    ->leftJoin(
                        'author_settings AS asgs',
                        fn (JoinClause $j) => $j->on('asgs.author_id', '=', 'au.author_id')
                            ->where('asgs.setting_name', '=', Identity::IDENTITY_SETTING_GIVENNAME)
                    )
                    ->leftJoin(
                        'author_settings AS asfs',
                        fn (JoinClause $j) => $j->on('asfs.author_id', '=', 'au.author_id')
                            ->where('asfs.setting_name', '=', Identity::IDENTITY_SETTING_FAMILYNAME)
                    )
            )
            ->when(
                $settingName,
                fn (Builder $q) => $q->leftJoin(
                    'publication_settings AS pss',
                    fn (JoinClause $j) => $j->on('p.publication_id', '=', 'pss.publication_id')
                        ->where('pss.setting_name', '=', $settingName)
                )
            )
            ->where('s.status', '=', Submission::STATUS_PUBLISHED)
            ->where('s.context_id', '=', $contextId)
            ->where('p.version_stage', '=', VersionStage::VERSION_OF_RECORD)
            ->where('p.status', '=', Submission::STATUS_PUBLISHED) // TO-DO: see if this is enough, or if we should consider published or date_published column ?
            ->whereNull('p2.publication_id')
            ->when($pubIdType != null, fn (Builder $q) => $q->where('pspidt.setting_name', '=', "pub-id::{$pubIdType}")->whereNotNull('pspidt.setting_value'))
            ->when($title != null, fn (Builder $q) => $q->where('pst.setting_name', '=', 'title')->where('pst.setting_value', 'LIKE', "%{$title}%"))
            ->when($author != null, fn (Builder $q) => $q->whereRaw("CONCAT(COALESCE(asgs.setting_value, ''), ' ', COALESCE(asfs.setting_value, '')) LIKE ?", [$author]))
            ->when($issueId != null, fn (Builder $q) => $q->where('p.issue_id', '=', $issueId))
            ->when(
                $settingName,
                fn (Builder $q) => $q->when(
                    $settingValue === null,
                    fn (Builder $q) => $q->whereRaw("COALESCE(pss.setting_value, '') = ''"),
                    fn (Builder $q) => $q->when(
                        $settingValue == PubObjectsExportPlugin::EXPORT_STATUS_NOT_DEPOSITED,
                        fn (Builder $q) => $q->whereNull('pss.setting_value'),
                        fn (Builder $q) => $q->when(
                            $settingValue == PubObjectsExportPlugin::EXPORT_STATUS_DEPOSITABLE,
                            fn (Builder $q) => $q->whereNull('pss.setting_value')->orWhere('pss.setting_value', '=', PubObjectsExportPlugin::EXPORT_STATUS_STALE),
                            fn (Builder $q) => $q->where('pss.setting_value', '=', $settingValue)
                        )
                    )
                )
            )
            ->groupBy('p.publication_id')
            ->orderByDesc('s.submission_id')
            ->orderByDesc('p.version_major')
            ->select('p.*');

        $rows = $this->deprecatedDao->retrieveRange($q, [], $rangeInfo);
        return new DAOResultFactory($rows, $this, 'fromRow', [], $q, [], $rangeInfo);
    }
}
