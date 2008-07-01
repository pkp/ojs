<?php

/**
 * @defgroup GatewayPlugin
 */
 
/**
 * @file MetsExportDom.inc.php
 *
 * Copyright (c) 2003-2005 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MetsExportDom
 * @ingroup GatewayPlugin
 *
 * @brief MetsExportDom export plugin DOM functions for export
 */

// $Id$


import('xml.XMLCustomWriter');

class MetsExportDom {

	 /**
	 *  creates the METS:structMap element for an issue with multiple issues
	 */
	function generateStructMap(&$doc, &$root, &$journal , &$issues) {
		$structMap = &XMLCustomWriter::createElement($doc, 'METS:structMap');
		XMLCustomWriter::setAttribute($structMap, 'TYPE', 'logical');
		$sDiv = &XMLCustomWriter::createElement($doc, 'METS:div');
		XMLCustomWriter::setAttribute($sDiv, 'TYPE', 'journal');
		XMLCustomWriter::setAttribute($sDiv, 'DMDID', 'J-'.$journal->getJournalId());
		foreach ($issues as $issue) {
			MetsExportDom::generateIssueDiv($doc, $sDiv, $issue);
		}
		XMLCustomWriter::appendChild($structMap, $sDiv);
		XMLCustomWriter::appendChild($root, $structMap);
	}

	function generateIssueDiv(&$doc, &$root, &$issue) {
		$pDiv = &XMLCustomWriter::createElement($doc, 'METS:div');
		XMLCustomWriter::setAttribute($pDiv, 'TYPE', 'issue');
		XMLCustomWriter::setAttribute($pDiv, 'DMDID', 'I-'.$issue->getIssueId());
		$SectionDAO = &DAORegistry::getDAO('SectionDAO');
		$SectionArray = &$SectionDAO->getSectionsForIssue($issue->getIssueId());
		$i = 0;
		while ($i < sizeof($SectionArray)) {
			MetsExportDom::generateSectionDiv($doc, $pDiv, $SectionArray[$i], $issue);
			$i++; 
		}
		XMLCustomWriter::appendChild($root, $pDiv);
	}

	function generateSectionDiv(&$doc, &$root, &$section, &$issue) {
		$pDiv = &XMLCustomWriter::createElement($doc, 'METS:div');
		XMLCustomWriter::setAttribute($pDiv, 'TYPE', 'section');
		XMLCustomWriter::setAttribute($pDiv, 'DMDID', 'S-'.$section->getSectionId());
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticleArray = &$publishedArticleDao->getPublishedArticlesBySectionId($section->getSectionId(),$issue->getIssueId());
		$i = 0;
		while ($i < sizeof($publishedArticleArray)) {
			MetsExportDom::generateArticleDiv($doc, $pDiv, $publishedArticleArray[$i], $issue);
			$i++; 
		}
		XMLCustomWriter::appendChild($root, $pDiv);
	}

	/**
	 *  creates the METS:div element for a submission
	 */
	function generateArticleDiv(&$doc, &$root, &$article, &$issue) {
		$pDiv = &XMLCustomWriter::createElement($doc, 'METS:div');
		XMLCustomWriter::setAttribute($pDiv, 'TYPE', 'article');
		XMLCustomWriter::setAttribute($pDiv, 'DMDID', 'A-'.$article->getArticleId());
		$ArticleGalleyDAO = &DAORegistry::getDAO('ArticleGalleyDAO');
		$i = 0;
		$GalleysArray = &$ArticleGalleyDAO->getGalleysByArticle($article->getArticleId());
		while ($i < sizeof($GalleysArray)) {
			MetsExportDom::generateArticleFileDiv($doc, $pDiv,  $GalleysArray[$i]);
   		 	if($GalleysArray[$i]->isHTMLGalley()){
				$images = $GalleysArray[$i]->getImageFiles();
				foreach ($images as $image) {
					MetsExportDom::generateArticleHtmlGalleyImageFileDiv($doc, $pDiv, $image, $article);
				}
			}
			$i++; 
		}
		$SuppFileDAO = &DAORegistry::getDAO('SuppFileDAO');
		$suppFilesArray = &$SuppFileDAO->getSuppFilesByArticle($article->getArticleId());
		$i = 0;
		while ($i < sizeof($suppFilesArray)) {
			MetsExportDom::generateArticleSuppFilesDiv($doc, $pDiv, $suppFilesArray[$i]);
		$i++; 
		}
		XMLCustomWriter::appendChild($root, $pDiv);
	}

	/**
	*  creates the METS:fptr element for a ArticleGalley
	*/
	function generateArticleFileDiv(&$doc, &$root, $File) {
		$fDiv = &XMLCustomWriter::createElement($doc, 'METS:fptr');
		XMLCustomWriter::setAttribute($fDiv, 'FILEID', 'F'.$File->getFileId().'-A'.$File->getArticleId());
		XMLCustomWriter::appendChild($root, $fDiv);
	}

	function generateArticleHtmlGalleyImageFileDiv(&$doc, &$root, &$imageFile, &$article){
		$fDiv = &XMLCustomWriter::createElement($doc, 'METS:fptr');
		XMLCustomWriter::setAttribute($fDiv, 'FILEID', 'F'.$imageFile->getFileId().'-A'.$article->getArticleId());
		XMLCustomWriter::appendChild($root, $fDiv);
	}
	
