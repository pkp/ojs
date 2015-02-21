<?php

/**
 * @file plugins/oaiMetadataFormats/marcxml/OAIMetadataFormat_MARC21.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormat_MARC21
 * @ingroup oai_format
 * @see OAI
 *
 * @brief OAI metadata format class -- MARC21 (MARCXML).
 */

class OAIMetadataFormat_MARC21 extends OAIMetadataFormat {
	/**
	 * @see OAIMetadataFormat#toXml
	 */
	function toXml(&$record, $format = null) {
		$article =& $record->getData('article');
		$journal =& $record->getData('journal');

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign(array(
			'journal' => $journal,
			'article' => $article,
			'issue' => $record->getData('issue'),
			'section' => $record->getData('section')
		));

		$subjects = array_merge_recursive(
			$this->stripAssocArray((array) $article->getDiscipline(null)),
			$this->stripAssocArray((array) $article->getSubject(null)),
			$this->stripAssocArray((array) $article->getSubjectClass(null))
		);

		$templateMgr->assign(array(
			'subject' => isset($subjects[$journal->getPrimaryLocale()])?$subjects[$journal->getPrimaryLocale()]:'',
			'abstract' => String::html2text($article->getAbstract($article->getLocale())),
			'language' => AppLocale::get3LetterIsoFromLocale($article->getLocale())
		));

		return $templateMgr->fetch(dirname(__FILE__) . '/record.tpl');
	}
}

?>
