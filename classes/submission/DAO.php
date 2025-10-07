<?php

/**
 * @file classes/submission/DAO.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DAO
 *
 * @brief Read and write submissions to the database.
 */

namespace APP\submission;

use APP\plugins\PubObjectsExportPlugin;
use APP\publication\enums\VersionStage;
use APP\publication\Publication;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use PKP\db\DAOResultFactory;
use PKP\db\DBResultRange;
use PKP\identity\Identity;
use PKP\observers\events\SubmissionDeleted;

class DAO extends \PKP\submission\DAO
{
    /**
     * @copydoc \PKP\core\EntityDAO::deleteById()
     */
    public function deleteById(int $id): int
    {
        event(new SubmissionDeleted($id));
        return parent::deleteById($id);
    }

    /**
     * Get all published submissions (eventually with a pubId assigned and) matching the specified settings.
     */
    public function getExportable(int $contextId, ?string $pubIdType = null, ?string $title = null, ?string $author = null, ?int $issueId = null, ?string $settingName = null, ?string $settingValue = null, ?DBResultRange $rangeInfo = null): DAOResultFactory
    {
        $q = DB::table('submissions', 's')
            ->leftJoin('publications AS p', 's.current_publication_id', '=', 'p.publication_id')
            ->leftJoin('publication_settings AS ps', 'p.publication_id', '=', 'ps.publication_id')
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
                    'submission_settings AS pss',
                    fn (JoinClause $j) => $j->on('s.submission_id', '=', 'pss.submission_id')
                        ->where('pss.setting_name', '=', $settingName)
                )
            )
            ->where('s.context_id', '=', $contextId)
            ->where('p.version_stage', '=', VersionStage::VERSION_OF_RECORD)
            ->where('p.status', '=', Publication::STATUS_PUBLISHED)
            ->when($pubIdType != null, fn (Builder $q) => $q->where('pspidt.setting_name', '=', "pub-id::{$pubIdType}")->whereNotNull('pspidt.setting_value'))
            ->when($title != null, fn (Builder $q) => $q->where('pst.setting_name', '=', 'title')->where('pst.setting_value', 'LIKE', "%{$title}%"))
            ->when($author != null, fn (Builder $q) => $q->whereRaw("CONCAT(COALESCE(asgs.setting_value, ''), ' ', COALESCE(asfs.setting_value, '')) LIKE ?", ["%{$author}%"]))
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
            ->groupBy('s.submission_id')
            ->orderByRaw('MAX(p.date_published) DESC')
            ->orderByDesc('s.submission_id')
            ->select('s.*');

        $rows = $this->deprecatedDao->retrieveRange($q, [], $rangeInfo);
        return new DAOResultFactory($rows, $this, 'fromRow', [], $q, [], $rangeInfo);
    }
}