	/**
	*  creates the METS:div @TYPE=additional_material for the Supp Files
	*/
	function generateArticleSuppFilesDiv(&$doc, &$root, $SuppFile) {
		$sDiv = &XMLCustomWriter::createElement($doc, 'METS:div');
		XMLCustomWriter::setAttribute($sDiv, 'TYPE', 'additional_material');
		XMLCustomWriter::setAttribute($sDiv, 'DMDID', 'DMD-SF'.$SuppFile->getFileId().'-A'.$SuppFile->getArticleId());
		$fDiv = &XMLCustomWriter::createElement($doc, 'METS:fptr');
		XMLCustomWriter::setAttribute($fDiv, 'FILEID', 'SF'.$SuppFile->getFileId().'-A'.$SuppFile->getArticleId());
		XMLCustomWriter::appendChild($sDiv, $fDiv);
		XMLCustomWriter::appendChild($root, $sDiv);
	}

	/**
	 *  creates the METS:dmdSec element for the Journal
	 */
	function generateJournalDmdSecDom(&$doc, $root, &$journal) {
		$dmdSec = &XMLCustomWriter::createElement($doc, 'METS:dmdSec');
		XMLCustomWriter::setAttribute($dmdSec, 'ID', 'J-'.$journal->getJournalId());
		$mdWrap = &XMLCustomWriter::createElement($doc, 'METS:mdWrap');
		$xmlData = &XMLCustomWriter::createElement($doc, 'METS:xmlData');
		XMLCustomWriter::setAttribute($mdWrap, 'MDTYPE', 'MODS');
		$mods = &XMLCustomWriter::createElement($doc, 'mods:mods');
		XMLCustomWriter::setAttribute($mods, 'xmlns:mods', 'http://www.loc.gov/mods/v3');
		XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', str_replace(' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd', '', $root->getAttribute('xsi:schemaLocation')) . ' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd');
		$titleInfo = &XMLCustomWriter::createElement($doc, 'mods:titleInfo');
		XMLCustomWriter::createChildWithText($doc, $titleInfo, 'mods:title', $journal->getJournalTitle());
		XMLCustomWriter::appendChild($mods, $titleInfo);
		XMLCustomWriter::createChildWithText($doc, $mods, 'mods:genre', 'journal');
		XMLCustomWriter::appendChild($xmlData, $mods);
		XMLCustomWriter::appendChild($dmdSec, $mdWrap);
		XMLCustomWriter::appendChild($mdWrap,$xmlData);
		XMLCustomWriter::appendChild($root, $dmdSec);
	}

	/**
	 *  creates the METS:dmdSec element for an Issue
	 */
	function generateIssueDmdSecDom(&$doc, &$root, &$issue, &$journal) {
		$dmdSec = &XMLCustomWriter::createElement($doc, 'METS:dmdSec');
		XMLCustomWriter::setAttribute($dmdSec, 'ID', 'I-'.$issue->getIssueId());
		$mdWrap = &XMLCustomWriter::createElement($doc, 'METS:mdWrap');
		$xmlData = &XMLCustomWriter::createElement($doc, 'METS:xmlData');
		XMLCustomWriter::setAttribute($mdWrap, 'MDTYPE', 'MODS');
		$mods = &XMLCustomWriter::createElement($doc, 'mods:mods');
		XMLCustomWriter::setAttribute($mods, 'xmlns:mods', 'http://www.loc.gov/mods/v3');
		XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', str_replace(' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd', '', $root->getAttribute('xsi:schemaLocation')) . ' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd');
		$titleInfo = &XMLCustomWriter::createElement($doc, 'mods:titleInfo');
		XMLCustomWriter::createChildWithText($doc, $titleInfo, 'mods:title', $issue->getIssueTitle());
		XMLCustomWriter::appendChild($mods, $titleInfo);

		if($issue->getIssueDescription() != ''){
			$modsAbstract = XMLCustomWriter::createChildWithText($doc, $titleInfo, 'mods:abstract', $issue->getIssueDescription());
			XMLCustomWriter::appendChild($mods, $modsAbstract);
		}

		XMLCustomWriter::createChildWithText($doc, $mods, 'mods:genre', 'issue');
		import('classes.config.Config');
		$base_url = &Config::getVar('general','base_url');
		$url = $base_url.'/index.php/'.$journal->getPath().'/issue/view/'.$issue->getIssueId();
		$modsIdentifier = XMLCustomWriter::createChildWithText($doc, $mods, 'mods:identifier', $url);
		XMLCustomWriter::setAttribute($modsIdentifier, 'type', 'uri');
		$modsOriginInfo = &XMLCustomWriter::createElement($doc, 'mods:originInfo');
		$timeIssued = date(DATE_W3C, strtotime($issue->getDatePublished()));
		$modsDateIssued = XMLCustomWriter::createChildWithText($doc, $modsOriginInfo, 'mods:dateIssued', $timeIssued);
		XMLCustomWriter::appendChild($mods, $modsOriginInfo);
		$modsRelatedItem = &XMLCustomWriter::createElement($doc, 'mods:relatedItem');
		XMLCustomWriter::setAttribute($modsRelatedItem, 'type', 'host');
		$modsTitleInfo = &XMLCustomWriter::createElement($doc, 'mods:titleInfo');
		$modsIdentifier = XMLCustomWriter::createChildWithText($doc, $modsTitleInfo, 'mods:title', $journal->getJournalTitle());
		XMLCustomWriter::appendChild($modsRelatedItem, $modsTitleInfo);
		$url = $base_url.'/index.php/'.$journal->getPath();
		$modsIdentifier = XMLCustomWriter::createChildWithText($doc, $modsRelatedItem, 'mods:identifier', $url);
		XMLCustomWriter::setAttribute($modsIdentifier, 'type', 'uri');
		$modsPart = &XMLCustomWriter::createElement($doc, 'mods:part');
		$modsVolumDetail = &XMLCustomWriter::createElement($doc, 'mods:detail');
		XMLCustomWriter::setAttribute($modsVolumDetail, 'type', 'volume');
		XMLCustomWriter::createChildWithText($doc, $modsVolumDetail, 'mods:number', $issue->getVolume());
		$modsIssueDetail = &XMLCustomWriter::createElement($doc, 'mods:detail');
		XMLCustomWriter::setAttribute($modsIssueDetail, 'type', 'issue');
		XMLCustomWriter::createChildWithText($doc, $modsIssueDetail, 'mods:number', $issue->getNumber());
		XMLCustomWriter::appendChild($modsPart, $modsVolumDetail);
		XMLCustomWriter::appendChild($modsPart, $modsIssueDetail);
		XMLCustomWriter::createChildWithText($doc, $modsPart, 'mods:date', $issue->getYear());
		XMLCustomWriter::appendChild($modsRelatedItem, $modsPart);
		XMLCustomWriter::appendChild($mods, $modsRelatedItem);
		XMLCustomWriter::appendChild($xmlData, $mods);
		XMLCustomWriter::appendChild($dmdSec, $mdWrap);
		XMLCustomWriter::appendChild($mdWrap,$xmlData);
		XMLCustomWriter::appendChild($root, $dmdSec);
		$SectionDAO = &DAORegistry::getDAO('SectionDAO');
		$SectionArray = &$SectionDAO->getSectionsForIssue($issue->getIssueId());
		$i = 0;
		while ($i < sizeof($SectionArray)) {
			MetsExportDom::generateSectionDmdSecDom($doc, $root, $SectionArray[$i], $issue);
			$i++; 
		}
	}

