<?php

/**
 * @file classes/plugins/DOIPubIdExportPlugin.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DOIPubIdExportPlugin
 *
 * @ingroup plugins
 *
 * @brief Basis class for DOI XML metadata export plugins
 */

namespace APP\plugins;

use APP\facades\Repo;
use APP\issue\Issue;
use APP\journal\Journal;
use APP\submission\Submission;
use APP\template\TemplateManager;
use PKP\core\PKPString;
use PKP\galley\Galley;
use PKP\submission\PKPSubmission;

// Configuration errors.
define('DOI_EXPORT_CONFIG_ERROR_DOIPREFIX', 0x01);

// The name of the setting used to save the registered DOI.
define('DOI_EXPORT_REGISTERED_DOI', 'registeredDoi');

abstract class DOIPubIdExportPlugin extends PubObjectsExportPlugin
{
    /**
     * @copydoc ImportExportPlugin::display()
     */
    public function display($args, $request)
    {
        switch (array_shift($args)) {
            case 'index':
            case '':
                $templateMgr = TemplateManager::getManager($request);
                $templateMgr->display($this->getTemplateResource('index.tpl'));
                break;
            default:
                parent::display($args, $request);
        }
    }

    /**
     * Get pub ID type
     *
     * @return string
     */
    public function getPubIdType()
    {
        return 'doi';
    }

    /**
     * Get pub ID display type
     *
     * @return string
     */
    public function getPubIdDisplayType()
    {
        return 'DOI';
    }

    /**
     * Mark selected submissions or issues as registered.
     *
     * @param Journal $context
     * @param array $objects Array of published submissions, issues or galleys
     */
    public function markRegistered($context, $objects)
    {
        foreach ($objects as $object) {
            $doiId = $object->getData('doiId');

            if ($doiId != null) {
                Repo::doi()->markRegistered($doiId);
            }
        }
    }

    /**
     * Saving object's DOI to the object's
     * "registeredDoi" setting.
     * We prefix the setting with the plugin's
     * id so that we do not get name clashes
     * when several DOI registration plug-ins
     * are active at the same time.
     *
     * @param Journal $context
     * @param Issue|Submission|Galley $object
     * @param string $testPrefix
     */
    public function saveRegisteredDoi($context, $object, $testPrefix = '10.1234')
    {
        $registeredDoi = $object->getStoredPubId('doi');
        assert(!empty($registeredDoi));
        if ($this->isTestMode($context)) {
            $registeredDoi = PKPString::regexp_replace('#^[^/]+/#', $testPrefix . '/', $registeredDoi);
        }
        $object->setData($this->getPluginSettingsPrefix() . '::' . DOI_EXPORT_REGISTERED_DOI, $registeredDoi);
        $this->updateObject($object);
    }

    /**
     * Get a list of additional setting names that should be stored with the objects.
     *
     * @return array
     */
    protected function _getObjectAdditionalSettings()
    {
        return array_merge(parent::_getObjectAdditionalSettings(), [
            $this->getPluginSettingsPrefix() . '::' . DOI_EXPORT_REGISTERED_DOI
        ]);
    }

    /**
     * Get published submissions with a DOI assigned from submission IDs.
     *
     * @param array $submissionIds
     * @param Journal $context
     *
     * @return array
     */
    public function getPublishedSubmissions($submissionIds, $context)
    {
        $allSubmissionIds = Repo::submission()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByStatus([PKPSubmission::STATUS_PUBLISHED])
            ->getIds()
            ->toArray();
        $validSubmissionIds = array_intersect($allSubmissionIds, $submissionIds);
        $submissions = array_map(function ($submissionId) {
            return Repo::submission()->get($submissionId);
        }, $validSubmissionIds);
        return array_filter($submissions, function ($submission) {
            return $submission->getCurrentPublication()->getDoi() !== null;
        });
    }

    /**
     * Get published issues with a DOI assigned from issue IDs.
     *
     * @param array $issueIds
     * @param Journal $context
     *
     * @return array
     */
    public function getPublishedIssues($issueIds, $context)
    {
        return Repo::issue()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByIssueIds($issueIds)
            ->filterByPublished(true)
            ->filterByHasDois(true)
            ->getMany()
            ->toArray();
    }

    /**
     * Get article galleys with a DOI assigned from galley IDs.
     *
     * @param array $galleyIds
     * @param Journal $context
     *
     * @return array
     */
    public function getArticleGalleys($galleyIds, $context)
    {
        $allGalleyIds = Repo::galley()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->getIds()
            ->toArray();
        $validGalleyIds = array_intersect($allGalleyIds, $galleyIds);
        $galleys = array_map(function ($galleyId) {
            return Repo::galley()->get($galleyId);
        }, $validGalleyIds);
        return array_filter($galleys, function ($galley) {
            return $galley->getDoi() !== null;
        });
    }

    /**
     * @copydoc ImportExportPlugin::executeCLI()
     */
    public function executeCLI($scriptName, &$args)
    {
        return;
    }

    /**
     * @copydoc ImportExportPlugin::supportsCLI()
     */
    public function supportsCLI(): bool
    {
        return false;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\DOIPubIdExportPlugin', '\DOIPubIdExportPlugin');
}
