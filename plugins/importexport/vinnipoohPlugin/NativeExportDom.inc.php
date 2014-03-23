<?php

/**
 * @file plugins/importexport/vinnipoohPlugin/NativeExportDom.inc.php
 *
 * Copyright (c) 2003-2013 Artem Gusarenko
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NativeExportDom
 * @ingroup plugins_importexport_native
 *
 * @brief Native import/export plugin DOM functions for export
 */

import('lib.pkp.classes.xml.XMLCustomWriter');

class NativeExportDom {
	function &generateIssueDom(&$doc, &$journal, &$issue) {
		$root =& XMLCustomWriter::createElement($doc, 'journal');
		XMLCustomWriter::setAttribute($root, 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance', false);
		XMLCustomWriter::setAttribute($root, 'xsi:noNamespaceSchemaLocation', 'JournalArticulus.xsd', false);
		$opercardNode =& XMLCustomWriter::createElement($doc, 'operCard');
		XMLCustomWriter::appendChild($root, $opercardNode);
		XMLCustomWriter::createChildWithText($doc, $opercardNode, 'operator', 'Articulus_305', false);
		XMLCustomWriter::createChildWithText($doc, $opercardNode, 'pid', '125942', false);
		XMLCustomWriter::createChildWithText($doc, $opercardNode, 'date', NativeExportDom::formatDateIssue($issue->getDatePublished()), false);
		XMLCustomWriter::createChildWithText($doc, $opercardNode, 'cntArticle', $issue->getNumArticles(), false);
		$cntNode =& XMLCustomWriter::createElement($doc, 'cntNode');
		XMLCustomWriter::appendChild($opercardNode, $cntNode);
		XMLCustomWriter::createChildWithText($doc, $opercardNode, 'cs', '0', false);
		NativeExportDom::generatePubId($doc, $root, $issue, $issue);

		XMLCustomWriter::createChildWithText($doc, $root, 'titleid', '8564', false);
		XMLCustomWriter::createChildWithText($doc, $root, 'issn', '1992-6502', false);
		XMLCustomWriter::createChildWithText($doc, $root, 'codeNEB', '19926502', false);
		$journalinfoNode =& XMLCustomWriter::createElement($doc, 'journalInfo');
		XMLCustomWriter::appendChild($root, $journalinfoNode);
		XMLCustomWriter::setAttribute($journalinfoNode, 'lang', 'RUS', false);
		XMLCustomWriter::createChildWithText($doc, $journalinfoNode, 'title', 'Вестник Уфимского государственного авиационного технического университета', false);
		switch (
			(int) $issue->getShowVolume() .
			(int) $issue->getShowNumber() .
			(int) $issue->getShowYear() .
			(int) $issue->getShowTitle()
		) {
			case '1110': $idType = 'num_vol_year'; break;
			case '1010': $idType = 'vol_year'; break;
			case '0010': $idType = 'year'; break;
			case '1000': $idType = 'vol'; break;
			case '0001': $idType = 'title'; break;
			default: $idType = null;
		}
		XMLCustomWriter::setAttribute($root, 'public_id', $issue->getPubId('publisher-id'), false);
		if (is_array($issue->getTitle(null))) foreach ($issue->getTitle(null) as $locale => $title) {
			if ($locale=='ru_RU'){
				$titleNode =& XMLCustomWriter::createChildWithText($doc, $root, 'title', $title, false);
				if ($titleNode) XMLCustomWriter::setAttribute($titleNode, 'lang', 'RUS');
				unset($titleNode);
			}
			if ($locale=='en_US'){
				$titleNode =& XMLCustomWriter::createChildWithText($doc, $root, 'title', $title, false);
				if ($titleNode) XMLCustomWriter::setAttribute($titleNode, 'lang', 'ENG');
				unset($titleNode);
			}
		}

		$issueNode =& XMLCustomWriter::createElement($doc, 'issue');
		$issueNode = XMLCustomWriter::appendChild($root, $issueNode);
		$altNumber = array_map('trim', explode('(', $issue->getNumber()));
		$altNumber1 = rtrim($altNumber[1], ')'); 
		$newNumber = array_map('trim', explode('(', $issue->getNumber()));
		$newNumber1 = rtrim($newNumber[0], ')');
		XMLCustomWriter::createChildWithText($doc, $issueNode, 'volume', $issue->getVolume(), false);
		XMLCustomWriter::createChildWithText($doc, $issueNode, 'number', $newNumber1, false);
		XMLCustomWriter::createChildWithText($doc, $issueNode, 'altNumber', $altNumber1, false);
		$partNode =& XMLCustomWriter::createElement($doc, 'part');
		$part =& XMLCustomWriter::appendChild($issueNode, $partNode);
		XMLCustomWriter::createChildWithText($doc, $issueNode, 'dateUni', $issue->getYear(), false);
		$issTitleNode =& XMLCustomWriter::createElement($doc, 'issTitle');
		$issTitle =& XMLCustomWriter::appendChild($issueNode, $issTitleNode);
		$sectionDao1 =& DAORegistry::getDAO('SectionDAO');
		$publishedArticleDao1 =& DAORegistry::getDAO('PublishedArticleDAO');
		$pagefirst = array();
		$movement=0;
		foreach ($sectionDao1->getSectionsForIssue($issue->getId()) as $section) {
			foreach ($publishedArticleDao1->getPublishedArticlesBySectionId($section->getId(), $issue->getId()) as $article) {
				$pagefirst[$movement] = $article->getPages();
				$movement++;
			}
		}
		reset($pagefirst);
		$fp = current($pagefirst);
		$lp = array_pop($pagefirst);
		$fp = array_map('trim', explode('-',$fp[0]));
		$lp = mb_substr($lp,3, mb_strlen($lp));
		XMLCustomWriter::createChildWithText($doc, $issueNode, 'pages', $fp[0].$lp, false);
		$articlesNode =& XMLCustomWriter::createElement($doc, 'articles');
		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		foreach ($sectionDao->getSectionsForIssue($issue->getId()) as $section) {
			NativeExportDom::generateSectionDom($doc, $journal, $issue, $section, $articlesNode, $issueNode);
			unset($sectionNode);
		}

		return $root;
	}

	function &generateSectionDom(&$doc, &$journal, &$issue, &$section, &$articlesNode, &$issueNode) {
		if (is_array($section->getTitle(null))){
			$sec =& XMLCustomWriter::createElement($doc, 'section');
			XMLCustomWriter::appendChild($articlesNode, $sec);
			foreach ($section->getTitle(null) as $locale => $title) {
				if ($locale=='ru_RU'){
					$titleNode =& XMLCustomWriter::createChildWithText($doc, $sec, 'secTitle', $title, false);
					if ($titleNode) XMLCustomWriter::setAttribute($titleNode, 'lang', 'RUS');
					unset($titleNode);
				}
				if ($locale=='en_US'){
					$titleNode =& XMLCustomWriter::createChildWithText($doc, $sec, 'secTitle', $title, false);
					if ($titleNode) XMLCustomWriter::setAttribute($titleNode, 'lang', 'ENG');
					unset($titleNode);
				}
			}
		}
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		foreach ($publishedArticleDao->getPublishedArticlesBySectionId($section->getId(), $issue->getId()) as $article) {
			$articleNode =& NativeExportDom::generateArticleDom($doc, $journal, $issue, $section, $article, $articlesNode, $issueNode);
			XMLCustomWriter::appendChild($articlesNode, $articleNode);
			unset($articleNode);
		}

		return $root;
	}

	function &generateArticleDom(&$doc, &$journal, &$issue, &$section, &$article, &$articlesNode, &$issueNode) {
		$articleNode =& XMLCustomWriter::createElement($doc, 'article');
		XMLCustomWriter::appendChild($issueNode, $articlesNode);
		XMLCustomWriter::appendChild($articlesNode, $articleNode);
		$root =& $articleNode;
		XMLCustomWriter::createChildWithText($doc, $root, 'pages',$article->getPages(), false);
		foreach ($article->getGalleys() as $galley) {
			XMLCustomWriter::createChildWithText($doc, $root, 'artType', $galley->getLabel(), false);
		}
		
		XMLCustomWriter::setAttribute($root, 'public_id', $article->getPubId('publisher-id'), false);
		$authorsNodes =& XMLCustomWriter::createElement($doc, 'authors');
		$autors = XMLCustomWriter::appendChild($root, $authorsNodes);
		foreach ($article->getAuthors() as $numauthor => $author) {
			$aut =& XMLCustomWriter::createElement($doc, 'author');
			XMLCustomWriter::appendChild($autors, $aut);
			$authorNode =& NativeExportDom::generateAuthorDom($doc, $journal, $issue, $article, $author, $aut);
			$authorNode1 =& NativeExportDom::generateAuthorDom1($doc, $journal, $issue, $article, $author, $aut);
			XMLCustomWriter::setAttribute($aut, 'num', $numauthor+1);
			unset($authorNode);
			unset($authorNode1);
		}
		
		/* --- */
		if (is_array($article->getShowCoverPage(null))) foreach (array_keys($article->getShowCoverPage(null)) as $locale) {
			if ($article->getShowCoverPage($locale)) {
				$coverNode =& XMLCustomWriter::createElement($doc, 'cover');
				XMLCustomWriter::appendChild($root, $coverNode);
				XMLCustomWriter::setAttribute($coverNode, 'lang', $locale);
				XMLCustomWriter::createChildWithText($doc, $coverNode, 'altText', $issue->getCoverPageDescription($locale), false);
				$coverFile = $article->getFileName($locale);
				if ($coverFile != '') {
					$imageNode =& XMLCustomWriter::createElement($doc, 'image');
					XMLCustomWriter::appendChild($coverNode, $imageNode);
					import('classes.file.PublicFileManager');
					$publicFileManager = new PublicFileManager();
					$coverPagePath = $publicFileManager->getJournalFilesPath($journal->getId()) . '/';
					$coverPagePath .= $coverFile;
					$embedNode =& XMLCustomWriter::createChildWithText($doc, $imageNode, 'embed', base64_encode($publicFileManager->readFile($coverPagePath)));
					XMLCustomWriter::setAttribute($embedNode, 'filename', $article->getOriginalFileName($locale));
					XMLCustomWriter::setAttribute($embedNode, 'encoding', 'base64');
					XMLCustomWriter::setAttribute($embedNode, 'mime_type', String::mime_content_type($coverPagePath));
				}
		
				unset($coverNode);
			}
		}
		
		NativeExportDom::generatePubId($doc, $root, $article, $issue);
		$titlesNode =& XMLCustomWriter::createElement($doc, 'artTitles');
		/* --- Titles and Abstracts --- */
		if (is_array($article->getTitle(null))) foreach ($article->getTitle(null) as $locale => $title) {
			if ($locale=='ru_RU'){
				XMLCustomWriter::appendChild($root, $titlesNode);
				$titleNode =& XMLCustomWriter::createChildWithText($doc, $titlesNode, 'artTitle', $title, false);
				if ($titleNode) XMLCustomWriter::setAttribute($titleNode, 'lang', 'RUS');
				unset($titleNode);
			}
			if ($locale=='en_US'){
				XMLCustomWriter::appendChild($root, $titlesNode);
				$titleNode =& XMLCustomWriter::createChildWithText($doc, $titlesNode, 'artTitle', $title, false);
				if ($titleNode) XMLCustomWriter::setAttribute($titleNode, 'lang', 'ENG');
				unset($titleNode);
			}
		}

		$abstractsNode =& XMLCustomWriter::createElement($doc, 'abstracts');
		if (is_array($article->getAbstract(null))) foreach ($article->getAbstract(null) as $locale => $abstract) {
			if ($locale=='ru_RU'){
				XMLCustomWriter::appendChild($root, $abstractsNode);
				$abstractNode =& XMLCustomWriter::createChildWithText($doc, $abstractsNode, 'abstract', $abstract, false);
				if ($abstractNode) XMLCustomWriter::setAttribute($abstractNode, 'lang', 'RUS');
				unset($abstractNode);
			}
			if ($locale=='en_US'){
				XMLCustomWriter::appendChild($root, $abstractsNode);
				$abstractNode =& XMLCustomWriter::createChildWithText($doc, $abstractsNode, 'abstract', $abstract, false);
				if ($abstractNode) XMLCustomWriter::setAttribute($abstractNode, 'lang', 'ENG');
				unset($abstractNode);
			}
		}

		/* --- Keywords --- */
		$keywords =& XMLCustomWriter::createElement($doc, 'keywords');
		XMLCustomWriter::appendChild($root, $keywords);
		if (is_array($article->getSubject(null))) foreach ($article->getSubject(null) as $locale => $keyword) {
			$subjects = array_map('trim', explode(';', $keyword));
				if ($locale=='ru_RU'){
					$kwdGroupsrus =& XMLCustomWriter::createElement($doc, 'kwdGroup');
					XMLCustomWriter::setAttribute($kwdGroupsrus, 'lang', 'RUS');
					XMLCustomWriter::appendChild($keywords, $kwdGroupsrus);
					foreach ($subjects as $keyword) {
						XMLCustomWriter::createChildWithText($doc, $kwdGroupsrus, 'keyword', $keyword, false);
						$isIndexingNecessary = true;
					}
				}
				if ($locale=='en_US'){
					$kwdGroupseng =& XMLCustomWriter::createElement($doc, 'kwdGroup');
					XMLCustomWriter::setAttribute($kwdGroupseng, 'lang', 'ENG');
					XMLCustomWriter::appendChild($keywords, $kwdGroupseng);
					foreach ($subjects as $keyword) {
						XMLCustomWriter::createChildWithText($doc, $kwdGroupseng, 'keyword', $keyword, false);
						$isIndexingNecessary = true;
					}
				}
			unset($subjects);
		}
		
		
		
		/* --- Citations and FullText URL, Gusarenko, 2013, Nov. 20 --- */
		$stroka = $article->getCitations();
		$pattern = '~\s*\r\n\s*~';
		$pattern1 = '/^\d+.\s|\t/';
		//$pattern3 = '/{(.*)}/';
		$pattern3 = '/\[[^(?!Online)][^(?!Electronic resource)][^(?!Электронный ресурс)][^(?!и др.)](.*)\]/';
		$stroka1 = array();
		$stroka1 = preg_split ($pattern, $stroka);
		$stroka1 = preg_replace($pattern1,'',$stroka1);
		$stroka1 = preg_replace($pattern3,'', $stroka1);
		$referencesNode =& XMLCustomWriter::createElement($doc, 'references');
		$references =& XMLCustomWriter::appendChild($root, $referencesNode);
		if ((in_array('<b>ORIGINAL BIBLIOGRAPHY</b>', $stroka1)) or (in_array('<i><b>Original bibliography</b></i>', $stroka1)) or 
				(in_array('<b><i>Original bibliography (In Russian)</i></b>', $stroka1))  or (in_array('<b>Original bibliography</b>', $stroka1))) {
			unset($stroka1[array_search('<b>Original bibliography</b>', $stroka1)]);
			unset($stroka1[array_search('<b><i>Original bibliography (In Russian)</i></b>', $stroka1)]);
			unset($stroka1[array_search('<b>ORIGINAL BIBLIOGRAPHY</b>', $stroka1)]);
			unset($stroka1[array_search('<b>Original bibliography</b>', $stroka1)]);
			unset($stroka1[array_search('<i><b>ORIGINAL BIBLIOGRAPHY</b></i>', $stroka1)]);
			unset($stroka1[array_search('<i><b>Original bibliography</b></i>', $stroka1)]);
		}
		
		if (in_array('<i><b>References (English transliteration)</b></i>', $stroka1)) {
			//Получение номера ключа найденного элемента
			$start = array_search('<i><b>References (English transliteration)</b></i>', $stroka1);
						while(list($key2, $val2) = each($stroka1)){
							if ($key2>=$start){
								unset($stroka1[$key2]);
							}
					}
		}
		
		if (in_array('<i><b>References (English transliterations)</b></i>', $stroka1)) {
			$start1 = array_search('<i><b>References (English transliterations)</b></i>', $stroka1);
			while(list($key3, $val3) = each($stroka1)){
				if ($key3>=$start1){
					unset($stroka1[$key3]);
				}
			}
		}
		
		if (in_array('<b>References (English transliterations)</b>', $stroka1)) {
			$start2 = array_search('<b>References (English transliterations)</b>', $stroka1);
			while(list($key4, $val4) = each($stroka1)){
				if ($key4>=$start2){
					unset($stroka1[$key4]);
				}
			}
		}
		
		if (in_array('<b>References (English Transliterations)</b>', $stroka1)) {
			$start3 = array_search('<b>References (English Transliterations)</b>', $stroka1);
			while(list($key5, $val5) = each($stroka1)){
				if ($key5>=$start3){
					unset($stroka1[$key5]);
				}
			}
		}
		
		if (in_array('<i><b>References (English Transliteration)</b></i>', $stroka1)) {
			$start4 = array_search('<i><b>References (English Transliteration)</b></i>', $stroka1);
			while(list($key6, $val6) = each($stroka1)){
				if ($key6>=$start4){
					unset($stroka1[$key6]);
				}
			}
		}
		
		if (in_array('<b>References (English transliteration)</b>', $stroka1)) {
			$start5 = array_search('<b>References (English transliteration)</b>', $stroka1);
			while(list($key7, $val7) = each($stroka1)){
				if ($key7>=$start5){
					unset($stroka1[$key7]);
				}
			}
		}
		
		if (in_array('<b>References (Ehglish transliteration)</b>', $stroka1)) {
			$start6 = array_search('<b>References (Ehglish transliteration)</b>', $stroka1);
			while(list($key8, $val8) = each($stroka1)){
				if ($key8>=$start6){
					unset($stroka1[$key8]);
				}
			}
		}
		
		if (in_array('<b><i>English transliteranion</i></b>', $stroka1)) {
			$start7 = array_search('<b><i>English transliteranion</i></b>', $stroka1);
			while(list($key9, $val9) = each($stroka1)){
				if ($key9>=$start7){
					unset($stroka1[$key9]);
				}
			}
		}
		
		if (in_array('<i><b>References (English Transliteration) </b></i>', $stroka1)) {
			$start8 = array_search('<i><b>References (English Transliteration) </b></i>', $stroka1);
			while(list($key10, $val10) = each($stroka1)){
				if ($key10>=$start8){
					unset($stroka1[$key10]);
				}
			}
		}
		
		foreach ($stroka1 as $value) {
			XMLCustomWriter::createChildWithText($doc, $references, 'reference', $value, false);
		}
		
		$files =& XMLCustomWriter::createElement($doc, 'files');
		XMLCustomWriter::appendChild($root, $files);
		$fullTextUrl =& XMLCustomWriter::createChildWithText($doc, $files, 'furl', Request::url(null, 'article', 'view', $article->getId()));

		if ($article->getAccessStatus() == ARTICLE_ACCESS_OPEN) {
			$accessNode =& XMLCustomWriter::createElement($doc, 'open_access');
			XMLCustomWriter::appendChild($root, $accessNode);
		}

		/* --- Galleys --- */
		foreach ($article->getGalleys() as $galley) {
			$galleyNode =& NativeExportDom::generateGalleyDom($doc, $journal, $issue, $article, $galley, $files);
			if ($galleyNode !== null) XMLCustomWriter::appendChild($root, $galleyNode);
			unset($galleyNode);

		}
		return $root;
	}

	function &generateAuthorDom(&$doc, &$journal, &$issue, &$article, &$author, &$aut) {
		$individInforus =& XMLCustomWriter::createElement($doc, 'individInfo');
		XMLCustomWriter::appendChild($aut, $individInforus);
		$affiliations = $author->getAffiliation(null);
		if (is_array($affiliations)) foreach ($affiliations as $locale => $affiliation) {
			if ($locale=='ru_RU'){
				XMLCustomWriter::setAttribute($individInforus, 'lang', 'RUS');
				XMLCustomWriter::createChildWithText($doc, $individInforus, 'surname', $author->getLastName());
				XMLCustomWriter::createChildWithText($doc, $individInforus, 'initials', $author->getFirstName().' '.$author->getMiddleName(), false);
			}
		}
		

		$affiliations = $author->getAffiliation(null);
		if (is_array($affiliations)) foreach ($affiliations as $locale => $affiliation) {
			if ($locale=='ru_RU'){
				$affiliationNode =& XMLCustomWriter::createChildWithText($doc, $individInforus, 'orgName', $affiliation, false);
				unset($affiliationNode);
			}
		}
		
		XMLCustomWriter::createChildWithText($doc, $individInforus, 'email', $author->getEmail());
		XMLCustomWriter::createChildWithText($doc, $individInforus, 'url', $author->getUrl(), false);
		if (is_array($author->getCompetingInterests(null))) foreach ($author->getCompetingInterests(null) as $locale => $competingInterests) {
			$competingInterestsNode =& XMLCustomWriter::createChildWithText($doc, $individInforus, 'competing_interests', strip_tags($competingInterests), false);
			if ($competingInterestsNode) XMLCustomWriter::setAttribute($competingInterestsNode, 'lang', $locale);
			unset($competingInterestsNode);
		}
		
		if (is_array($author->getBiography(null))) foreach ($author->getBiography(null) as $locale => $biography) {
			if ($locale=='ru_RU'){
				$biographyNode =& XMLCustomWriter::createChildWithText($doc, $individInforus, 'otherInfo', strip_tags($biography), false);
				unset($biographyNode);
			}
		}
		return $root;
	}
		
		
	function &generateAuthorDom1(&$doc, &$journal, &$issue, &$article, &$author, &$aut) {
		$individInfoeng =& XMLCustomWriter::createElement($doc, 'individInfo');
		XMLCustomWriter::appendChild($aut, $individInfoeng);		
		$affiliations = $author->getAffiliation(null);
		if (is_array($affiliations)) foreach ($affiliations as $locale => $affiliation) {
			if ($locale=='en_US'){
				XMLCustomWriter::setAttribute($individInfoeng, 'lang', 'ENG');
				
				if (is_array($author->getBiography(null))) foreach ($author->getBiography(null) as $locale => $biography) {
					if ($locale=='en_US'){
						$biographyNodefio = strip_tags($biography);
						$biographyNodename = strip_tags($biography);
						$fio = array_map('trim', explode(',', $biographyNodefio));
						$name = array_map('trim', explode(',', $biographyNodename));
						XMLCustomWriter::createChildWithText($doc, $individInfoeng, 'surname', $fio[0]);
						XMLCustomWriter::createChildWithText($doc, $individInfoeng, 'initials', $name[1], false);
					}
				}
			}
		}
		
		$affiliations = $author->getAffiliation(null);
		if (is_array($affiliations)) foreach ($affiliations as $locale => $affiliation) {
			if ($locale=='en_US'){
				$affiliationNode =& XMLCustomWriter::createChildWithText($doc, $individInfoeng, 'orgName', $affiliation, false);
				unset($affiliationNode1);
			}
		}
		
		XMLCustomWriter::createChildWithText($doc, $individInfoeng, 'email', $author->getEmail());
		XMLCustomWriter::createChildWithText($doc, $individInfoeng, 'url', $author->getUrl(), false);
		if (is_array($author->getCompetingInterests(null))) foreach ($author->getCompetingInterests(null) as $locale => $competingInterests) {
			$competingInterestsNode =& XMLCustomWriter::createChildWithText($doc, $individInfoeng, 'competing_interests', strip_tags($competingInterests), false);
			if ($competingInterestsNode) XMLCustomWriter::setAttribute($competingInterestsNode, 'lang', $locale);
			unset($competingInterestsNode);
		}

		if (is_array($author->getBiography(null))) foreach ($author->getBiography(null) as $locale => $biography) {
		 	if ($locale=='en_US'){
		 		$biographyNode =& XMLCustomWriter::createChildWithText($doc, $individInfoeng, 'otherInfo', strip_tags($biography), false);
		 		unset($biographyNode);
		 	}
		}
		return $root;
	}

	function &generateGalleyDom(&$doc, &$journal, &$issue, &$article, &$galley, &$files) {
		$isHtml = $galley->isHTMLGalley();

		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($article->getId());
		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		$articleFile =& $articleFileDao->getArticleFile($galley->getFileId());
		if (!$articleFile) return $articleFile; // Stupidity check
		$fileUrl =& XMLCustomWriter::createChildWithText($doc, $files, 'file', $articleFile->getOriginalFileName());
		return $root;
	}

	function formatDate($date) {
		if ($date == '') return null;
		return date('Y-m-d', strtotime($date));
	}
	
	function formatDateIssue($date) {
		if ($date == '') return null;
		return date('Y-m-d H:i:s', strtotime($date));
	}

	/**
	 * Add ID-nodes to the given node.
	 * @param $doc DOMDocument
	 * @param $node DOMNode
	 * @param $pubObject object
	 * @param $issue Issue
	 */
	function generatePubId(&$doc, &$node, &$pubObject, &$issue) {
		$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true, $issue->getJournalId());
		if (is_array($pubIdPlugins)) foreach ($pubIdPlugins as $pubIdPlugin) {
			if ($issue->getPublished()) {
				$pubId = $pubIdPlugin->getPubId($pubObject);
			} else {
				$pubId = $pubIdPlugin->getPubId($pubObject, true);
			}
			if ($pubId) {
				$pubIdType = $pubIdPlugin->getPubIdType();
				$idNode =& XMLCustomWriter::createChildWithText($doc, $node, 'id', $pubId);
				XMLCustomWriter::setAttribute($idNode, 'type', $pubIdType);
			}
		}
	}
}
?>