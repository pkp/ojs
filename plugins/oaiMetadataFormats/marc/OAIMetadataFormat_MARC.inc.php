<?php

/**
 * @file plugins/oaiMetadataFormats/marc/OAIMetadataFormat_MARC.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormat_MARC
 * @ingroup oai_format
 * @see OAI
 *
 * @brief OAI metadata format class -- MARC.
 */

class OAIMetadataFormat_MARC extends OAIMetadataFormat {
	/**
	 * Constructor.
	 */
	function __construct($prefix, $schema, $namespace) {
		parent::__construct($prefix, $schema, $namespace);
		PKPLocale::requireComponents([LOCALE_COMPONENT_PKP_SUBMISSION]); // submission.copyrightStatement
	}

	/**
	 * @see OAIMetadataFormat#toXml
	 */
	function toXml($record, $format = null) {
		$article = $record->getData('article');
		$journal = $record->getData('journal');

		$templateMgr = TemplateManager::getManager();
		$templateMgr->assign(array(
			'journal' => $journal,
			'article' => $article,
			'issue' => $record->getData('issue'),
			'section' => $record->getData('section')
		));

		$subjects = array_merge_recursive(
			stripAssocArray((array) $article->getDiscipline(null)),
			stripAssocArray((array) $article->getSubject(null))
		);

		// A submission without a locale returns every abstract of every language.
		$locale = $article->getLocale();
		if (!$locale) {
			$abstracts = array_filter((array) $article->getAbstract(null));
			$locale = $abstracts ? key($abstracts) : $journal->getPrimaryLocale();
		}
		$templateMgr->assign(array(
			'subject' => isset($subjects[$journal->getPrimaryLocale()])?$subjects[$journal->getPrimaryLocale()]:'',
			'abstract' => PKPString::html2text((string) $article->getAbstract($locale)),
			// A bare two-letter locale (e.g. "en") is accepted too.
			'language' => AppLocale::get3LetterFrom2LetterIsoLanguage(substr($locale, 0, 2))
		));

		$plugin = PluginRegistry::getPlugin('oaiMetadataFormats', 'OAIFormatPlugin_MARC');
		return $templateMgr->fetch($plugin->getTemplateResource('record.tpl'));
	}
}


