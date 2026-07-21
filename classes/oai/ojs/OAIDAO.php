<?php

/**
 * @file classes/oai/ojs/OAIDAO.php
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2003-2026 John Willinsky
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
use APP\issue\Issue;
use APP\journal\Journal;
use APP\journal\JournalDAO;
use APP\publication\enums\VersionStage;
use APP\section\Section;
use DateTime;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PKP\context\Context;
use PKP\db\DAO;
use PKP\db\DAORegistry;
use PKP\galley\DAO as PKPGalleyDAO;
use PKP\oai\OAISet;
use PKP\oai\OAIUtils;
use PKP\oai\PKPOAIDAO;
use PKP\plugins\Hook;
use PKP\publication\PKPPublication;
use PKP\tombstone\DataObjectTombstoneDAO;

class OAIDAO extends PKPOAIDAO
{
    // Helper DAOs
    public JournalDAO|DAO $journalDao;
    public PKPGalleyDAO $galleyDao;

    public array $journalCache;
    public array $sectionCache;
    public array $issueCache;

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
     * Cached function to get a journal.
     */
    public function &getJournal(int $journalId): ?Journal
    {
        if (!isset($this->journalCache[$journalId])) {
            $this->journalCache[$journalId] = $this->journalDao->getById($journalId);
        }
        return $this->journalCache[$journalId];
    }

    /**
     * Cached function to get an issue
     */
    public function &getIssue(?int $issueId): ?Issue
    {
        if (is_null($issueId)) {
            return $issueId;
        }

        if (!isset($this->issueCache[$issueId])) {
            $this->issueCache[$issueId] = Repo::issue()->get($issueId);
        }

        return $this->issueCache[$issueId];
    }

    /**
     * Cached function to get a journal section.
     */
    public function &getSection(int $sectionId): ?Section
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
     * @hook OAIDAO::getJournalSets [[$this, $journalId, $offset, $limit, $total, &$sets]]
     */
    public function &getJournalSets(?int $journalId, int $offset, $limit, int &$total): array
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
            $sets[] = new OAISet(self::setSpec($journal), $title, '');
            /** @var DataObjectTombstoneDAO $tombstoneDao */
            $tombstoneDao = DAORegistry::getDAO('DataObjectTombstoneDAO');
            $articleTombstoneSets = $tombstoneDao->getSets(Application::ASSOC_TYPE_JOURNAL, $journal->getId());

            $sections = Repo::section()->getCollector()->filterByContextIds([$journal->getId()])->getMany();
            foreach ($sections as $section) {
                $setSpec = self::setSpec($journal, $section);
                if (array_key_exists($setSpec, $articleTombstoneSets)) {
                    unset($articleTombstoneSets[$setSpec]);
                }
                $sets[] = new OAISet($setSpec, $section->getLocalizedTitle(), '');
            }
            foreach ($articleTombstoneSets as $articleTombstoneSetSpec => $articleTombstoneSetName) {
                $sets[] = new OAISet($articleTombstoneSetSpec, $articleTombstoneSetName, '');
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
     * @return array (int, int)
     */
    public function getSetJournalSectionId(
        string $journalSpec,
        ?string $sectionSpec,
        ?int $restrictJournalId = null
    ): array {
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
        $publicationId = $row['publication_id'] ?? null;
        $currentPublicationId = $row['current_publication_id'] ?? null;
        $versionMajor = $row['version_major'] ?? null;
        $versionStage = $row['version_stage'] ?? null;

        // Older versions are exposed under a version-specific identifier keyed to the
        // version stage and major version number (stable across minor updates); the current
        // version keeps the unversioned identifier.
        $isCurrentVersion = !$publicationId || ($publicationId == $currentPublicationId);

        /** @var JournalOAI $oai */
        $oai = $this->oai;
        $record->identifier = $oai->articleIdToIdentifier(
            $articleId,
            $isCurrentVersion ? null : $versionStage,
            $isCurrentVersion ? null : $versionMajor
        );
        $record->sets = [self::setSpec($journal, $section)];

        if ($isRecord) {
            $submission = Repo::submission()->get($articleId);

            // Metadata formats read the submission's current publication, so point
            // it at the version this record represents.
            if (!$isCurrentVersion) {
                $submission->setData('currentPublicationId', $publicationId);
            }
            $renderedPublicationId = $publicationId ?: $submission->getCurrentPublication()->getId();

            $issue = $this->getIssue($row['issue_id']);
            $galleys = Repo::galley()->getCollector()
                ->filterByPublicationIds([$renderedPublicationId])
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
     * @copydoc PKPOAIDAO::getRecordsRecordSetQuery()
     */
    public function getRecordsRecordSetQuery(
        array $setIds,
        int|string|null $from,
        int|string|null $until,
        ?string $set,
        ?int $submissionId = null,
        string $orderBy = 'journal_id, submission_id',
        ?int $publicationId = null
    ): Builder {
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

        // Journals that expose one OAI record per (major) publication version: those with
        // DOI versioning and DOIs enabled. For these, each published version of a record
        // gets its own record instead of only the current publication being exposed.
        $versioningJournalIds = DB::table('journal_settings')
            ->where('setting_name', '=', Context::SETTING_DOI_VERSIONING)
            ->where('setting_value', '=', '1')
            ->whereIn('journal_id', function ($query) {
                $query->select('journal_id')
                    ->from('journal_settings')
                    ->where('setting_name', '=', Context::SETTING_ENABLE_DOIS)
                    ->where('setting_value', '=', '1');
            })
            ->pluck('journal_id')
            ->all();

        // Version stages exposed as their own OAI record when DOI versioning is enabled.
        $versionedStages = [
            VersionStage::AUTHOR_ORIGINAL->value,
            VersionStage::PUBLISHED_MANUSCRIPT_UNDER_REVIEW->value,
            VersionStage::VERSION_OF_RECORD->value,
        ];

        // Records for the current publication (journals without per-version OAI records).
        $query = DB::table('submissions AS a')
            ->select([
                DB::raw('GREATEST(i.last_modified, p.last_modified) AS last_modified'),
                'a.submission_id AS submission_id',
                'i.issue_id',
                DB::raw('NULL AS tombstone_id'),
                DB::raw('NULL AS set_spec'),
                DB::raw('NULL AS oai_identifier'),
                'j.journal_id AS journal_id',
                's.section_id AS section_id',
                'p.publication_id AS publication_id',
                'a.current_publication_id AS current_publication_id',
                'p.version_major AS version_major',
                'p.version_stage AS version_stage',
            ])
            ->join('publications AS p', 'a.current_publication_id', '=', 'p.publication_id')
            ->leftJoin('issues AS i', 'i.issue_id', '=', 'p.issue_id')
            ->join('sections AS s', 's.section_id', '=', 'p.section_id')
            ->join('journals AS j', 'j.journal_id', '=', 'a.context_id')
            ->where('j.enabled', '=', 1)
            ->where('p.status', '=', PKPPublication::STATUS_PUBLISHED)
            ->when($versioningJournalIds, function ($query, $versioningJournalIds) {
                return $query->whereNotIn('j.journal_id', $versioningJournalIds);
            })
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
                return $query->whereDate(
                    DB::raw('GREATEST(i.last_modified, p.last_modified)'),
                    '>=',
                    DateTime::createFromFormat('U', $from)
                );
            })
            ->when($until, function ($query, $until) {
                return $query->whereDate(
                    DB::raw('GREATEST(i.last_modified, p.last_modified)'),
                    '<=',
                    DateTime::createFromFormat('U', $until)
                );
            })
            ->when($submissionId, function ($query, $submissionId) {
                return $query->where('a.submission_id', '=', (int) $submissionId);
            })
            ->when($publicationId, function ($query, $publicationId) {
                return $query->where('p.publication_id', '=', (int) $publicationId);
            });

        // Records for each published (major) version of record, exposed via the latest
        // minor version of each major, for journals with DOI versioning.
        if (!empty($versioningJournalIds)) {
            $versionQuery = DB::table('submissions AS a')
                ->select([
                    DB::raw('GREATEST(i.last_modified, p.last_modified) AS last_modified'),
                    'a.submission_id AS submission_id',
                    'i.issue_id',
                    DB::raw('NULL AS tombstone_id'),
                    DB::raw('NULL AS set_spec'),
                    DB::raw('NULL AS oai_identifier'),
                    'j.journal_id AS journal_id',
                    's.section_id AS section_id',
                    'p.publication_id AS publication_id',
                    'a.current_publication_id AS current_publication_id',
                    'p.version_major AS version_major',
                    'p.version_stage AS version_stage',
                ])
                ->join('publications AS p', 'p.submission_id', '=', 'a.submission_id')
                // Keep only the latest minor version of each major version of record.
                ->leftJoin('publications AS p2', function ($join) {
                    $join->on('p2.submission_id', '=', 'p.submission_id')
                        ->on('p2.version_stage', '=', 'p.version_stage')
                        ->on('p2.version_major', '=', 'p.version_major')
                        ->on('p.version_minor', '<', 'p2.version_minor')
                        ->where('p2.status', '=', PKPPublication::STATUS_PUBLISHED);
                })
                ->leftJoin('issues AS i', 'i.issue_id', '=', 'p.issue_id')
                ->join('sections AS s', 's.section_id', '=', 'p.section_id')
                ->join('journals AS j', 'j.journal_id', '=', 'a.context_id')
                ->whereNull('p2.publication_id')
                ->where('j.enabled', '=', 1)
                ->where('p.status', '=', PKPPublication::STATUS_PUBLISHED)
                ->whereIn('p.version_stage', $versionedStages)
                ->whereIn('j.journal_id', $versioningJournalIds)
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
                    return $query->whereDate(
                        DB::raw('GREATEST(i.last_modified, p.last_modified)'),
                        '>=',
                        DateTime::createFromFormat('U', $from)
                    );
                })
                ->when($until, function ($query, $until) {
                    return $query->whereDate(
                        DB::raw('GREATEST(i.last_modified, p.last_modified)'),
                        '<=',
                        DateTime::createFromFormat('U', $until)
                    );
                })
                ->when($submissionId, function ($query, $submissionId) {
                    return $query->where('a.submission_id', '=', (int) $submissionId);
                })
                ->when($publicationId, function ($query, $publicationId) {
                    return $query->where('p.publication_id', '=', (int) $publicationId);
                })
                // A single-record lookup by the bare identifier resolves to the current publication.
                ->when($submissionId && !$publicationId, function ($query) {
                    return $query->whereColumn('p.publication_id', '=', 'a.current_publication_id');
                });
            $query->union($versionQuery);
        }

        $tombstoneQuery = DB::table('data_object_tombstones AS dot')
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
            // Extra columns to match the live-record branches for the UNION.
            ->addSelect([
                DB::raw('NULL AS publication_id'),
                DB::raw('NULL AS current_publication_id'),
                DB::raw('NULL AS version_major'),
                DB::raw('NULL AS version_stage'),
            ])
            ->when(isset($set), function ($query) use ($set) {
                return $query->where('dot.set_spec', '=', $set)
                    ->orWhere('dot.set_spec', 'like', $set . ':%');
            })
            ->when($from, function ($query, $from) {
                return $query->whereDate('dot.date_deleted', '>=', DateTime::createFromFormat('U', $from));
            })
            ->when($until, function ($query, $until) {
                return $query->whereDate('dot.date_deleted', '<=', DateTime::createFromFormat('U', $until));
            })
            ->when($submissionId, function ($query, $submissionId) {
                return $query->where('dot.data_object_id', '=', (int) $submissionId);
            });

        return $query
            ->union($tombstoneQuery)
            ->orderBy(DB::raw($orderBy));
    }
}