	/**
	 *  creates the METS:dmdSec element for a Section
	 */
	function generateSectionDmdSecDom(&$doc, &$root, &$section, &$issue) {
		$dmdSec = &XMLCustomWriter::createElement($doc, 'METS:dmdSec');
		XMLCustomWriter::setAttribute($dmdSec, 'ID', 'S-'.$section->getSectionId());
		$mdWrap = &XMLCustomWriter::createElement($doc, 'METS:mdWrap');
		$xmlData = &XMLCustomWriter::createElement($doc, 'METS:xmlData');
		XMLCustomWriter::setAttribute($mdWrap, 'MDTYPE', 'MODS');
		$mods = &XMLCustomWriter::createElement($doc, 'mods:mods');
		XMLCustomWriter::setAttribute($mods, 'xmlns:mods', 'http://www.loc.gov/mods/v3');
		XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', str_replace(' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd', '', $root->getAttribute('xsi:schemaLocation')) . ' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd');
		$titleInfo = &XMLCustomWriter::createElement($doc, 'mods:titleInfo');
		XMLCustomWriter::createChildWithText($doc, $titleInfo, 'mods:title', $section->getSectionTitle());
		XMLCustomWriter::appendChild($mods, $titleInfo);
		if($section->getSectionAbbrev() != ''){
			$titleInfoAlt1 = &XMLCustomWriter::createElement($doc, 'mods:titleInfo');
			XMLCustomWriter::createChildWithText($doc, $titleInfoAlt1, 'mods:title', $section->getSectionAbbrev());
			XMLCustomWriter::setAttribute($titleInfoAlt1, 'type', 'abbreviated');
			XMLCustomWriter::appendChild($mods, $titleInfoAlt1);
		}
		XMLCustomWriter::createChildWithText($doc, $mods, 'mods:genre', 'section');
		XMLCustomWriter::appendChild($xmlData, $mods);
		XMLCustomWriter::appendChild($dmdSec, $mdWrap);
		XMLCustomWriter::appendChild($mdWrap,$xmlData);
		XMLCustomWriter::appendChild($root, $dmdSec);
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticleArray = &$publishedArticleDao->getPublishedArticlesBySectionId($section->getSectionId(),$issue->getIssueId());
		$i = 0;
		$i = 0;
		while ($i < sizeof($publishedArticleArray)) {
			MetsExportDom::generateArticleDmdSecDom($doc, $root, $publishedArticleArray[$i], $issue);
			$i++; 
		}
	}

