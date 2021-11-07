<?php

/**
 * @file classes/oai/ojs/JournalOAI.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class JournalOAI
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
use PKP\db\DAORegistry;
use PKP\oai\OAI;
use PKP\oai\OAIRepository;
use PKP\oai\OAIResumptionToken;

use PKP\plugins\HookRegistry;

class JournalOAI extends OAI
{
    /** @var Site associated site object */
    public $site;

    /** @var Journal associated journal object */
    public $journal;

    /** @var int|null Journal ID; null if no journal */
    public $journalId;

    /** @var OAIDAO DAO for retrieving OAI records/tokens from database */
    public $dao;


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
     * Return a list of ignorable GET parameters.
     *
     * @return array
     */
    public function getNonPathInfoParams()
    {
        return ['journal', 'page'];
    }

    /**
     * Convert article ID to OAI identifier.
     *
     * @param int $articleId
     *
     * @return string
     */
    public function articleIdToIdentifier($articleId)
    {
        return 'oai:' . $this->config->repositoryId . ':' . 'article/' . $articleId;
    }

    /**
     * Convert OAI identifier to article ID.
     *
     * @param string $identifier
     *
     * @return int|false
     */
    public function identifierToArticleId($identifier)
    {
        $prefix = 'oai:' . $this->config->repositoryId . ':' . 'article/';
        if (strstr($identifier, $prefix)) {
            return (int) str_replace($prefix, '', $identifier);
        } else {
            return false;
        }
    }

    /**
     * Get the journal ID and section ID corresponding to a set specifier.
     *
     * @param null|mixed $journalId
     *
     * @return array
     */
    public function setSpecToSectionId($setSpec, $journalId = null)
    {
        $tmpArray = preg_split('/:/', $setSpec);
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
    public function repositoryInfo()
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
        $info->toolkitURL = 'http://pkp.sfu.ca/ojs/';

        return $info;
    }

    /**
     * @copydoc OAI::validIdentifier()
     */
    public function validIdentifier($identifier)
    {
        return $this->identifierToArticleId($identifier) !== false;
    }

    /**
     * @copydoc OAI::identifierExists()
     */
    public function identifierExists($identifier)
    {
        $recordExists = false;
        $articleId = $this->identifierToArticleId($identifier);
        if ($articleId) {
            $recordExists = $this->dao->recordExists($articleId, [$this->journalId]);
        }
        return $recordExists;
    }

    /**
     * @copydoc OAI::record()
     */
    public function record($identifier)
    {
        $articleId = $this->identifierToArticleId($identifier);
        if ($articleId) {
            $record = $this->dao->getRecord($articleId, [$this->journalId]);
        }
        if (!isset($record)) {
            $record = false;
        }
        return $record;
    }

    /**
     * @copydoc OAI::records()
     */
    public function records($metadataPrefix, $from, $until, $set, $offset, $limit, &$total)
    {
        $records = null;
        if (!HookRegistry::call('JournalOAI::records', [$this, $from, $until, $set, $offset, $limit, &$total, &$records])) {
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
     */
    public function identifiers($metadataPrefix, $from, $until, $set, $offset, $limit, &$total)
    {
        $records = null;
        if (!HookRegistry::call('JournalOAI::identifiers', [$this, $from, $until, $set, $offset, $limit, &$total, &$records])) {
            $sectionId = null;
            if (isset($set)) {
                [$journalId, $sectionId] = $this->setSpecToSectionId($set);
            } else {
                $journalId = $this->journalId;
            }
            $records = $this->dao->getIdentifiers([$journalId, $sectionId], $from, $until, $set, $offset, $limit, $total);
        }
        return $records;
    }

    /**
     * @copydoc OAI::sets()
     */
    public function sets($offset, $limit, &$total)
    {
        $sets = null;
        if (!HookRegistry::call('JournalOAI::sets', [$this, $offset, $limit, &$total, &$sets])) {
            $sets = $this->dao->getJournalSets($this->journalId, $offset, $limit, $total);
        }
        return $sets;
    }

    /**
     * @copydoc OAI::resumptionToken()
     */
    public function resumptionToken($tokenId)
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
    public function saveResumptionToken($offset, $params)
    {
        $token = new OAIResumptionToken(null, $offset, $params, time() + $this->config->tokenLifetime);
        $this->dao->insertToken($token);
        return $token;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\oai\ojs\JournalOAI', '\JournalOAI');
}
