<?php

/**
 * @file plugins/oaiMetadataFormats/marc/OAIMetadataFormat_MARC.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormat_MARC
 * @ingroup oai_format
 * @see OAI
 *
 * @brief OAI metadata format class -- MARC.
 */

class OAIMetadataFormat_MARC extends OAIMetadataFormat {
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

		return $templateMgr->fetch(dirname(__FILE__) . '/record.tpl');
	}
}