	/**
	 *  creates the METS:dmdSec element for a published Paper
	 */
	function generateArticleDmdSecDom(&$doc, &$root, &$article, &$issue) {
		$dmdSec = &XMLCustomWriter::createElement($doc, 'METS:dmdSec');
		XMLCustomWriter::setAttribute($dmdSec, 'ID', 'A-'.$article->getArticleId());
		$mdWrap = &XMLCustomWriter::createElement($doc, 'METS:mdWrap');
		$xmlData = &XMLCustomWriter::createElement($doc, 'METS:xmlData');
		XMLCustomWriter::setAttribute($mdWrap, 'MDTYPE', 'MODS');
		$mods = &XMLCustomWriter::createElement($doc, 'mods:mods');
		XMLCustomWriter::setAttribute($mods, 'xmlns:mods', 'http://www.loc.gov/mods/v3');
		XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', str_replace(' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd', '', $root->getAttribute('xsi:schemaLocation')) . ' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd');
		$titleInfo = &XMLCustomWriter::createElement($doc, 'mods:titleInfo');
		XMLCustomWriter::createChildWithText($doc, $titleInfo, 'mods:title', $article->getArticleTitle());
		XMLCustomWriter::appendChild($mods, $titleInfo);
// TODO: localized metadata method calls
/*
		if($article->getTitleAlt1() != ''){
			$titleInfoAlt1 = &XMLCustomWriter::createElement($doc, 'mods:titleInfo');
			XMLCustomWriter::createChildWithText($doc, $titleInfoAlt1, 'mods:title', $article->getTitleAlt1());
			XMLCustomWriter::setAttribute($titleInfoAlt1, 'type', 'alternative');
			XMLCustomWriter::appendChild($mods, $titleInfoAlt1);
		}
		if($article->getTitleAlt2() != ''){
			$titleInfoAlt2 = &XMLCustomWriter::createElement($doc, 'mods:titleInfo');
			XMLCustomWriter::createChildWithText($doc, $titleInfoAlt2, 'mods:title', $article->getTitleAlt2());
			XMLCustomWriter::setAttribute($titleInfoAlt2, 'type', 'alternative');
			XMLCustomWriter::appendChild($mods, $titleInfoAlt2);
		}
*/
		if($article->getArticleAbstract() != '')
			XMLCustomWriter::createChildWithText($doc, $mods, 'mods:abstract', $article->getArticleAbstract());
// TODO: localized metadata method calls
/*
		if($article->getAbstractAlt1() != '')
			XMLCustomWriter::createChildWithText($doc, $mods, 'mods:abstract', $article->getAbstractAlt1());
		if($article->getAbstractAlt2() != '')
			XMLCustomWriter::createChildWithText($doc, $mods, 'mods:abstract', $article->getAbstractAlt2());
*/
		$i = 0;
		$authorsArray = &$article->getAuthors();
		while ($i < sizeof($authorsArray)) {
			$PresenterNode =  &MetsExportDom::generateAuthorDom($doc, $authorsArray[$i]);
			XMLCustomWriter::appendChild($mods, $PresenterNode);
			$i++; 
		}
		XMLCustomWriter::createChildWithText($doc, $mods, 'mods:genre', 'article');
		if($issue->getDatePublished() != ''){
			$timeIssued = date(DATE_W3C, strtotime($issue->getDatePublished()));
			$originInfo = &XMLCustomWriter::createElement($doc, 'mods:originInfo');
			$sDate = XMLCustomWriter::createChildWithText($doc, $originInfo, 'mods:dateIssued', $timeIssued);
			XMLCustomWriter::appendChild($mods, $originInfo);
		}
		if($article->getArticleDiscipline() != ''){
			$modsSubject = &XMLCustomWriter::createElement($doc, 'mods:subject');
			$DisciplineArray = explode(";", $article->getArticleDiscipline());
			$i = 0;
			while ($i < sizeof($DisciplineArray)) {
				XMLCustomWriter::createChildWithText($doc, $modsSubject, 'mods:topic', $DisciplineArray[$i] );
				$i++; 
			}
			XMLCustomWriter::appendChild($mods, $modsSubject);
		}
		if($article->getArticleSubject() != ''){
			$modsSubject = &XMLCustomWriter::createElement($doc, 'mods:subject');
			$modsTopic = XMLCustomWriter::createChildWithText($doc, $modsSubject, 'mods:topic', $article->getArticleSubject());
			if($article->getSubjectClass() != '')
				XMLCustomWriter::setAttribute($modsSubject, 'authority', $article->getSubjectClass());
			XMLCustomWriter::appendChild($mods, $modsSubject);
		}
		if($article->getArticleCoverageGeo() != ''){
			$modsSubject = &XMLCustomWriter::createElement($doc, 'mods:subject');
			$coverageArray = explode(";", $article->getArticleCoverageGeo());
			$i = 0;
			while ($i < sizeof($coverageArray)) {
				XMLCustomWriter::createChildWithText($doc, $modsSubject, 'mods:geographic', $coverageArray[$i] );
				$i++; 
			}
			XMLCustomWriter::appendChild($mods, $modsSubject);
		}
		if($article->getArticleCoverageChron() != ''){
			$modsSubject = &XMLCustomWriter::createElement($doc, 'mods:subject');
			$coverageArray = explode(";", $article->getArticleCoverageChron());
			$i = 0;
			while ($i < sizeof($coverageArray)) {
				XMLCustomWriter::createChildWithText($doc, $modsSubject, 'mods:temporal', $coverageArray[$i] );
				$i++; 
			}
			XMLCustomWriter::appendChild($mods, $modsSubject);
		}
		if($article->getArticleType() != ''){
			$modsSubject = &XMLCustomWriter::createElement($doc, 'mods:subject');
			XMLCustomWriter::createChildWithText($doc, $modsSubject, 'mods:genre', $article->getArticleType());
			XMLCustomWriter::appendChild($mods, $modsSubject);
		}
		if($article->getArticleSponsor() != ''){
			$PresenterNode = &XMLCustomWriter::createElement($doc, 'mods:name');
			XMLCustomWriter::setAttribute($PresenterNode, 'type', 'corporate');
			$fNameNode =&XMLCustomWriter::createChildWithText($doc, $PresenterNode, 'mods:namePart', $article->getArticleSponsor());
			$role = &XMLCustomWriter::createElement($doc, 'mods:role');
			$roleTerm =&XMLCustomWriter::createChildWithText($doc, $role, 'mods:roleTerm', 'sponsor');
			XMLCustomWriter::setAttribute($roleTerm, 'type', 'text');
			XMLCustomWriter::appendChild($PresenterNode, $role);
			XMLCustomWriter::appendChild($mods, $PresenterNode);
		}
		if($article->getLanguage() != '')
			XMLCustomWriter::createChildWithText($doc, $mods, 'mods:language', $article->getLanguage());
		XMLCustomWriter::appendChild($xmlData, $mods);
		XMLCustomWriter::appendChild($dmdSec, $mdWrap);
		XMLCustomWriter::appendChild($mdWrap,$xmlData);
		XMLCustomWriter::appendChild($root, $dmdSec);
		$SuppFileDAO = &DAORegistry::getDAO('SuppFileDAO');
		$suppFilesArray = &$SuppFileDAO->getSuppFilesByArticle($article->getArticleId());
		$i = 0;
		while ($i < sizeof($suppFilesArray)) {
			MetsExportDom::generateArticleSuppFilesDmdSecDom($doc, $root, $suppFilesArray[$i]);
			$i++; 
		}
	}

