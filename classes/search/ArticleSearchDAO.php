<?php

/**
 * @file classes/search/ArticleSearchDAO.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleSearchDAO
 *
 * @ingroup search
 *
 * @see ArticleSearch
 *
 * @brief DAO class for article search index.
 */

namespace APP\search;

use APP\journal\Journal;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use PKP\search\SubmissionSearchDAO;
use PKP\submission\PKPSubmission;

class ArticleSearchDAO extends SubmissionSearchDAO
{
    /**
     * Retrieve the top results for a phrase.
     *
     * @param Journal $journal
     * @param array $phrase
     * @param int|null $publishedFrom Optional start date
     * @param int|null $publishedTo Optional end date
     * @param int|null $type Application::ASSOC_TYPE_...
     * @param int $limit
     *
     * @return array of results (associative arrays)
     */
    public function getPhraseResults($journal, $phrase, $publishedFrom = null, $publishedTo = null, $type = null, $limit = 500)
    {
        if (empty($phrase)) {
            return [];
        }

        $q = DB::table('submissions', 's')
            ->join('journals AS j', 'j.journal_id', '=', 's.context_id')
            ->leftJoin(
                'journal_settings AS js',
                fn (JoinClause $j) => $j
                    ->on('j.journal_id', '=', 'js.journal_id')
                    ->where('js.locale', '=', '')
                    ->where('js.setting_name', '=', 'publishingMode')
            )
            ->join('publications AS p', 'p.publication_id', '=', 's.current_publication_id')
            ->join(
                'publication_settings AS ps',
                fn (JoinClause $j) => $j
                    ->where('ps.setting_name', '=', 'issueId')
                    ->whereColumn('ps.publication_id', '=', 'p.publication_id')
                    ->where('ps.locale', '=', '')
            )
            ->join('issues AS i', 'i.issue_id', '=', DB::raw('CAST(ps.setting_value AS ' . (DB::connection() instanceof MySqlConnection ? 'UNSIGNED' : 'INTEGER') . ')'))
            ->join('submission_search_objects AS o', 's.submission_id', '=', 'o.submission_id');

        foreach ($phrase as $i => $keyword) {
            $q->join("submission_search_object_keywords AS o{$i}", "o{$i}.object_id", '=', 'o.object_id')
                ->join("submission_search_keyword_list AS k{$i}", "k{$i}.keyword_id", '=', "o{$i}.keyword_id")
                ->where("k{$i}.keyword_text", strstr($phrase[$i], '%') !== false ? 'LIKE' : '=', $keyword)
                ->when(
                    $i,
                    fn (Builder $q) => $q
                        ->whereColumn('o0.object_id', '=', "o{$i}.object_id")
                        ->whereColumn(DB::raw("o0.pos + {$i}"), '=', "o{$i}.pos")
                );
        }

        $q->where('s.status', '=', PKPSubmission::STATUS_PUBLISHED)
            ->when(!empty($journal), fn (Builder $q) => $q->where('j.journal_id', '=', $journal->getId()))
            ->where('j.enabled', '=', 1)
            ->where(DB::raw("COALESCE(js.setting_value, '0')"), '<>', Journal::PUBLISHING_MODE_NONE)
            ->when(!empty($publishedFrom), fn (Builder $q) => $q->where('p.date_published', '>=', $this->datetimeToDB($publishedFrom)))
            ->when(!empty($publishedTo), fn (Builder $q) => $q->where('p.date_published', '<=', $this->datetimeToDB($publishedTo)))
            ->where('i.published', '=', 1)
            ->when(!empty($type), fn (Builder $q) => $q->whereRaw('(o.type & ?) != 0', [$type]))
            ->groupBy('o.submission_id')
            ->orderByDesc('count')
            ->limit($limit)
            ->select(
                'o.submission_id',
                DB::raw('MAX(s.context_id) AS journal_id'),
                DB::raw('MAX(i.date_published) AS i_pub'),
                DB::raw('MAX(p.date_published) AS s_pub'),
                DB::raw('COUNT(0) AS count')
            );

        return $q->get()
            ->mapWithKeys(fn (object $row) => [$row->submission_id => [
                'count' => $row->count,
                'journal_id' => $row->journal_id,
                'issuePublicationDate' => $this->datetimeFromDB($row->i_pub),
                'publicationDate' => $this->datetimeFromDB($row->s_pub)
            ]])
            ->toArray();
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\search\ArticleSearchDAO', '\ArticleSearchDAO');
}
