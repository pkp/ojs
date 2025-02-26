<?php

namespace APP\galley;

use APP\plugins\PubObjectsExportPlugin;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use PKP\db\DAOResultFactory;
use PKP\galley\DAO as PKPGalleyDAO;
use PKP\identity\Identity;
use PKP\submission\PKPSubmission;

/**
 * OJS-specific GalleyDAO override, including issueId filtering.
 */
class DAO extends PKPGalleyDAO
{
    /**
     * OJS version of getExportable with $issueId filter
     *
     * @param null|mixed $pubIdType
     * @param null|mixed $title
     * @param null|mixed $author
     * @param null|mixed $pubIdSettingName
     * @param null|mixed $pubIdSettingValue
     * @param null|mixed $rangeInfo
     */
    public function getExportable(int $contextId, $pubIdType = null, $title = null, $author = null, $pubIdSettingName = null, $pubIdSettingValue = null, $rangeInfo = null, ?int $issueId = null)
    {
        $q = DB::table('publication_galleys', 'g')
            ->leftJoin('publications AS p', 'p.publication_id', '=', 'g.publication_id')
            ->leftJoin('submissions AS s', 's.submission_id', '=', 'p.submission_id')
            ->leftJoin('submission_files AS sf', 'g.submission_file_id', '=', 'sf.submission_file_id')
            ->when($pubIdType != null, fn (Builder $q) => $q->leftJoin('publication_galley_settings AS gs', 'g.galley_id', '=', 'gs.galley_id'))
            ->when($title != null, fn (Builder $q) => $q->leftJoin('publication_settings AS pst', 'p.publication_id', '=', 'pst.publication_id'))
            ->when(
                $author != null,
                fn (Builder $q) => $q->leftJoin('authors AS au', 'p.publication_id', '=', 'au.publication_id')
                    ->leftJoin('author_settings AS asgs', fn (JoinClause $j) => $j->on('asgs.author_id', '=', 'au.author_id')->where('asgs.setting_name', '=', Identity::IDENTITY_SETTING_GIVENNAME))
                    ->leftJoin('author_settings AS asfs', fn (JoinClause $j) => $j->on('asfs.author_id', '=', 'au.author_id')->where('asfs.setting_name', '=', Identity::IDENTITY_SETTING_FAMILYNAME))
            )
            ->when($pubIdSettingName != null, fn (Builder $q) => $q->leftJoin('publication_galley_settings AS gss', fn (JoinClause $j) => $j->on('g.galley_id', '=', 'gss.galley_id')->where('gss.setting_name', '=', $pubIdSettingName)))
            ->where('s.status', '=', PKPSubmission::STATUS_PUBLISHED)
            ->where('s.context_id', '=', $contextId)
            ->when($pubIdType != null, fn (Builder $q) => $q->where('gs.setting_name', '=', "pub-id::{$pubIdType}")->whereNotNull('gs.setting_value'))
            ->when($title != null, fn (Builder $q) => $q->where('pst.setting_name', '=', 'title')->where('pst.setting_value', 'LIKE', "%{$title}%"))
            ->when($author != null, fn (Builder $q) => $q->whereRaw("CONCAT(COALESCE(asgs.setting_value, ''), ' ', COALESCE(asfs.setting_value, '')) LIKE ?", ["%{$author}%"]))
            ->when($issueId !== null, fn (Builder $q) => $q->where('p.issue_id', '=', $issueId))

            ->when(
                $pubIdSettingName,
                fn (Builder $q) =>
            $q->when(
                $pubIdSettingValue === null,
                fn (Builder $q) => $q->whereRaw("COALESCE(gss.setting_value, '') = ''"),
                fn (Builder $q) => $q->when(
                    $pubIdSettingValue != PubObjectsExportPlugin::EXPORT_STATUS_NOT_DEPOSITED,
                    fn (Builder $q) => $q->where('gss.setting_value', '=', $pubIdSettingValue),
                    fn (Builder $q) => $q->whereNull('gss.setting_value')
                )
            )
            )
            ->groupBy('g.galley_id')
            ->orderByDesc('p.date_published')
            ->orderByDesc('p.publication_id')
            ->orderByDesc('g.galley_id')
            ->select('g.*');

        $result = $this->deprecatedDao->retrieveRange($q, [], $rangeInfo);
        return new DAOResultFactory($result, $this, 'fromRow', [], $q, [], $rangeInfo);
    }
}