	/**
	 *  creates the METS:dmdSec element for Supplementary Files
	 */
	function generateArticleSuppFilesDmdSecDom(&$doc, &$root, $suppFile) {
		$dmdSec = &XMLCustomWriter::createElement($doc, 'METS:dmdSec');
		XMLCustomWriter::setAttribute($dmdSec, 'ID', 'DMD-SF'.$suppFile->getFileId().'-A'.$suppFile->getArticleId());
		$mdWrap = &XMLCustomWriter::createElement($doc, 'METS:mdWrap');
		$xmlData = &XMLCustomWriter::createElement($doc, 'METS:xmlData');
		XMLCustomWriter::setAttribute($mdWrap, 'MDTYPE', 'MODS');
		$mods = &XMLCustomWriter::createElement($doc, 'mods:mods');
		XMLCustomWriter::setAttribute($mods, 'xmlns:mods', 'http://www.loc.gov/mods/v3');
		XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', str_replace(' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd', '', $root->getAttribute('xsi:schemaLocation')) . ' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd');
		$titleInfo = &XMLCustomWriter::createElement($doc, 'mods:titleInfo');
		XMLCustomWriter::createChildWithText($doc, $titleInfo, 'mods:title', $suppFile->getTitle());
		XMLCustomWriter::appendChild($mods, $titleInfo);
		if($suppFile->getCreator() != ''){
			$creatorNode = &XMLCustomWriter::createElement($doc, 'mods:name');
			XMLCustomWriter::setAttribute($creatorNode, 'type', 'personal');
			$nameNode =&XMLCustomWriter::createChildWithText($doc, $creatorNode, 'mods:namePart', $suppFile->getCreator());
			$role = &XMLCustomWriter::createElement($doc, 'mods:role');
			$roleTerm =&XMLCustomWriter::createChildWithText($doc, $role, 'mods:roleTerm', 'creator');
			XMLCustomWriter::setAttribute($roleTerm, 'type', 'text');
			XMLCustomWriter::appendChild($creatorNode, $role);
			XMLCustomWriter::appendChild($mods, $creatorNode);
		}
		if($suppFile->getDescription() != '')
			XMLCustomWriter::createChildWithText($doc, $mods, 'mods:abstract', $suppFile->getDescription());
		if($suppFile->getDateCreated() != ''){
			$originInfo = &XMLCustomWriter::createElement($doc, 'mods:originInfo');
			$timeIssued = date(DATE_W3C, strtotime($suppFile->getDateCreated()));
			$sDate = XMLCustomWriter::createChildWithText($doc, $originInfo, 'mods:dateCreated', $timeIssued);
			XMLCustomWriter::appendChild($mods, $originInfo);
		}
		XMLCustomWriter::createChildWithText($doc, $mods, 'mods:genre', 'additional material');
		if($suppFile->getType() != '')
			XMLCustomWriter::createChildWithText($doc, $mods, 'mods:genre', $suppFile->getType());
		if($suppFile->getTypeOther() != '')
			XMLCustomWriter::createChildWithText($doc, $mods, 'mods:genre', $suppFile->getTypeOther());
		if($suppFile->getSubject() != ''){
			$subjNode = &XMLCustomWriter::createElement($doc, 'mods:subject');
			XMLCustomWriter::createChildWithText($doc, $subjNode, 'mods:topic', $suppFile->getSubject());
			XMLCustomWriter::appendChild($mods, $subjNode);
		}
		if($suppFile->getSponsor() != ''){
			$PresenterNode = &XMLCustomWriter::createElement($doc, 'mods:name');
			XMLCustomWriter::setAttribute($PresenterNode, 'type', 'corporate');
			$fNameNode =&XMLCustomWriter::createChildWithText($doc, $PresenterNode, 'mods:namePart', $suppFile->getSponsor());
			$role = &XMLCustomWriter::createElement($doc, 'mods:role');
			$roleTerm =&XMLCustomWriter::createChildWithText($doc, $role, 'mods:roleTerm', 'sponsor');
			XMLCustomWriter::setAttribute($roleTerm, 'type', 'text');
			XMLCustomWriter::appendChild($PresenterNode, $role);
			XMLCustomWriter::appendChild($mods, $PresenterNode);
		}
		if($suppFile->getPublisher() != ''){
			$PresenterNode = &XMLCustomWriter::createElement($doc, 'mods:name');
			XMLCustomWriter::setAttribute($PresenterNode, 'type', 'corporate');
			$fNameNode =&XMLCustomWriter::createChildWithText($doc, $PresenterNode, 'mods:namePart', $suppFile->getPublisher());
			$role = &XMLCustomWriter::createElement($doc, 'mods:role');
			$roleTerm =&XMLCustomWriter::createChildWithText($doc, $role, 'mods:roleTerm', 'publisher');
			XMLCustomWriter::setAttribute($roleTerm, 'type', 'text');
			XMLCustomWriter::appendChild($PresenterNode, $role);
			XMLCustomWriter::appendChild($mods, $PresenterNode);
		}
		if($suppFile->getLanguage() != '')
			XMLCustomWriter::createChildWithText($doc, $mods, 'mods:language', $suppFile->getLanguage());
		XMLCustomWriter::appendChild($xmlData, $mods);
		XMLCustomWriter::appendChild($dmdSec, $mdWrap);
		XMLCustomWriter::appendChild($mdWrap,$xmlData);
		XMLCustomWriter::appendChild($root, $dmdSec);
	}

