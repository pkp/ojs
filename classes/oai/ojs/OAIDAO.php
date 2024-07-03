<?php

/**
 * @file classes/oai/ojs/OAIDAO.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OAIDAO
 *
 * @ingroup oai_ojs
 *
 * @see OAI
 *
 * @brief DAO operations for the OJS OAI interface.
 */

namespace APP\oai\ojs;

use APP\core\Application;
use APP\facades\Repo;
use APP\journal\JournalDAO;
use Illuminate\Support\Facades\DB;
use PKP\db\DAORegistry;
use PKP\galley\DAO;
use PKP\oai\OAISet;
use PKP\oai\OAIUtils;
use PKP\oai\PKPOAIDAO;
use PKP\plugins\Hook;
use PKP\submission\PKPSubmission;
use PKP\tombstone\DataObjectTombstoneDAO;

class OAIDAO extends PKPOAIDAO
{
    // Helper DAOs
    /** @var JournalDAO */
    public $journalDao;
    /** @var DAO */
    public $galleyDao;

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
        $this->galleyDao = Repo::galley()->dao;

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
     * @param int $journalId
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
     * @param int $issueId
     *
     * @return object
     */
    public function &getIssue($issueId)
    {
        if (!isset($this->issueCache[$issueId])) {
            $this->issueCache[$issueId] = Repo::issue()->get($issueId);
        }
        return $this->issueCache[$issueId];
    }

    /**
     * Cached function to get a journal section
     *
     * @param int $sectionId
     *
     * @return object
     */
    public function &getSection($sectionId)
    {
        if (!isset($this->sectionCache[$sectionId])) {
            $this->sectionCache[$sectionId] = Repo::section()->get($sectionId);
        }
        return $this->sectionCache[$sectionId];
    }


    //
    // Sets
    //
    /**
     * Return hierarchy of OAI sets (journals plus journal sections).
     *
     * @param int $journalId
     * @param int $offset
     * @param int $total
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
            array_push($sets, new OAISet(self::setSpec($journal), $title, ''));

            $tombstoneDao = DAORegistry::getDAO('DataObjectTombstoneDAO'); /** @var DataObjectTombstoneDAO $tombstoneDao */
            $articleTombstoneSets = $tombstoneDao->getSets(Application::ASSOC_TYPE_JOURNAL, $journal->getId());

