<?php

/**
 * @file classes/oai/ojs/OAIDAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OAIDAO
 * @ingroup oai_ojs
 *
 * @see OAI
 *
 * @brief DAO operations for the OJS OAI interface.
 */

namespace APP\oai\ojs;

use APP\facades\Repo;
use Illuminate\Support\Facades\DB;
use PKP\oai\PKPOAIDAO;
use PKP\oai\OAISet;
use PKP\plugins\HookRegistry;
use PKP\db\DAORegistry;

use PKP\submission\PKPSubmission;

class OAIDAO extends PKPOAIDAO
{
    /** Helper DAOs */
    public $journalDao;
    public $sectionDao;
    public $articleGalleyDao;
    public $issueDao;
    public $authorDao;

    public $journalCache;
    public $sectionCache;
    public $issueCache;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->journalDao = DAORegistry::getDAO('JournalDAO');
        $this->sectionDao = DAORegistry::getDAO('SectionDAO');
        $this->articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
        $this->issueDao = DAORegistry::getDAO('IssueDAO');
        $this->authorDao = DAORegistry::getDAO('AuthorDAO');

        $this->journalCache = [];
        $this->sectionCache = [];
    }

    /**
     * @copydoc PKPOAIDAO::getEarliestDatestampQuery()
     */
    public function getEarliestDatestampQuery()
    {
    }

    /**
     * Cached function to get a journal
     *
     * @param $journalId int
     *
     * @return object
     */
    public function &getJournal($journalId)
    {
        if (!isset($this->journalCache[$journalId])) {
            $this->journalCache[$journalId] = $this->journalDao->getById($journalId);
        }
        return $this->journalCache[$journalId];
    }

    /**
     * Cached function to get an issue
     *
     * @param $issueId int
     *
     * @return object
     */
    public function &getIssue($issueId)
    {
        if (!isset($this->issueCache[$issueId])) {
            $this->issueCache[$issueId] = $this->issueDao->getById($issueId);
        }
        return $this->issueCache[$issueId];
    }

    /**
     * Cached function to get a journal section
     *
     * @param $sectionId int
     *
     * @return object
     */
    public function &getSection($sectionId)
    {
        if (!isset($this->sectionCache[$sectionId])) {
            $this->sectionCache[$sectionId] = $this->sectionDao->getById($sectionId);
        }
        return $this->sectionCache[$sectionId];
    }


    //
    // Sets
    //
    /**
     * Return hierarchy of OAI sets (journals plus journal sections).
     *
     * @param $journalId int
     * @param $offset int
     * @param $total int
     *
     * @return array OAISet
     */
    public function &getJournalSets($journalId, $offset, $limit, &$total)
    {
        if (isset($journalId)) {
            $journals = [$this->journalDao->getById($journalId)];
        } else {
            $journals = $this->journalDao->getAll(true);
            $journals = $journals->toArray();
        }

        // FIXME Set descriptions
        $sets = [];
        foreach ($journals as $journal) {
            $title = $journal->getLocalizedName();
            $abbrev = $journal->getPath();
            array_push($sets, new OAISet(urlencode($abbrev), $title, ''));

            $tombstoneDao = DAORegistry::getDAO('DataObjectTombstoneDAO'); /* @var $tombstoneDao DataObjectTombstoneDAO */
            $articleTombstoneSets = $tombstoneDao->getSets(ASSOC_TYPE_JOURNAL, $journal->getId());

            $sections = $this->sectionDao->getByJournalId($journal->getId());
            foreach ($sections->toArray() as $section) {
                if (array_key_exists(urlencode($abbrev) . ':' . urlencode($section->getLocalizedAbbrev()), $articleTombstoneSets)) {
                    unset($articleTombstoneSets[urlencode($abbrev) . ':' . urlencode($section->getLocalizedAbbrev())]);
                }
                array_push($sets, new OAISet(urlencode($abbrev) . ':' . urlencode($section->getLocalizedAbbrev()), $section->getLocalizedTitle(), ''));
            }
            foreach ($articleTombstoneSets as $articleTombstoneSetSpec => $articleTombstoneSetName) {
                array_push($sets, new OAISet($articleTombstoneSetSpec, $articleTombstoneSetName, ''));
            }
        }

        HookRegistry::call('OAIDAO::getJournalSets', [$this, $journalId, $offset, $limit, $total, &$sets]);

        $total = count($sets);
        $sets = array_slice($sets, $offset, $limit);

        return $sets;
    }

    /**
     * Return the journal ID and section ID corresponding to a journal/section pairing.
     *
     * @param $journalSpec string
     * @param $sectionSpec string
     * @param $restrictJournalId int
     *
     * @return array (int, int)
     */
    public function getSetJournalSectionId($journalSpec, $sectionSpec, $restrictJournalId = null)
    {
        $journal = $this->journalDao->getByPath($journalSpec);
        if (!isset($journal) || (isset($restrictJournalId) && $journal->getId() != $restrictJournalId)) {
            return [0, 0];
        }

        $journalId = $journal->getId();
        $sectionId = null;

        if (isset($sectionSpec)) {
            $section = $this->sectionDao->getByAbbrev($sectionSpec, $journal->getId());
            if (isset($section)) {
                $sectionId = $section->getId();
            } else {
                $sectionId = 0;
            }
        }

        return [$journalId, $sectionId];
    }

    //
    // Protected methods.
    //
    /**
     * @see lib/pkp/classes/oai/PKPOAIDAO::setOAIData()
     */
    public function setOAIData($record, $row, $isRecord = true)
    {
        $journal = $this->getJournal($row['journal_id']);
        $section = $this->getSection($row['section_id']);
        $articleId = $row['submission_id'];

        $record->identifier = $this->oai->articleIdToIdentifier($articleId);
        $record->sets = [urlencode($journal->getPath()) . ':' . urlencode($section->getLocalizedAbbrev())];

        if ($isRecord) {
            $submission = Repo::submission()->get($articleId);
            $issue = $this->getIssue($row['issue_id']);
            $galleys = $this->articleGalleyDao->getByPublicationId($submission->getCurrentPublication()->getId())->toArray();

            $record->setData('article', $submission);
            $record->setData('journal', $journal);
            $record->setData('section', $section);
            $record->setData('issue', $issue);
            $record->setData('galleys', $galleys);
        }

        return $record;
    }

    /**
     * @copydoc PKPOAIDAO::_getRecordsRecordSet
     */
    public function _getRecordsRecordSetQuery($setIds, $from, $until, $set, $submissionId = null, $orderBy = 'journal_id, submission_id', $offset = null, $limit = null)
    {
        $journalId = array_shift($setIds);
        $sectionId = array_shift($setIds);

        # Exlude all journals that do not have Oai specifically turned on, see #pkp/pkp-lib#6503
        $excludeJournals = DB::table('journals')
            ->whereNotIn('journal_id', function ($query) {
                $query->select('journal_id')
                    ->from('journal_settings')
                    ->where('setting_name', 'enableOai')
                    ->where('setting_value', 1);
            })
            ->groupBy('journal_id')
            ->pluck('journal_id')
            ->all();

        $submissionsQuery = DB::table('submissions AS a')
            ->select(DB::raw('GREATEST(a.last_modified, i.last_modified) AS last_modified,
                                a.submission_id AS submission_id,
                                j.journal_id AS journal_id,
                                s.section_id AS section_id,
                                i.issue_id,
                                NULL AS tombstone_id,
                                NULL AS set_spec,
                                NULL AS oai_identifier'))
            ->join('publications AS p', 'a.current_publication_id', '=', 'p.publication_id')
            ->join('publication_settings AS psissue', function($join) {
                $join->on('psissue.publication_id', '=', 'p.publication_id');
                $join->where('psissue.setting_name', '=', DB::raw('\'issueId\''));
                $join->where('psissue.locale', '=', DB::raw('\'\''));
            })
            ->join('issues AS i', function($join) {
                $join->on(DB::raw('CAST(i.issue_id AS CHAR(20))'), '=', 'psissue.setting_value');
            })
            ->join('sections AS s', 's.section_id', '=', 'p.section_id')
            ->join('journals AS j', 'j.journal_id', '=', 'a.context_id')
            ->where('i.published', '=', 1)
            ->where('j.enabled', '=', 1)
            ->where('a.status', '=', PKPSubmission::STATUS_PUBLISHED);
        if ($excludeJournals) $submissionsQuery->whereNotIn('j.journal_id', $excludeJournals);
        if (isset($journalId)) $submissionsQuery->where('j.journal_id', '=', (int) $journalId);
        if (isset($sectionId)) $submissionsQuery->where('p.section_id', '=', (int) $sectionId);
        if ($from) $submissionsQuery->where('GREATEST(a.last_modified, i.last_modified)', '>=', $from);
        if ($until) $submissionsQuery->where('GREATEST(a.last_modified, i.last_modified)', '<=', $until);
        if ($submissionId) $submissionsQuery->where('a.submission_id', '=', (int) $submissionId);

        $tombstonesQuery = DB::table('data_object_tombstones AS dot')
            ->select(DB::raw('dot.date_deleted AS last_modified,
                                dot.data_object_id AS submission_id,
                                ' . (isset($journalId) ? 'tsoj.assoc_id' : 'NULL') . ' AS assoc_id,' . '
                                ' . (isset($sectionId) ? 'tsos.assoc_id' : 'NULL') . ' AS section_id,
                                NULL AS issue_id,
                                dot.tombstone_id,
                                dot.set_spec,
                                dot.oai_identifier'));
        if (isset($journalId)) $tombstonesQuery->join('data_object_tombstone_oai_set_objects AS tsoj', function($join) use ($journalId) {
            $join->on('tsoj.tombstone_id', '=', 'dot.tombstone_id');
            $join->where('tsoj.assoc_type', '=', ASSOC_TYPE_JOURNAL);
            $join->where('tsoj.assoc_id', '=', (int) $journalId);
        });
        if (isset($sectionId)) $tombstonesQuery->join('data_object_tombstone_oai_set_objects AS tsos', function($join) use ($sectionId) {
            $join->on('tsos.tombstone_id', '=', 'dot.tombstone_id');
            $join->where('tsos.assoc_type', '=', ASSOC_TYPE_SECTION);
            $join->where('tsos.assoc_id', '=', (int) $sectionId);
        });
        if (isset($set)) $tombstonesQuery->where('dot.set_spec', '=', $set)
                ->orWhere('dot.set_spec', 'like', $set . ':%');
        if ($from) $tombstonesQuery->where('dot.date_deleted', '>=', $from);
        if ($until) $tombstonesQuery->where('dot.date_deleted', '<=', $until);
        if ($submissionId) $tombstonesQuery->where('dot.data_object_id', '=', (int) $submissionId);

        return $submissionsQuery
            ->union($tombstonesQuery)
            ->orderBy(DB::raw($orderBy));
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\oai\ojs\OAIDAO', '\OAIDAO');
}
