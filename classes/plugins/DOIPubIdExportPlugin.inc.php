<?php

/**
 * @file classes/plugins/DOIPubIdExportPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DOIPubIdExportPlugin
 * @ingroup plugins
 *
 * @brief Basis class for DOI XML metadata export plugins
 */

namespace APP\plugins;

// Configuration errors.
define('DOI_EXPORT_CONFIG_ERROR_DOIPREFIX', 0x01);

// The name of the setting used to save the registered DOI.
define('DOI_EXPORT_REGISTERED_DOI', 'registeredDoi');

use APP\facades\Repo;
use APP\template\TemplateManager;
use PKP\core\PKPString;
use PKP\db\DAORegistry;

use PKP\plugins\PluginRegistry;
use PKP\submission\PKPSubmission;

abstract class DOIPubIdExportPlugin extends PubObjectsExportPlugin
{
    /**
     * @copydoc ImportExportPlugin::display()
     */
    public function display($args, $request)
    {
        parent::display($args, $request);
        $context = $request->getContext();
        switch (array_shift($args)) {
            case 'index':
            case '':
                $templateMgr = TemplateManager::getManager($request);
                // Check for configuration errors:
                $configurationErrors = $templateMgr->getTemplateVars('configurationErrors');
                // missing DOI prefix
                $doiPrefix = null;
                $pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
                if (isset($pubIdPlugins['doipubidplugin'])) {
                    $doiPlugin = $pubIdPlugins['doipubidplugin'];
                    $doiPrefix = $doiPlugin->getSetting($context->getId(), $doiPlugin->getPrefixFieldName());
                    $templateMgr->assign([
                        'exportArticles' => $doiPlugin->getSetting($context->getId(), 'enablePublicationDoi'),
                        'exportIssues' => $doiPlugin->getSetting($context->getId(), 'enableIssueDoi'),
                        'exportRepresentations' => $doiPlugin->getSetting($context->getId(), 'enableRepresentationDoi'),
                    ]);
                }
                if (empty($doiPrefix)) {
                    $configurationErrors[] = DOI_EXPORT_CONFIG_ERROR_DOIPREFIX;
                }
                $templateMgr->display($this->getTemplateResource('index.tpl'));
                break;
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
     * @param $context Context
     * @param $objects array Array of published submissions, issues or galleys
     */
    public function markRegistered($context, $objects)
    {
        foreach ($objects as $object) {
            $object->setData($this->getDepositStatusSettingName(), EXPORT_STATUS_MARKEDREGISTERED);
            $this->saveRegisteredDoi($context, $object);
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
     * @param $context Context
     * @param $object Issue|Submission|ArticleGalley
     * @param $testPrefix string
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
     * Retrieve all unregistered articles.
     *
     * @param $context Context
     *
     * @return array
     */
    public function getUnregisteredArticles($context)
    {
        // Retrieve all published submissions that have not yet been registered.
        $articles = Repo::submission()->dao->getExportable(
            $context->getId(),
            $this->getPubIdType(),
            null,
            null,
            null,
            $this->getPluginSettingsPrefix() . '::' . DOI_EXPORT_REGISTERED_DOI,
            null,
            null
        );
        return $articles->toArray();
    }

    /**
     * Retrieve all unregistered issues.
     *
     * @param $context Context
     *
     * @return array
     */
    public function getUnregisteredIssues($context)
    {
        // Retrieve all issues that have not yet been registered.
        $issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
        $issuesFactory = $issueDao->getExportable(
            $context->getId(),
            $this->getPubIdType(),
            $this->getPluginSettingsPrefix() . '::' . DOI_EXPORT_REGISTERED_DOI,
            null,
            null
        );
        $issues = $issuesFactory->toArray();
        // Cache issues.
        $cache = $this->getCache();
        foreach ($issues as $issue) {
            $cache->add($issue, null);
            unset($issue);
        }
        return $issues;
    }

    /**
     * Retrieve all unregistered galleys.
     *
     * @param $context Context
     *
     * @return array
     */
    public function getUnregisteredGalleys($context)
    {
        // Retrieve all galleys that have not yet been registered.
        $galleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */
        $galleys = $galleyDao->getExportable(
            $context ? $context->getId() : null,
            $this->getPubIdType(),
            null,
            null,
            null,
            $this->getPluginSettingsPrefix() . '::' . DOI_EXPORT_REGISTERED_DOI,
            null,
            null
        );
        return $galleys->toArray();
    }

    /**
     * Get published submissions with a DOI assigned from submission IDs.
     *
     * @param $submissionIds array
     * @param $context Context
     *
     * @return array
     */
    public function getPublishedSubmissions($submissionIds, $context)
    {
        $submissions = array_map(function ($submissionId) {
            return Repo::submission()->get($submissionId);
        }, $submissionIds);
        return array_filter($submissions, function ($submission) {
            return $submission->getData('status') === PKPSubmission::STATUS_PUBLISHED && !!$submission->getStoredPubId('doi');
        });
    }

    /**
     * Get published issues with a DOI assigned from issue IDs.
     *
     * @param $issueIds array
     * @param $context Context
     *
     * @return array
     */
    public function getPublishedIssues($issueIds, $context)
    {
        $publishedIssues = [];
        $issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
        foreach ($issueIds as $issueId) {
            $publishedIssue = $issueDao->getById($issueId, $context->getId());
            if ($publishedIssue && $publishedIssue->getStoredPubId('doi')) {
                $publishedIssues[] = $publishedIssue;
            }
        }
        return $publishedIssues;
    }

    /**
     * Get article galleys with a DOI assigned from gallley IDs.
     *
     * @param $galleyIds array
     *
     * @return array
     */
    public function getArticleGalleys($galleyIds)
    {
        $galleys = [];
        $articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $articleGalleyDao ArticleGalleyDAO */
        foreach ($galleyIds as $galleyId) {
            $articleGalley = $articleGalleyDao->getById($galleyId);
            if ($articleGalley && $articleGalley->getStoredPubId('doi')) {
                $galleys[] = $articleGalley;
            }
        }
        return $galleys;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\DOIPubIdExportPlugin', '\DOIPubIdExportPlugin');
}
