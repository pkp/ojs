<?php

/**
 * @file classes/orcid/OrcidWork.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2000-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OrcidWork
 *
 * @brief Builds ORCID work object for deposit
 */

namespace APP\orcid;

use APP\core\Application;
use APP\issue\Issue;
use APP\plugins\generic\citationStyleLanguage\CitationStyleLanguagePlugin;
use APP\plugins\PubIdPlugin;
use APP\publication\Publication;
use APP\submission\Submission;
use PKP\context\Context;
use PKP\orcid\PKPOrcidWork;
use PKP\plugins\PluginRegistry;

class OrcidWork extends PKPOrcidWork
{
    public function __construct(
        protected Publication $publication,
        protected Context $context,
        protected array $authors,
        protected ?Issue $issue = null
    ) {
        parent::__construct($this->publication, $this->context, $this->authors);
    }

    /**
     * @inheritdoc
     */
    protected function getAppPubIdExternalIds(PubIdPlugin $plugin): array
    {
        $ids = [];

        $pubIdType = $plugin->getPubIdType();
        $pubId = $this->issue?->getStoredPubId($pubIdType);
        if ($pubId) {
            $ids[] = [
                'external-id-type' => self::PUBID_TO_ORCID_EXT_ID[$pubIdType],
                'external-id-value' => $pubId,
                'external-id-url' => [
                    'value' => $plugin->getResolvingURL($this->context->getId(), $pubId)
                ],
                'external-id-relationship' => 'part-of'
            ];
        }

        return $ids;
    }

    /**
     * @inheritdoc
     */
    protected function getAppDoiExternalIds(): array
    {
        $ids = [];

        $issueDoiObject = $this->issue->getData('doiObject');
        if ($issueDoiObject) {
            $ids[] = [
                'external-id-type' => self::PUBID_TO_ORCID_EXT_ID['doi'],
                'external-id-value' => $issueDoiObject->getData('doi'),
                'external-id-url' => [
                    'value' => $issueDoiObject->getResolvingUrl()
                ],
                'external-id-relationship' => 'part-of'
            ];
        }

        return $ids;
    }

    /**
     * @inheritDoc
     */
    protected function getOrcidPublicationType(): string
    {
        return 'journal-article';
    }

    /**
     * @inheritdoc
     */
    protected function getBibtexCitation(Submission $submission): string
    {
        $request = Application::get()->getRequest();
        try {
            PluginRegistry::loadCategory('generic');
            /** @var CitationStyleLanguagePlugin $citationPlugin */
            $citationPlugin = PluginRegistry::getPlugin('generic', 'citationstylelanguageplugin');
            return trim(
                strip_tags(
                    $citationPlugin->getCitation(
                        $request,
                        $submission,
                        'bibtex',
                        $this->issue,
                        $this->publication
                    )
                )
            );
        } catch (\Exception $exception) {
            return '';
        }
    }
}
