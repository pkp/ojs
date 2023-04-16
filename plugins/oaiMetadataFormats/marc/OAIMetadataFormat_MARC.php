<?php

/**
 * @file plugins/oaiMetadataFormats/marc/OAIMetadataFormat_MARC.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormat_MARC
 *
 * @ingroup oai_format
 *
 * @see OAI
 *
 * @brief OAI metadata format class -- MARC.
 */

namespace APP\plugins\oaiMetadataFormats\marc;

use APP\template\TemplateManager;
use PKP\core\PKPString;
use PKP\i18n\LocaleConversion;
use PKP\oai\OAIMetadataFormat;
use PKP\plugins\PluginRegistry;

class OAIMetadataFormat_MARC extends OAIMetadataFormat
{
    /**
     * @see OAIMetadataFormat#toXml
     *
     * @param null|mixed $format
     */
    public function toXml($record, $format = null)
    {
        $article = $record->getData('article');
        $journal = $record->getData('journal');

        $templateMgr = TemplateManager::getManager();
        $templateMgr->assign([
            'journal' => $journal,
            'article' => $article,
            'issue' => $record->getData('issue'),
            'section' => $record->getData('section')
        ]);

        $subjects = array_merge_recursive(
            stripAssocArray((array) $article->getDiscipline(null)),
            stripAssocArray((array) $article->getSubject(null))
        );

        $templateMgr->assign([
            'subject' => isset($subjects[$journal->getPrimaryLocale()]) ? $subjects[$journal->getPrimaryLocale()] : '',
            'abstract' => PKPString::html2text($article->getAbstract($article->getLocale())),
            'language' => LocaleConversion::get3LetterIsoFromLocale($article->getLocale())
        ]);

        $plugin = PluginRegistry::getPlugin('oaiMetadataFormats', 'OAIFormatPlugin_MARC');
        return $templateMgr->fetch($plugin->getTemplateResource('record.tpl'));
    }
}
