<?php

/**
 * @file classes/publication/Publication.php
 *
 * Copyright (c) 2016-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Publication
 *
 * @ingroup publication
 *
 * @see DAO
 *
 * @brief Class for Publication.
 */

namespace APP\publication;

use APP\core\Application;
use APP\facades\Repo;
use APP\file\PublicFileManager;
use APP\publication\enums\VersionStage;
use PKP\context\Context;
use PKP\plugins\PluginRegistry;
use PKP\publication\PKPPublication;

class Publication extends PKPPublication
{
    use HasContextIdentityMetadata;

    // Case of no issue, published issue and future issue with publish intent
    public const STATUS_READY_TO_PUBLISH = 6;
    // Case of future issue with schedule intent
    public const STATUS_READY_TO_SCHEDULE = 7;

    public const DEFAULT_VERSION_STAGE = VersionStage::VERSION_OF_RECORD;

    /**
     * Get the valid pre-publish statuses if available
     */
    public static function getPrePublishStatuses(): array
    {
        return [
            static::STATUS_READY_TO_PUBLISH,
            static::STATUS_READY_TO_SCHEDULE,
        ];
    }

    /**
     * Get the URL to a localized cover image
     *
     *
     * @return string
     */
    public function getLocalizedCoverImageUrl(int $contextId)
    {
        $coverImage = $this->getLocalizedData('coverImage');

        if (!$coverImage) {
            return '';
        }

        $publicFileManager = new PublicFileManager();

        return join('/', [
            Application::get()->getRequest()->getBaseUrl(),
            $publicFileManager->getContextFilesPath($contextId),
            $coverImage['uploadName'],
        ]);
    }

    /**
     * Retrieves the issue ID associated with the publication.
     */
    public function getIssueId(): ?int
    {
        return $this->getData('issueId');
    }

    /**
     * Sets the issue ID associated with the publication.
     */
    public function setIssueId(?int $issueId): void
    {
        $this->setData('issueId', $issueId);
    }

    /**
     * Set the current journal identity metadata.
     * If CSL plugin is enabled then publisher location from this plugin settings is also set.
     */
    public function stampContextIdentity(?Context $context = null): void
    {
        // Inherit identity from an already-published issue instead of the current context.
        // Falls through to the current context when there is no issue (issue-free journals,
        // or an article published without an issue assignment).
        $issue = $this->getIssueId() ? Repo::issue()->get($this->getIssueId()) : null;
        if ($issue && $issue->getData('published') && $issue->getData('contextName')) {
            $this->setData('contextName', $issue->getData('contextName'));
            $this->setData('contextPrimaryLocale', $issue->getData('contextPrimaryLocale'));
            $this->setData('printIssn', $issue->getData('printIssn'));
            $this->setData('onlineIssn', $issue->getData('onlineIssn'));
            $this->setData('publisher', $issue->getData('publisher'));
            $this->setData('publisherLocation', $issue->getData('publisherLocation'));
            return;
        }

        $context ??= $this->getStampingContext();
        parent::stampContextIdentity($context);
        $this->setData('printIssn', $context->getData('printIssn'));
        $this->setData('onlineIssn', $context->getData('onlineIssn'));
        $this->setData('publisher', $context->getData('publisherInstitution'));

        $cslPlugin = PluginRegistry::getPlugin('generic', 'citationstylelanguageplugin');
        if ($cslPlugin?->getEnabled($context->getId()) && !empty($publisherLocation = $cslPlugin->getSetting($context->getId(), 'publisherLocation'))) {
            $this->setData('publisherLocation', $publisherLocation);
        }
    }

    public function clearIdentityMetadata(): void
    {
        parent::clearIdentityMetadata();
        $this->setData('publisher', null);
        $this->setData('onlineIssn', null);
        $this->setData('printIssn', null);
    }
}