	/**
	 *  finds all files associated with this Issue by going through all Articles
	 *
	*/  
	function generateIssueFileSecDom(&$doc, &$root, &$issue) {
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticleArray = $publishedArticleDao->getPublishedArticles($issue->getIssueId());
		$i = 0;
		while ($i < sizeof($publishedArticleArray)) {
			MetsExportDom::generateArticleFilesDom($doc, $root, $publishedArticleArray[$i], $issue);
			$i++; 
		}
	}

	function generateIssueHtmlGalleyFileSecDom(&$doc, &$root, &$issue) {
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticleArray = $publishedArticleDao->getPublishedArticles($issue->getIssueId());
		$i = 0;
		while ($i < sizeof($publishedArticleArray)) {
			MetsExportDom::generateArticleHtmlGalleyFilesDom($doc, $root, $publishedArticleArray[$i], $issue);
			$i++; 
		}
	}

	/**
	 *  finds all files associated with this published Papers
	*/
	function generateArticleFilesDom(&$doc, $root, $article, &$issue) {
		$ArticleGalleyDAO = &DAORegistry::getDAO('ArticleGalleyDAO');
		$i = 0;
		$GalleysArray = &$ArticleGalleyDAO->getGalleysByArticle($article->getArticleId());
		while ($i < sizeof($GalleysArray)) {
			if(!$GalleysArray[$i]->isHTMLGalley())
				MetsExportDom::generateArticleFileDom($doc, $root, $article, $GalleysArray[$i], null);
			$i++; 
		}
		$SuppFileDAO = &DAORegistry::getDAO('SuppFileDAO');
		$suppFilesArray = &$SuppFileDAO->getSuppFilesByArticle($article->getArticleId());
		$i = 0;
		while ($i < sizeof($suppFilesArray)) {
			MetsExportDom::generateArticleSuppFileDom($doc, $root, $article, $suppFilesArray[$i]);
			$i++; 
		}
	}

	function generateArticleHtmlGalleyFilesDom(&$doc, $root, $article, &$issue) {
		$ArticleGalleyDAO = &DAORegistry::getDAO('ArticleGalleyDAO');
		$i = 0;
		$GalleysArray = &$ArticleGalleyDAO->getGalleysByArticle($article->getArticleId());
		while ($i < sizeof($GalleysArray)) {
			if($GalleysArray[$i]->isHTMLGalley()){
				MetsExportDom::generateArticleFileDom($doc, $root, $article, $GalleysArray[$i], 'html');
				$images = $GalleysArray[$i]->getImageFiles();
				foreach ($images as $image) {
					MetsExportDom::generateArticleHtmlGalleyImageFileDom($doc, $root, $article, $GalleysArray[$i], $image, 'html');
				}
			}
			$i++; 
		}
	}

	function generateArticleHtmlGalleyImageFileDom(&$doc, &$root, $article, $galley, $imageFile, $useAttribute) {
		import('classes.file.PublicFileManager');
		import('classes.file.FileManager');
		$contentWrapper = $this->getSetting($this->journalId, 'contentWrapper');
		$mfile = &XMLCustomWriter::createElement($doc, 'METS:file');
		$filePath  = MetsExportDom::getPublicFilePath($imageFile , '/public/');

		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journal =& $journalDao->getJournal($article->getJournalId());

		$chkmd5return = md5_file($filePath);
		XMLCustomWriter::setAttribute($mfile, 'ID', 'F'.$imageFile->getFileId().'-A'.$article->getArticleId());
		if($useAttribute != null)
			XMLCustomWriter::setAttribute($mfile, 'USE', $useAttribute);
		XMLCustomWriter::setAttribute($mfile, 'SIZE', $imageFile->getFileSize());
		XMLCustomWriter::setAttribute($mfile, 'MIMETYPE', $imageFile->getFileType());
		XMLCustomWriter::setAttribute($mfile, 'OWNERID', $imageFile->getOriginalFileName());
		XMLCustomWriter::setAttribute($mfile, 'CHECKSUM', $chkmd5return);
		XMLCustomWriter::setAttribute($mfile, 'CHECKSUMTYPE', 'MD5');
		if($contentWrapper == 'FContent'){
			$FileContent = &FileManager::readFile($filePath);
			$FContent = &XMLCustomWriter::createElement($doc, 'METS:FContent');
			$fNameNode =&XMLCustomWriter::createChildWithText($doc, $FContent, 'METS:binData',base64_encode($FileContent));
			XMLCustomWriter::appendChild($mfile, $FContent);
		}
		else{
			$fLocat = &XMLCustomWriter::createElement($doc, 'METS:FLocat');
			$fileUrl = Request::url(null, 'article', 'viewFile', array($article->getArticleId(), $galley->getBestGalleyId($journal), $imageFile->getFileId()));
			XMLCustomWriter::setAttribute($fLocat, 'xlink:href', $fileUrl);
			XMLCustomWriter::setAttribute($fLocat, 'LOCTYPE', 'URL');
			XMLCustomWriter::appendChild($mfile, $fLocat);
		}
		XMLCustomWriter::appendChild($root, $mfile);
	}

