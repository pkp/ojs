<?php

/**
 * @file plugins/generic/driver/DRIVERPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DRIVERPlugin
 * @ingroup plugins_generic_driver
 *
 * @brief DRIVER plugin class
 */

use APP\facades\Repo;

define('DRIVER_ACCESS_OPEN', 0);
define('DRIVER_ACCESS_CLOSED', 1);
define('DRIVER_ACCESS_EMBARGOED', 2);
define('DRIVER_ACCESS_DELAYED', 3);
define('DRIVER_ACCESS_RESTRICTED', 4);

use PKP\db\DAORegistry;
use PKP\plugins\GenericPlugin;
use PKP\plugins\HookRegistry;

class DRIVERPlugin extends GenericPlugin
{
    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        if ($success && $this->getEnabled($mainContextId)) {
            $this->import('DRIVERDAO');
            $driverDao = new DRIVERDAO();
            DAORegistry::registerDAO('DRIVERDAO', $driverDao);

            // Add DRIVER set to OAI results
            HookRegistry::register('OAIDAO::getJournalSets', [$this, 'sets']);
            HookRegistry::register('JournalOAI::records', [$this, 'recordsOrIdentifiers']);
            HookRegistry::register('JournalOAI::identifiers', [$this, 'recordsOrIdentifiers']);
            HookRegistry::register('OAIDAO::_returnRecordFromRow', [$this, 'addSet']);
            HookRegistry::register('OAIDAO::_returnIdentifierFromRow', [$this, 'addSet']);

            // consider DRIVER article in article tombstones
            HookRegistry::register('ArticleTombstoneManager::insertArticleTombstone', [$this, 'insertDRIVERArticleTombstone']);
        }
        return $success;
    }

    public function getDisplayName()
    {
        return __('plugins.generic.driver.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.driver.description');
    }

    /*
     * OAI interface
     */

    /**
     * Add DRIVER set
     */
    public function sets($hookName, $params)
    {
        $sets = & $params[5];
        array_push($sets, new OAISet('driver', 'Open Access DRIVERset', ''));
        return false;
    }

    /**
     * Get DRIVER records or identifiers
     */
    public function recordsOrIdentifiers($hookName, $params)
    {
        $journalOAI = & $params[0];
        $from = $params[1];
        $until = $params[2];
        $set = $params[3];
        $offset = $params[4];
        $limit = $params[5];
        $total = & $params[6];
        $records = & $params[7];

        $records = [];
        if (isset($set) && $set == 'driver') {
            $driverDao = DAORegistry::getDAO('DRIVERDAO'); /** @var DRIVERDAO $driverDao */
            $driverDao->setOAI($journalOAI);
            if ($hookName == 'JournalOAI::records') {
                $funcName = '_returnRecordFromRow';
            } elseif ($hookName == 'JournalOAI::identifiers') {
                $funcName = '_returnIdentifierFromRow';
            }
            $journalId = $journalOAI->journalId;
            $records = $driverDao->getDRIVERRecordsOrIdentifiers([$journalId, null], $from, $until, $offset, $limit, $total, $funcName);
            return true;
        }
        return false;
    }

    /**
     * Change OAI record or identifier to consider the DRIVER set
     */
    public function addSet($hookName, $params)
    {
        $record = & $params[0];
        $row = $params[1];

        if ($this->isDRIVERRecord($row)) {
            $record->sets[] = 'driver';
        }
        return false;
    }

    /**
     * Consider the DRIVER article in the article tombstone
     */
    public function insertDRIVERArticleTombstone($hookName, $params)
    {
        $articleTombstone = & $params[0];

        if ($this->isDRIVERArticle($articleTombstone->getOAISetObjectId(ASSOC_TYPE_JOURNAL), $articleTombstone->getDataObjectId())) {
            $dataObjectTombstoneSettingsDao = DAORegistry::getDAO('DataObjectTombstoneSettingsDAO'); /** @var DataObjectTombstoneSettingsDAO $dataObjectTombstoneSettingsDao */
            $dataObjectTombstoneSettingsDao->updateSetting($articleTombstone->getId(), 'driver', true, 'bool');
        }
        return false;
    }

    /**
     * Check if it's a DRIVER record.
     *
     * @param array $row Database fields
     *
     * @return bool
     */
    public function isDRIVERRecord($row)
    {
        // if the article is alive
        if (!isset($row['tombstone_id'])) {
            $journalDao = DAORegistry::getDAO('JournalDAO'); /** @var JournalDAO $journalDao */

            $journal = $journalDao->getById($row['journal_id']);
            $submission = Repo::submission()->get($row['submission_id']);
            $publication = $submission->getCurrentPublication();
            $issue = Repo::issue()->get($publication->getData('issueId'));

            // is open access
            $status = '';
            if ($journal->getData('publishingMode') == \APP\journal\Journal::PUBLISHING_MODE_OPEN) {
                $status = DRIVER_ACCESS_OPEN;
            } elseif ($journal->getData('publishingMode') == \APP\journal\Journal::PUBLISHING_MODE_SUBSCRIPTION) {
                if ($issue->getAccessStatus() == 0 || $issue->getAccessStatus() == \APP\issue\Issue::ISSUE_ACCESS_OPEN) {
                    $status = DRIVER_ACCESS_OPEN;
                } elseif ($issue->getAccessStatus() == \APP\issue\Issue::ISSUE_ACCESS_SUBSCRIPTION) {
                    if ($publication->getData('accessStatus') == \APP\submission\Submission::ARTICLE_ACCESS_OPEN) {
                        $status = DRIVER_ACCESS_OPEN;
                    } elseif ($issue->getAccessStatus() == \APP\issue\Issue::ISSUE_ACCESS_SUBSCRIPTION && $issue->getOpenAccessDate() != null) {
                        $status = DRIVER_ACCESS_EMBARGOED;
                    } elseif ($issue->getAccessStatus() == \APP\issue\Issue::ISSUE_ACCESS_SUBSCRIPTION && $issue->getOpenAccessDate() == null) {
                        $status = DRIVER_ACCESS_CLOSED;
                    }
                }
            }
            if ($journal->getData('restrictSiteAccess') == 1 || $journal->getData('restrictArticleAccess') == 1) {
                $status = DRIVER_ACCESS_RESTRICTED;
            }

            if ($status == DRIVER_ACCESS_EMBARGOED && date('Y-m-d') >= date('Y-m-d', strtotime($issue->getOpenAccessDate()))) {
                $status = DRIVER_ACCESS_DELAYED;
            }

            // is there a full text
            $galleys = $submission->getGalleys();
            if (!empty($galleys)) {
                return $status == DRIVER_ACCESS_OPEN;
            }
            return false;
        } else {
            $dataObjectTombstoneSettingsDao = DAORegistry::getDAO('DataObjectTombstoneSettingsDAO'); /** @var DataObjectTombstoneSettingsDAO $dataObjectTombstoneSettingsDao */
            return $dataObjectTombstoneSettingsDao->getSetting($row['tombstone_id'], 'driver');
        }
    }


    /**
     * Check if it's a DRIVER article.
     *
     * @return bool
     */
    public function isDRIVERArticle($journalId, $articleId)
    {
        $journalDao = DAORegistry::getDAO('JournalDAO'); /** @var JournalDAO $journalDao */

        $journal = $journalDao->getById($journalId);
        $submission = Repo::submission()->get($articleId);
        $publication = $submission->getCurrentPublication();
        $issue = Repo::issue()->get($publication->getData('issueId'));

        // is open access
        $status = '';
        if ($journal->getData('publishingMode') == \APP\journal\Journal::PUBLISHING_MODE_OPEN) {
            $status = DRIVER_ACCESS_OPEN;
        } elseif ($journal->getData('publishingMode') == \APP\journal\Journal::PUBLISHING_MODE_SUBSCRIPTION) {
            if ($issue->getAccessStatus() == 0 || $issue->getAccessStatus() == \APP\issue\Issue::ISSUE_ACCESS_OPEN) {
                $status = DRIVER_ACCESS_OPEN;
            } elseif ($issue->getAccessStatus() == \APP\issue\Issue::ISSUE_ACCESS_SUBSCRIPTION) {
                if ($publication->getData('accessStatus') == \APP\submission\Submission::ARTICLE_ACCESS_OPEN) {
                    $status = DRIVER_ACCESS_OPEN;
                } elseif ($issue->getAccessStatus() == \APP\issue\Issue::ISSUE_ACCESS_SUBSCRIPTION && $issue->getOpenAccessDate() != null) {
                    $status = DRIVER_ACCESS_EMBARGOED;
                } elseif ($issue->getAccessStatus() == \APP\issue\Issue::ISSUE_ACCESS_SUBSCRIPTION && $issue->getOpenAccessDate() == null) {
                    $status = DRIVER_ACCESS_CLOSED;
                }
            }
        }
        if ($journal->getData('restrictSiteAccess') == 1 || $journal->getData('restrictArticleAccess') == 1) {
            $status = DRIVER_ACCESS_RESTRICTED;
        }

        if ($status == DRIVER_ACCESS_EMBARGOED && date('Y-m-d') >= date('Y-m-d', strtotime($issue->getOpenAccessDate()))) {
            $status = DRIVER_ACCESS_DELAYED;
        }

        // is there a full text
        $galleys = $submission->getGalleys();
        if (!empty($galleys)) {
            return $status == DRIVER_ACCESS_OPEN;
        }
        return false;
    }
}
