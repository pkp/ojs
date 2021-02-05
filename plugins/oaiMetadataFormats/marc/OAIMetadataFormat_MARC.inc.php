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

		$templateMgr->assign(array(
			'subject' => isset($subjects[$journal->getPrimaryLocale()])?$subjects[$journal->getPrimaryLocale()]:'',
			'abstract' => PKPString::html2text($article->getAbstract($article->getLocale())),
			'language' => AppLocale::get3LetterIsoFromLocale($article->getLocale())
		));

		$plugin = PluginRegistry::getPlugin('oaiMetadataFormats', 'OAIFormatPlugin_MARC');
		return $templateMgr->fetch($plugin->getTemplateResource('record.tpl'));
	}
}