	/**
	 *  Creates a METS:file for the paperfile 
	 *  checks if METS:FContent or METS:FLocat should be used
	*/
	function generateArticleFileDom(&$doc, &$root, $article, &$galleyFile, $useAttribute) {
		import('classes.file.PublicFileManager');
		import('classes.file.FileManager');
		$contentWrapper = $this->getSetting($this->journalId, 'contentWrapper');
		$mfile = &XMLCustomWriter::createElement($doc, 'METS:file');
		$filePath  = MetsExportDom::getPublicFilePath($galleyFile , '/public/');
		$chkmd5return = md5_file($filePath);
		XMLCustomWriter::setAttribute($mfile, 'ID', 'F'.$galleyFile->getFileId().'-A'.$galleyFile->getArticleId());
		if($useAttribute != null)
			XMLCustomWriter::setAttribute($mfile, 'USE', $useAttribute);
		XMLCustomWriter::setAttribute($mfile, 'SIZE', $galleyFile->getFileSize());
		XMLCustomWriter::setAttribute($mfile, 'MIMETYPE', $galleyFile->getFileType());
		XMLCustomWriter::setAttribute($mfile, 'OWNERID', $galleyFile->getFileName());
		XMLCustomWriter::setAttribute($mfile, 'CHECKSUM', $chkmd5return);
		XMLCustomWriter::setAttribute($mfile, 'CHECKSUMTYPE', 'MD5');
		if($contentWrapper == 'FContent'){
			$FileContent = &FileManager::readFile($filePath);
			$FContent = &XMLCustomWriter::createElement($doc, 'METS:FContent');
			$fNameNode =&XMLCustomWriter::createChildWithText($doc, $FContent, 'METS:binData',base64_encode($FileContent));
			XMLCustomWriter::appendChild($mfile, $FContent);
		}
		else{
			$fLocat = &XMLCustomWriter::createElement($doc, 'METS:FLocat');
			$fileUrl = MetsExportDom::getPublicFileUrl($galleyFile);
			XMLCustomWriter::setAttribute($fLocat, 'xlink:href', $fileUrl);
			XMLCustomWriter::setAttribute($fLocat, 'LOCTYPE', 'URL');
			XMLCustomWriter::appendChild($mfile, $fLocat);
		}
		XMLCustomWriter::appendChild($root, $mfile);
	}

	/**
	 *  Creates a METS:file for the Supplementary File
	 *  checks if METS:FContent or METS:FLocat should be used
	*/
	function generateArticleSuppFileDom(&$doc, &$root, $article, &$SuppFile) {
		import('classes.file.PublicFileManager');
		import('classes.file.FileManager');
		$contentWrapper = $this->getSetting($this->journalId, 'contentWrapper');
		$mfile = &XMLCustomWriter::createElement($doc, 'METS:file');
		$filePath  = MetsExportDom::getPublicFilePath($SuppFile , '/supp/');;
		$chkmd5return = md5_file($filePath);
		XMLCustomWriter::setAttribute($mfile, 'ID', 'SF'.$SuppFile->getFileId().'-A'.$SuppFile->getArticleId());
		XMLCustomWriter::setAttribute($mfile, 'SIZE', $SuppFile->getFileSize());
		XMLCustomWriter::setAttribute($mfile, 'MIMETYPE', $SuppFile->getFileType());
		XMLCustomWriter::setAttribute($mfile, 'OWNERID', $SuppFile->getFileName());
		XMLCustomWriter::setAttribute($mfile, 'CHECKSUM', $chkmd5return);
		XMLCustomWriter::setAttribute($mfile, 'CHECKSUMTYPE', 'MD5');
		if($contentWrapper == 'FContent'){
			$FileContent = &FileManager::readFile($filePath);
			$FContent = &XMLCustomWriter::createElement($doc, 'METS:FContent');
			$fNameNode =&XMLCustomWriter::createChildWithText($doc, $FContent, 'METS:binData',base64_encode($FileContent));
			XMLCustomWriter::appendChild($mfile, $FContent);
		}
		else{
			$fLocat = &XMLCustomWriter::createElement($doc, 'METS:FLocat');
			$fileUrl = MetsExportDom::getPublicSuppFileUrl($SuppFile);
			XMLCustomWriter::setAttribute($fLocat, 'xlink:href', $fileUrl);
			XMLCustomWriter::setAttribute($fLocat, 'LOCTYPE', 'URL');
			XMLCustomWriter::appendChild($mfile, $fLocat);
		}
		XMLCustomWriter::appendChild($root, $mfile);
	}

	/**
	 *  Create mods:name for a presenter
	*/	
	function &generateAuthorDom(&$doc, $author) {
		$PresenterNode = &XMLCustomWriter::createElement($doc, 'mods:name');
		XMLCustomWriter::setAttribute($PresenterNode, 'type', 'personal');
		$fNameNode =&XMLCustomWriter::createChildWithText($doc, $PresenterNode, 'mods:namePart', $author->getFirstName().' '.$author->getMiddleName());
		XMLCustomWriter::setAttribute($fNameNode, 'type', 'given');
		$lNameNode =&XMLCustomWriter::createChildWithText($doc, $PresenterNode, 'mods:namePart', $author->getLastName());
		XMLCustomWriter::setAttribute($lNameNode, 'type', 'family');
		$role = &XMLCustomWriter::createElement($doc, 'mods:role');
		$roleTerm =&XMLCustomWriter::createChildWithText($doc, $role, 'mods:roleTerm', 'author');
		XMLCustomWriter::setAttribute($roleTerm, 'type', 'text');
		XMLCustomWriter::appendChild($PresenterNode, $role);
		return $PresenterNode;
	}

