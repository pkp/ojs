<?php

/**
 * @file plugins/oaiMetadataFormats/marcxml/OAIMetadataFormat_MARC21.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormat_MARC21
 *
 * @ingroup oai_format
 *
 * @see OAI
 *
 * @brief OAI metadata format class -- MARC21 (MARCXML).
 */

namespace APP\plugins\oaiMetadataFormats\marcxml;

use APP\journal\Journal;
use APP\publication\Publication;
use APP\submission\Submission;
use APP\template\TemplateManager;
use PKP\core\PKPString;
use PKP\i18n\LocaleConversion;
use PKP\oai\OAIMetadataFormat;
use PKP\plugins\PluginRegistry;

class OAIMetadataFormat_MARC21 extends OAIMetadataFormat
{
    /**
     * @see OAIMetadataFormat#toXml
     *
     * @param null|mixed $format
     */
    public function toXml($record, $format = null)
    {
        /** @var Submission $article */
        $article = $record->getData('article');

        /** @var Publication $publication */
        $publication = $article->getCurrentPublication();

        $publicationLocale = $publication->getData('locale');

        /* @var Journal $journal */
        $journal = $record->getData('journal');

        $templateMgr = TemplateManager::getManager();
        $templateMgr->assign([
            'journal' => $journal,
            'article' => $article,
            'publication' => $publication,
            'issue' => $record->getData('issue'),
            'section' => $record->getData('section'),
            'publicationLocale' => $publicationLocale,
        ]);

        $subjects = array_merge_recursive(
            stripAssocArray((array) $publication->getData('discipline')),
            stripAssocArray((array) $publication->getData('subjects'))
        );

        $templateMgr->assign([
            'subject' => $subjects[$publicationLocale] ?? $subjects[$journal->getPrimaryLocale()] ?? '',
            'abstract' => PKPString::html2text($publication->getData('abstract', $publicationLocale)),
            'language' => LocaleConversion::get3LetterIsoFromLocale($publicationLocale)
        ]);

        $plugin = PluginRegistry::getPlugin('oaiMetadataFormats', 'OAIFormatPlugin_MARC21');
        return $templateMgr->fetch($plugin->getTemplateResource('record.tpl'));
    }
}
