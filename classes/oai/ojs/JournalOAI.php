<?php

/**
 * @file classes/oai/ojs/JournalOAI.php
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2003-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class JournalOAI
 *
 * @ingroup oai
 *
 * @see OAIDAO
 *
 * @brief OJS-specific OAI interface.
 * Designed to support both a site-wide and journal-specific OAI interface
 * (based on where the request is directed).
 */

namespace APP\oai\ojs;

use APP\core\Application;
use APP\facades\Repo;
use APP\journal\Journal;
use APP\publication\enums\VersionStage;
use PKP\db\DAO;
use PKP\db\DAORegistry;
use PKP\oai\OAI;
use PKP\oai\OAIRecord;
use PKP\oai\OAIRepository;
use PKP\oai\OAIResumptionToken;
use PKP\plugins\Hook;
use PKP\publication\PKPPublication;
use PKP\site\Site;
use PKP\site\VersionDAO;

class JournalOAI extends OAI
{
    public ?Site $site;
    public ?Journal $journal;
    public ?int $journalId;
    public DAO|OAIDAO $dao;

    /**
     * @copydoc OAI::OAI()
     */
    public function __construct($config)
    {
        parent::__construct($config);

        $request = Application::get()->getRequest();
        $this->site = $request->getSite();
        $this->journal = $request->getJournal();
        $this->journalId = isset($this->journal) ? $this->journal->getId() : null;
        $this->dao = DAORegistry::getDAO('OAIDAO');
        $this->dao->setOAI($this);
    }

    /**
     * Convert article ID to OAI identifier.
     *
     * When a version (major) number is given, a version-specific identifier is
     * returned. This exposes older article versions as their own OAI records
     * when DOI versioning is enabled; the current version keeps the bare
     * (unversioned) identifier for backwards compatibility. The version number
     * is the version-of-record major number, which is stable across minor
     * updates within that major.
     */
    public function articleIdToIdentifier(int $articleId, ?int $versionMajor = null): string
    {
        $identifier = 'oai:' . $this->config->repositoryId . ':' . 'article/' . $articleId;
        if ($versionMajor) {
            $identifier .= '/version/' . $versionMajor;
        }
        return $identifier;
    }

    /**
     * Convert OAI identifier to article ID.
     */
    public function identifierToArticleId(string $identifier): int|false
    {
        return $this->identifierToArticleAndVersionMajor($identifier)[0];
    }

    /**
     * Convert OAI identifier to its article ID and, if the identifier is
     * version-specific, the version-of-record major number it refers to.
     *
     * @return array [int|false $articleId, int|null $versionMajor]
     */
    public function identifierToArticleAndVersionMajor(string $identifier): array
    {
        $prefix = 'oai:' . $this->config->repositoryId . ':' . 'article/';
        if (!str_starts_with($identifier, $prefix)) {
            return [false, null];
        }
        $suffix = substr($identifier, strlen($prefix));
        if (!preg_match('#^(\d+)(?:/version/(\d+))?$#', $suffix, $matches)) {
            return [false, null];
        }
        return [(int) $matches[1], isset($matches[2]) ? (int) $matches[2] : null];
    }

    /**
     * Resolve a version-of-record major number to the publication that represents
     * it in OAI: the latest published minor of that major. Returns null when no
     * version is given (bare identifier), leaving the DAO to fall back to the
     * current publication, or when no such published major version exists.
     */
    private function versionMajorToPublicationId(int $articleId, ?int $versionMajor = null): ?int
    {
        if ($versionMajor === null) {
            return null;
        }
        return Repo::publication()->getCollector()
            ->filterBySubmissionIds([$articleId])
            ->filterByVersionStage(VersionStage::VERSION_OF_RECORD->value)
            ->filterByVersionMajor($versionMajor)
            ->filterByStatus([PKPPublication::STATUS_PUBLISHED])
            ->orderByVersion()
            ->getMany()
            ->last()
            ?->getId();
    }

    /**
     * Resolve an OAI identifier to the [articleId, publicationId] pair for a DAO
     * lookup, or null if it can't map to an exposable record.
     *
     * @return array{0: int, 1: ?int}|null
     */
    private function resolveIdentifier(string $identifier): ?array
    {
        [$articleId, $versionMajor] = $this->identifierToArticleAndVersionMajor($identifier);
        if (!$articleId) {
            return null;
        }
        if ($versionMajor === null) {
            return [$articleId, null];
        }
        $publicationId = $this->versionMajorToPublicationId($articleId, $versionMajor);
        return ($publicationId === null) ? null : [$articleId, $publicationId];
    }

    /**
     * Get the journal ID and section ID corresponding to a set specifier.
     */
    public function setSpecToSectionId($setSpec): array
    {
        $tmpArray = explode(':', $setSpec);
        if (count($tmpArray) == 1) {
            [$journalSpec] = $tmpArray;
            $sectionSpec = null;
        } elseif (count($tmpArray) == 2) {
            [$journalSpec, $sectionSpec] = $tmpArray;
        } else {
            return [0, 0];
        }
        return $this->dao->getSetJournalSectionId($journalSpec, $sectionSpec, $this->journalId);
    }