	/**
	 *  Create METS:amdSec for the Conference
	*/
	function createmetsamdSec($doc, &$root, &$journal)
	{
		$amdSec = &XMLCustomWriter::createElement($doc, 'METS:amdSec');
		$techMD = &XMLCustomWriter::createElement($doc, 'METS:techMD');
		XMLCustomWriter::setAttribute($techMD, 'ID', 'A-'.$journal->getJournalId());
		$mdWrap = &XMLCustomWriter::createElement($doc, 'METS:mdWrap');
		XMLCustomWriter::setAttribute($mdWrap, 'MDTYPE', 'PREMIS');
		$xmlData = &XMLCustomWriter::createElement($doc, 'METS:xmlData');
		$pObject = &XMLCustomWriter::createElement($doc, 'premis:object');
		XMLCustomWriter::setAttribute($pObject, 'xmlns:premis', 'http://www.loc.gov/standards/premis/v1');
		XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', str_replace(' http://www.loc.gov/standards/premis/v1 http://www.loc.gov/standards/premis/v1/PREMIS-v1-1.xsd', '', $root->getAttribute('xsi:schemaLocation')) . ' http://www.loc.gov/standards/premis/v1 http://www.loc.gov/standards/premis/v1/PREMIS-v1-1.xsd');
		$objectIdentifier = &XMLCustomWriter::createElement($doc, 'premis:objectIdentifier');
		XMLCustomWriter::createChildWithText($doc, $objectIdentifier, 'premis:objectIdentifierType', 'internal');
		XMLCustomWriter::createChildWithText($doc, $objectIdentifier, 'premis:objectIdentifierValue', 'J-'.$journal->getJournalId());
		XMLCustomWriter::appendChild($pObject, $objectIdentifier);
		$preservationLevel = $this->getSetting($this->journalId, 'preservationLevel');
		if($preservationLevel == ''){
			$preservationLevel = '1';
		}
		XMLCustomWriter::createChildWithText($doc, $pObject, 'premis:preservationLevel', 'level '.$preservationLevel);
		XMLCustomWriter::createChildWithText($doc, $pObject, 'premis:objectCategory', 'Representation');
		XMLCustomWriter::appendChild($xmlData, $pObject);
		XMLCustomWriter::appendChild($mdWrap, $xmlData);
		XMLCustomWriter::appendChild($techMD ,$mdWrap);
		XMLCustomWriter::appendChild($amdSec, $techMD);		
		return $amdSec;
	}

	/**
	 *  Create METS:metsHdr for export
	*/
	function createmetsHdr($doc)
	{
		$root = &XMLCustomWriter::createElement($doc, 'METS:metsHdr');
		XMLCustomWriter::setAttribute($root, 'CREATEDATE', date('c'));
		XMLCustomWriter::setAttribute($root, 'LASTMODDATE', date('c'));
		$agentNode = &XMLCustomWriter::createElement($doc, 'METS:agent');
		XMLCustomWriter::setAttribute($agentNode, 'ROLE', 'DISSEMINATOR');
		XMLCustomWriter::setAttribute($agentNode, 'TYPE', 'ORGANIZATION');
		$organization = $this->getSetting($this->journalId, 'organization');
		if($organization == ''){
			$siteDao = &DAORegistry::getDAO('SiteDAO');
			$site = $siteDao->getSite();
			$organization = $site->getSiteTitle();
		}
		XMLCustomWriter::createChildWithText($doc, $agentNode, 'METS:name', $organization, false);
		XMLCustomWriter::appendChild($root, $agentNode);
		$agentNode2 = &XMLCustomWriter::createElement($doc, 'METS:agent');
		XMLCustomWriter::setAttribute($agentNode2, 'ROLE', 'CREATOR');
		XMLCustomWriter::setAttribute($agentNode2, 'TYPE', 'OTHER');
		XMLCustomWriter::createChildWithText($doc, $agentNode2, 'METS:name', MetsExportDom::getCreatorString(), false);
		XMLCustomWriter::appendChild($root, $agentNode2);
		return $root;
	}

	/**
	 *  Creator is the OJS Sysytem
	*/
	function getCreatorString() {
		$versionDAO = &DAORegistry::getDAO('VersionDAO');
		$cVersion = $versionDAO->getCurrentVersion();
		return sprintf('Open Journal Systems v%d.%d.%d build %d', $cVersion->getMajor(), $cVersion->getMinor(), $cVersion->getRevision(), $cVersion->getBuild());
	}

	/**
	 *  getPublicFilePath had to be added due to problems in the current 
	 *  $PaperFile->getFilePath(); for Galley Files
	*/
	function getPublicFilePath(&$File, $pathComponent) {
		$articleId = $File->getArticleId();
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$article = &$articleDao->getArticle($articleId);
		$journalId = $article->getJournalId();		
		return Config::getVar('files', 'files_dir') . '/journals/' . $journalId .
		'/articles/' . $File->getArticleId() . '/' . $pathComponent . '/' . $File->getFileName();
	}

	/**
	 *  getPublicFileUrl !!!! must be a better way....
	*/
	function getPublicFileUrl(&$File) {
		import('classes.config.Config');
		$base_url = &Config::getVar('general','base_url');
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$article = &$articleDao->getArticle($File->getArticleId());
		$JournalDAO = &DAORegistry::getDAO('JournalDAO');
		$journal = $JournalDAO->getJournal($article->getJournalId());
		$base_url = &Config::getVar('general','base_url');
		$url = $base_url.'/index.php/'.$journal->getPath().'/article/download/'.$File->getArticleId().'/'.$File->getBestGalleyId($journal);
		return $url;
	}

	/**
	 *  getPublicSuppFileUrl !!!! must be a better way....
	*/
	function getPublicSuppFileUrl(&$File) {
		import('classes.config.Config');
		$base_url = &Config::getVar('general','base_url');
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$article = &$articleDao->getArticle($File->getArticleId());
		$JournalDAO = &DAORegistry::getDAO('JournalDAO');
		$journal = $JournalDAO->getJournal($article->getJournalId());
		$base_url = &Config::getVar('general','base_url');
		$url = $base_url.'/index.php/'.$journal->getPath().'/article/downloadSuppFile/'.$File->getArticleId().'/'.$File->getSuppFileId();
		return $url;
	}

}
?>