            $sections = Repo::section()->getCollector()->filterByContextIds([$journal->getId()])->getMany();
            foreach ($sections as $section) {
                $setSpec = self::setSpec($journal, $section);
                if (array_key_exists($setSpec, $articleTombstoneSets)) {
                    unset($articleTombstoneSets[$setSpec]);
                }
                array_push($sets, new OAISet($setSpec, $section->getLocalizedTitle(), ''));
            }
            foreach ($articleTombstoneSets as $articleTombstoneSetSpec => $articleTombstoneSetName) {
                array_push($sets, new OAISet($articleTombstoneSetSpec, $articleTombstoneSetName, ''));
            }
        }

        Hook::call('OAIDAO::getJournalSets', [$this, $journalId, $offset, $limit, $total, &$sets]);

        $total = count($sets);
        $sets = array_slice($sets, $offset, $limit);

        return $sets;
    }

    /**
     * Return the journal ID and section ID corresponding to a journal/section pairing.
     *
     * @param string $journalSpec
     * @param string $sectionSpec
     * @param int $restrictJournalId
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
            $sectionId = 0;
            $sections = Repo::section()->getCollector()->filterByContextIds([$journalId])->getMany();
            foreach ($sections as $section) {
                if ($sectionSpec == OAIUtils::toValidSetSpec($section->getLocalizedAbbrev())) {
                    $sectionId = $section->getId();
                    break;
                }
            }
        }

        return [$journalId, $sectionId];
    }

    public static function setSpec($journal, $section = null): string
    {
        // journal path is already restricted to ascii alphanumeric, '-' and '_'
        return isset($section)
            ? $journal->getPath() . ':' . OAIUtils::toValidSetSpec($section->getLocalizedAbbrev())
            : $journal->getPath();
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

        /** @var JournalOAI */
        $oai = $this->oai;
        $record->identifier = $oai->articleIdToIdentifier($articleId);
        $record->sets = [self::setSpec($journal, $section)];

        if ($isRecord) {
            $submission = Repo::submission()->get($articleId);
            $issue = $this->getIssue($row['issue_id']);
            $galleys = Repo::galley()->getCollector()
                ->filterByPublicationIds([$submission->getCurrentPublication()->getId()])
                ->getMany();

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
     *
     * @param null|mixed $submissionId
     */
    public function _getRecordsRecordSetQuery($setIds, $from, $until, $set, $submissionId = null, $orderBy = 'journal_id, submission_id')
    {
        $journalId = array_shift($setIds);
        $sectionId = array_shift($setIds);

        // Exclude all journals that do not have OAI-PMH specifically turned on, see #pkp/pkp-lib#6503
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

        return DB::table('submissions AS a')
            ->select([
                DB::raw('GREATEST(a.last_modified, i.last_modified, p.last_modified) AS last_modified'),
                'a.submission_id AS submission_id',
                'i.issue_id',
                DB::raw('NULL AS tombstone_id'),
                DB::raw('NULL AS set_spec'),
                DB::raw('NULL AS oai_identifier'),
                'j.journal_id AS journal_id',
                's.section_id AS section_id',
            ])
            ->join('publications AS p', 'a.current_publication_id', '=', 'p.publication_id')
            ->join('publication_settings AS psissue', function ($join) {
                $join->on('psissue.publication_id', '=', 'p.publication_id');
                $join->where('psissue.setting_name', '=', DB::raw('\'issueId\''));
                $join->where('psissue.locale', '=', DB::raw('\'\''));
            })
            ->join('issues AS i', DB::raw('CAST(i.issue_id AS CHAR(20))'), '=', 'psissue.setting_value')
            ->join('sections AS s', 's.section_id', '=', 'p.section_id')
            ->join('journals AS j', 'j.journal_id', '=', 'a.context_id')
            ->where('i.published', '=', 1)
            ->where('j.enabled', '=', 1)
            ->where('a.status', '=', PKPSubmission::STATUS_PUBLISHED)
            ->when($excludeJournals, function ($query, $excludeJournals) {
                return $query->whereNotIn('j.journal_id', $excludeJournals);
            })
            ->when(isset($journalId), function ($query) use ($journalId) {
                return $query->where('j.journal_id', '=', (int) $journalId);
            })
            ->when(isset($sectionId), function ($query) use ($sectionId) {
                return $query->where('p.section_id', '=', (int) $sectionId);
            })
            ->when($from, function ($query, $from) {
                return $query->whereDate(DB::raw('GREATEST(a.last_modified, i.last_modified, p.last_modified)'), '>=', \DateTime::createFromFormat('U', $from));
            })
            ->when($until, function ($query, $until) {
                return $query->whereDate(DB::raw('GREATEST(a.last_modified, i.last_modified, p.last_modified)'), '<=', \DateTime::createFromFormat('U', $until));
            })
            ->when($submissionId, function ($query, $submissionId) {
                return $query->where('a.submission_id', '=', (int) $submissionId);
            })
            ->union(
                DB::table('data_object_tombstones AS dot')
                    ->select([
                        'dot.date_deleted AS last_modified',
                        'dot.data_object_id AS submission_id',
                        DB::raw('NULL AS issue_id'),
                        'dot.tombstone_id',
                        'dot.set_spec',
                        'dot.oai_identifier'
                    ])
                    ->when(isset($journalId), function ($query, $journalId) {
                        return $query->join('data_object_tombstone_oai_set_objects AS tsoj', function ($join) use ($journalId) {
                            $join->on('tsoj.tombstone_id', '=', 'dot.tombstone_id');
                            $join->where('tsoj.assoc_type', '=', Application::ASSOC_TYPE_JOURNAL);
                            $join->where('tsoj.assoc_id', '=', (int) $journalId);
                        })->addSelect(['tsoj.assoc_id']);
                    }, function ($query) {
                        return $query->addSelect([DB::raw('NULL AS assoc_id')]);
                    })
                    ->when(isset($sectionId), function ($query) use ($sectionId) {
                        return $query->join('data_object_tombstone_oai_set_objects AS tsos', function ($join) use ($sectionId) {
                            $join->on('tsos.tombstone_id', '=', 'dot.tombstone_id');
                            $join->where('tsos.assoc_type', '=', Application::ASSOC_TYPE_SECTION);
                            $join->where('tsos.assoc_id', '=', (int) $sectionId);
                        })->addSelect(['tsos.assoc_id']);
                    }, function ($query) {
                        return $query->addSelect([DB::raw('NULL AS assoc_id')]);
                    })
                    ->when(isset($set), function ($query) use ($set) {
                        return $query->where('dot.set_spec', '=', $set)
                            ->orWhere('dot.set_spec', 'like', $set . ':%');
                    })
                    ->when($from, function ($query, $from) {
                        return $query->whereDate('dot.date_deleted', '>=', \DateTime::createFromFormat('U', $from));
                    })
                    ->when($until, function ($query, $until) {
                        return $query->whereDate('dot.date_deleted', '<=', \DateTime::createFromFormat('U', $until));
                    })
                    ->when($submissionId, function ($query, $submissionId) {
                        return $query->where('dot.data_object_id', '=', (int) $submissionId);
                    })
            )
            ->orderBy(DB::raw($orderBy));
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\oai\ojs\OAIDAO', '\OAIDAO');
}