    //
    // OAI interface functions
    //

    /**
     * @copydoc OAI::repositoryInfo()
     */
    public function repositoryInfo(): OAIRepository
    {
        $info = new OAIRepository();

        if (isset($this->journal)) {
            $info->repositoryName = $this->journal->getLocalizedName();
            $info->adminEmail = $this->journal->getData('contactEmail');
        } else {
            $info->repositoryName = $this->site->getLocalizedTitle();
            $info->adminEmail = $this->site->getLocalizedContactEmail();
        }

        $info->sampleIdentifier = $this->articleIdToIdentifier(1);
        $info->earliestDatestamp = $this->dao->getEarliestDatestamp([$this->journalId]);

        $info->toolkitTitle = 'Open Journal Systems';
        $versionDao = DAORegistry::getDAO('VersionDAO'); /** @var VersionDAO $versionDao */
        $currentVersion = $versionDao->getCurrentVersion();
        $info->toolkitVersion = $currentVersion->getVersionString();
        $info->toolkitURL = 'https://pkp.sfu.ca/ojs/';

        return $info;
    }

    /**
     * @copydoc OAI::validIdentifier()
     */
    public function validIdentifier(string $identifier): bool
    {
        return $this->identifierToArticleId($identifier) !== false;
    }

    /**
     * @copydoc OAI::identifierExists()
     */
    public function identifierExists(string $identifier): bool
    {
        $resolved = $this->resolveIdentifier($identifier);
        return $resolved !== null && $this->dao->recordExists($resolved[0], [$this->journalId], $resolved[1]);
    }

    /**
     * @copydoc OAI::record()
     */
    public function record(string $identifier): OAIRecord|false
    {
        $resolved = $this->resolveIdentifier($identifier);
        if ($resolved === null) {
            return false;
        }
        return $this->dao->getRecord($resolved[0], [$this->journalId], $resolved[1]) ?? false;
    }

    /**
     * @copydoc OAI::records()
     *
     * @hook JournalOAI::records [[$this, $from, $until, $set, $offset, $limit, &$total, &$records]]
     */
    public function records(
        string $metadataPrefix,
        ?int $from,
        ?int $until,
        ?string $set,
        int $offset,
        int $limit,
        int &$total
    ): ?array {
        $records = null;
        if (!Hook::call('JournalOAI::records', [$this, $from, $until, $set, $offset, $limit, &$total, &$records])) {
            $sectionId = null;
            if (isset($set)) {
                [$journalId, $sectionId] = $this->setSpecToSectionId($set);
            } else {
                $journalId = $this->journalId;
            }
            $records = $this->dao->getRecords([$journalId, $sectionId], $from, $until, $set, $offset, $limit, $total);
        }
        return $records;
    }

    /**
     * @copydoc OAI::identifiers()
     *
     * @hook JournalOAI::identifiers [[$this, $from, $until, $set, $offset, $limit, &$total, &$records]]
     */
    public function identifiers($metadataPrefix, $from, $until, $set, $offset, $limit, &$total): ?array
    {
        $records = null;
        if (!Hook::call('JournalOAI::identifiers', [$this, $from, $until, $set, $offset, $limit, &$total, &$records])) {
            $sectionId = null;
            if (isset($set)) {
                [$journalId, $sectionId] = $this->setSpecToSectionId($set);
            } else {
                $journalId = $this->journalId;
            }
            $records = $this->dao->getIdentifiers(
                [$journalId, $sectionId],
                $from,
                $until,
                $set,
                $offset,
                $limit,
                $total
            );
        }
        return $records;
    }

    /**
     * @copydoc OAI::sets()
     *
     * @hook JournalOAI::sets [[$this, $offset, $limit, &$total, &$sets]]
     */
    public function sets(int $offset, int $limit, int &$total): ?array
    {
        $sets = null;
        if (!Hook::call('JournalOAI::sets', [$this, $offset, $limit, &$total, &$sets])) {
            $sets = $this->dao->getJournalSets($this->journalId, $offset, $limit, $total);
        }
        return $sets;
    }

    /**
     * @copydoc OAI::resumptionToken()
     */
    public function resumptionToken(string $tokenId): OAIResumptionToken|false
    {
        $this->dao->clearTokens();
        $token = $this->dao->getToken($tokenId);
        if (!isset($token)) {
            $token = false;
        }
        return $token;
    }

    /**
     * @copydoc OAI::saveResumptionToken()
     */
    public function saveResumptionToken(int $offset, array $params): OAIResumptionToken
    {
        $token = new OAIResumptionToken(null, $offset, $params, time() + $this->config->tokenLifetime);
        $this->dao->insertToken($token);
        return $token;
    }
}
