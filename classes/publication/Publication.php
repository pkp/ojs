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
     * Stamp the journal's current identity metadata, also for an article in an issue: the identity
     * at the time the article itself is published. The publisher location is taken from the CSL
     * plugin settings if that plugin is enabled, and cleared otherwise.
     */
    public function stampContextIdentity(Context $context): void
    {
        parent::stampContextIdentity($context);
        $this->setData('printIssn', $context->getData('printIssn'));
        $this->setData('onlineIssn', $context->getData('onlineIssn'));
        $this->setData('publisher', $context->getData('publisherInstitution'));

        // Always set, so a re-stamp does not keep an old location when CSL provides none
        $cslPlugin = PluginRegistry::getPlugin('generic', 'citationstylelanguageplugin');
        $publisherLocation = $cslPlugin?->getEnabled($context->getId()) ? $cslPlugin->getSetting($context->getId(), 'publisherLocation') : null;
        $this->setData('publisherLocation', $publisherLocation ?: null);
    }

    /**
     * Inherit the identity of the publication's issue when that issue is published and stamped.
     * Used by the native XML import, so imported back content takes its issue's identity.
     *
     * @return bool Whether an identity was inherited
     */
    public function inheritContextIdentityFromIssue(): bool
    {
        $issue = $this->getIssueId() ? Repo::issue()->get($this->getIssueId()) : null;
        if (!$issue || !$issue->getData('published') || !$issue->hasContextIdentity()) {
            return false;
        }
        $this->setData('contextName', $issue->getData('contextName'));
        $this->setData('contextAbbreviation', $issue->getData('contextAbbreviation'));
        $this->setData('contextPrimaryLocale', $issue->getData('contextPrimaryLocale'));
        $this->setData('printIssn', $issue->getData('printIssn'));
        $this->setData('onlineIssn', $issue->getData('onlineIssn'));
        $this->setData('publisher', $issue->getData('publisher'));
        $this->setData('publisherLocation', $issue->getData('publisherLocation'));
        return true;
    }

    /**
     * @copydoc PKPPublication::clearIdentityMetadata()
     *
     * Also clears the ISSNs and the publisher.
     */
    public function clearIdentityMetadata(): void
    {
        parent::clearIdentityMetadata();
        $this->setData('publisher', null);
        $this->setData('onlineIssn', null);
        $this->setData('printIssn', null);
    }
}
