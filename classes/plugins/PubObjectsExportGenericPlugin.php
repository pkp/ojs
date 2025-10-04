<?php

/**
 * @file classes/plugins/PubObjectsExportGenericPlugin.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under The MIT License. For full terms see the file LICENSE.
 *
 * @class PubObjectsExportGenericPlugin
 *
 * @ingroup plugins
 *
 * @brief Base class for generic part of the export plugins, so that they can listen
 * to publication changes, like version, publish, unpublish
 *
 */

namespace APP\plugins;

use APP\core\Application;
use APP\facades\Repo;
use PKP\context\Context;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\submission\PKPSubmission;

abstract class PubObjectsExportGenericPlugin extends GenericPlugin
{
    protected ?PubObjectsExportPlugin $exportPlugin = null;

    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        if (!parent::register($category, $path, $mainContextId)) {
            return false;
        }

        if (Application::isUnderMaintenance()) {
            return true;
        }

        $this->setExportPlugin();

        $context = Application::get()->getRequest()->getContext();
        if ($context?->getData(Context::SETTING_DOI_VERSIONING)) {
            Hook::add('Publication::version', $this->handlePublicationVersioning(...));
        }
        Hook::add('Publication::publish', $this->handlePublicationPublishing(...));
        Hook::add('Publication::unpublish', $this->handlePublicationUnpublishing(...));

        return true;
    }

    /** Register and get the export plugin */
    abstract protected function setExportPlugin(): void;

    /**
     * Handle publication versioning:
     * If the same DOI is used for all versions: nothing to do -- the settings are saved for submission
     * If different DOIs are used for different versions: Remove existing settings for the new publication, if it is a major version.
     *
     * @return bool Hook processing status
     */
    public function handlePublicationVersioning($hookName, $params): bool
    {
        if (!$this->getEnabled()) {
            return Hook::CONTINUE;
        }

        $newPublication = &$params[0];
        $publication = $params[1];

        $isMajorVersion = $newPublication->getData('versionStage') != $publication->getData('versionStage') ||
            $newPublication->getData('versionMajor') != $publication->getData('versionMajor');

        if (!$isMajorVersion) {
            return false;
        }

        foreach ($this->exportPlugin->getObjectAdditionalSettings() as $fieldName) {
            $newPublication->setData($fieldName, null);
        }
        Repo::publication()->edit($newPublication, []);

        return Hook::CONTINUE;
    }

    /**
     * Handle publishing a version:
     * If the same DOI is used for all versions: mark the registered submission stale if a new current publication has been published.
     * If different DOIs are used for different versions: if a new minor (but already registered) version has been published, mark the version stale
     *
     * @return bool Hook processing status
     */
    public function handlePublicationPublishing($hookName, $params): bool
    {
        if (!$this->getEnabled()) {
            return Hook::CONTINUE;
        }

        $newPublication = &$params[0];
        $submission = $params[2];

        $updatableStatuses = [
            PubObjectsExportPlugin::EXPORT_STATUS_REGISTERED,
            PubObjectsExportPlugin::EXPORT_STATUS_MARKEDREGISTERED
        ];
        $context = Application::get()->getRequest()->getContext();
        if ($context->getData(Context::SETTING_DOI_VERSIONING) &&
            in_array($newPublication->getData($this->exportPlugin->getDepositStatusSettingName()), $updatableStatuses) &&
            $newPublication->getData('versionMinor') != '0') {

            $lastMinorPublications = Repo::publication()->getCollector()
                ->filterBySubmissionIds([$newPublication->getData('submissionId')])
                ->filterByVersionStage($newPublication->getData('versionStage'))
                ->filterByVersionMajor($newPublication->getData('versionMajor'))
                ->filterByStatus([PKPSubmission::STATUS_PUBLISHED])
                ->orderByVersion()
                ->getMany()
                ->last(); // minor versions are sorted ASC, so get only the last

            // if it is the last published minor version
            if ($newPublication->getId() == $lastMinorPublications->getId()) {

                // This will be the case if a new minor version, of a version that is already registered, is published
                // (Because the settings are copied at versioning, this version will also have the status registered).
                // Or if a last minor version was unpublished (its registered status did not change) and then published again.
                $this->exportPlugin->markStale($newPublication);
            }

        } elseif ($submission->getData('currentPublicationId') === $newPublication->getId() &&
            in_array($submission->getData($this->exportPlugin->getDepositStatusSettingName()), $updatableStatuses)) {

            $this->exportPlugin->markStale($submission);
        }
        return Hook::CONTINUE;
    }

    /**
     * Handle unpublishing a version:
     * If the same DOI is used for all versions: mark the registered submission stale only if the current publication was unpublished.
     * If different DOIs are used for different versions: if the latest minor version, that was registered, is unpublished, mark the next previous
     * published minor version stale.
     *
     * @return bool Hook processing status
     */
    public function handlePublicationUnpublishing($hookName, $params): bool
    {
        if (!$this->getEnabled()) {
            return Hook::CONTINUE;
        }

        $newPublication = &$params[0];
        $submission = $params[2];

        $updatableStatuses = [
            PubObjectsExportPlugin::EXPORT_STATUS_REGISTERED,
            PubObjectsExportPlugin::EXPORT_STATUS_MARKEDREGISTERED
        ];
        $context = Application::get()->getRequest()->getContext();
        if ($context->getData(Context::SETTING_DOI_VERSIONING) &&
            in_array($newPublication->getData($this->exportPlugin->getDepositStatusSettingName()), $updatableStatuses) &&
            $newPublication->getData('versionMinor') != '0') {

            $lastMinorPublication = Repo::publication()->getCollector()
                ->filterBySubmissionIds([$newPublication->getData('submissionId')])
                ->filterByVersionStage($newPublication->getData('versionStage'))
                ->filterByVersionMajor($newPublication->getData('versionMajor'))
                ->filterByStatus([PKPSubmission::STATUS_PUBLISHED])
                ->orderByVersion()
                ->getMany()
                ->last(); // minor versions are sorted ASC, so get only the last

            if ($lastMinorPublication && (int) $newPublication->getData('versionMinor') > (int) $lastMinorPublication->getData('versionMinor')) {
                // it was the last published minor version
                // so mark the next previous published version stale
                $this->exportPlugin->markStale($lastMinorPublication);
            }

        } elseif ($submission->getData('currentPublicationId') === $newPublication->getId() &&
            in_array($submission->getData($this->exportPlugin->getDepositStatusSettingName()), $updatableStatuses)) {

            $this->exportPlugin->markStale($submission);
        }
        return Hook::CONTINUE;
    }

}
