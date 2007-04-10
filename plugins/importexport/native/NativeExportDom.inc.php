<?php

/**
 * NativeExportDom.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Native import/export plugin DOM functions for export
 *
 * $Id$
 */

import('xml.XMLCustomWriter');

class NativeExportDom {
	function &generateIssueDom(&$doc, &$journal, &$issue) {
		$root = &XMLCustomWriter::createElement($doc, 'issue');

		XMLCustomWriter::setAttribute($root, 'published', $issue->getPublished()?'true':'false');
		XMLCustomWriter::setAttribute($root, 'current', $issue->getCurrent()?'true':'false');
		XMLCustomWriter::setAttribute($root, 'public_id', $issue->getPublicIssueId(), false);

		XMLCustomWriter::createChildWithText($doc, $root, 'title', $issue->getTitle());
		XMLCustomWriter::createChildWithText($doc, $root, 'description', $issue->getDescription(), false);
		XMLCustomWriter::createChildWithText($doc, $root, 'volume', $issue->getVolume(), false);
		XMLCustomWriter::createChildWithText($doc, $root, 'number', $issue->getNumber(), false);
		XMLCustomWriter::createChildWithText($doc, $root, 'year', $issue->getYear(), false);

		if ($issue->getShowCoverPage()) {
			$coverNode = &XMLCustomWriter::createElement($doc, 'cover');
			XMLCustomWriter::appendChild($root, $coverNode);
			XMLCustomWriter::createChildWithText($doc, $coverNode, 'caption', $issue->getCoverPageDescription(), false);

			$coverFile = $issue->getFileName();
			if ($coverFile != '') {
				$imageNode = &XMLCustomWriter::createElement($doc, 'image');
				XMLCustomWriter::appendChild($coverNode, $imageNode);
				import('file.PublicFileManager');
				$publicFileManager = &new PublicFileManager();
				$coverPagePath = $publicFileManager->getJournalFilesPath($journal->getJournalId()) . '/';
				$coverPagePath .= $coverFile;
				$embedNode = &XMLCustomWriter::createChildWithText($doc, $imageNode, 'embed', base64_encode($publicFileManager->readFile($coverPagePath)));
				XMLCustomWriter::setAttribute($embedNode, 'filename', $issue->getOriginalFileName());
				XMLCustomWriter::setAttribute($embedNode, 'encoding', 'base64');
				XMLCustomWriter::setAttribute($embedNode, 'mime_type', String::mime_content_type($coverPagePath));
			}
		}

		XMLCustomWriter::createChildWithText($doc, $root, 'date_published', NativeExportDom::formatDate($issue->getDatePublished()), false);

		if (XMLCustomWriter::createChildWithText($doc, $root, 'access_date', NativeExportDom::formatDate($issue->getDatePublished()), false)==null) {
			// This may be an open access issue. Check and flag
			// as necessary.

			if ($issue->getAccessStatus()) {
				$accessNode = &XMLCustomWriter::createElement($doc, 'open_access');
				XMLCustomWriter::appendChild($root, $accessNode);
			}
		}

		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		foreach ($sectionDao->getSectionsForIssue($issue->getIssueId()) as $section) {
			$sectionNode =& NativeExportDom::generateSectionDom($doc, $journal, $issue, $section);
			XMLCustomWriter::appendChild($root, $sectionNode);
			unset($sectionNode);
		}

		return $root;
	}

	function &generateSectionDom(&$doc, &$journal, &$issue, &$section) {
		$root = &XMLCustomWriter::createElement($doc, 'section');

		$altLocale1 = $journal->getSetting('alternateLocale1');
		$altLocale2 = $journal->getSetting('alternateLocale2');
		$locale = $journal->getLocale();

		$titleNode =& XMLCustomWriter::createChildWithText($doc, $root, 'title', $section->getTitle());
		XMLCustomWriter::setAttribute($titleNode, 'locale', $locale);

		if ($titleAlt1 = $section->getTitleAlt1() != '') {
			$titleAlt1Node =& XMLCustomWriter::createChildWithText($doc, $root, 'title', $section->getTitleAlt1(), false);
			if ($titleAlt1Node) XMLCustomWriter::setAttribute($titleAlt1Node, 'locale', $altLocale1);
		}

		if ($titleAlt2 = $section->getTitleAlt2() != '') {
			$titleAlt2Node =& XMLCustomWriter::createChildWithText($doc, $root, 'title', $section->getTitleAlt2(), false);
			if ($titleAlt2Node) XMLCustomWriter::setAttribute($titleAlt1Node, 'locale', $altLocale2);
		}

		$abbrevNode =& XMLCustomWriter::createChildWithText($doc, $root, 'abbrev', $section->getAbbrev(), false);
		if ($abbrevNode) XMLCustomWriter::setAttribute($abbrevNode, 'locale', $locale);

		if ($abbrevAlt1 = $section->getAbbrevAlt1() != '') {
			$abbrevAlt1Node =& XMLCustomWriter::createChildWithText($doc, $root, 'abbrev', $section->getAbbrevAlt1(), false);
			if ($abbrevAlt1Node) XMLCustomWriter::setAttribute($abbrevAlt1Node, 'locale', $altLocale1);
		}

		if ($abbrevAlt2 = $section->getAbbrevAlt2() != '') {
			$abbrevAlt2Node =& XMLCustomWriter::createChildWithText($doc, $root, 'abbrev', $section->getAbbrevAlt2(), false);
			if ($abbrevAlt2Node) XMLCustomWriter::setAttribute($abbrevAlt2Node, 'locale', $altLocale2);
		}

		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		foreach ($publishedArticleDao->getPublishedArticlesBySectionId($section->getSectionId(), $issue->getIssueId()) as $article) {
			$articleNode =& NativeExportDom::generateArticleDom($doc, $journal, $issue, $section, $article);
			XMLCustomWriter::appendChild($root, $articleNode);
			unset($articleNode);
		}
		return $root;
	}

	function &generateArticleDom(&$doc, &$journal, &$issue, &$section, &$article) {
		$root = &XMLCustomWriter::createElement($doc, 'article');

		/* --- Titles and Abstracts --- */
		$titleNode = XMLCustomWriter::createChildWithText($doc, $root, 'title', $article->getTitle());
		XMLCustomWriter::setAttribute($titleNode, 'locale', $journal->getLocale(), false);

		$titleAlt = $article->getTitleAlt1();
		if ($titleAlt) {
			$altLocale = $journal->getSetting('alternateLocale1');
			if ($altLocale) {
				$titleNode = XMLCustomWriter::createChildWithText($doc, $root, 'title', $titleAlt);
				XMLCustomWriter::setAttribute($titleNode, 'locale', $altLocale);
			}
		}

		$titleAlt = $article->getTitleAlt2();
		if ($titleAlt) {
			$altLocale = $journal->getSetting('alternateLocale2');
			if ($altLocale) {
				$titleNode = XMLCustomWriter::createChildWithText($doc, $root, 'title', $titleAlt);
				XMLCustomWriter::setAttribute($titleNode, 'locale', $altLocale);
			}
		}

		if (!$section->getAbstractsDisabled()) {
			$abstractNode = XMLCustomWriter::createChildWithText($doc, $root, 'abstract', $article->getAbstract());
			XMLCustomWriter::setAttribute($abstractNode, 'locale', $journal->getLocale(), false);

			$abstractAlt = $article->getAbstractAlt1();
			if ($abstractAlt) {
				$altLocale = $journal->getSetting('alternateLocale1');
				if ($altLocale) {
					$abstractNode = XMLCustomWriter::createChildWithText($doc, $root, 'abstract', $abstractAlt);
					XMLCustomWriter::setAttribute($abstractNode, 'locale', $altLocale);
				}
			}

			$abstractAlt = $article->getAbstractAlt2();
			if ($abstractAlt) {
				$altLocale = $journal->getSetting('alternateLocale2');
				if ($altLocale) {
					$abstractNode = XMLCustomWriter::createChildWithText($doc, $root, 'abstract', $abstractAlt);
					XMLCustomWriter::setAttribute($abstractNode, 'locale', $altLocale);
				}
			}
		}

		/* --- */

		XMLCustomWriter::createChildWithText($doc, $root, 'date_published', NativeExportDom::formatDate($article->getDatePublished()), false);

		/* --- Authors --- */

		if ($article->getAccessStatus()) {
			$accessNode = &XMLCustomWriter::createElement($doc, 'open_access');
			XMLCustomWriter::appendChild($root, $accessNode);
		}

		foreach ($article->getAuthors() as $author) {
			$authorNode =& NativeExportDom::generateAuthorDom($doc, $journal, $issue, $article, $author);
			XMLCustomWriter::appendChild($root, $authorNode);
			unset($authorNode);
		}

		/* --- Indexing --- */

		$indexingNode = &XMLCustomWriter::createElement($doc, 'indexing');
		$isIndexingNecessary = false;

		if (XMLCustomWriter::createChildWithText($doc, $indexingNode, 'discipline', $article->getDiscipline(), false)!== null) $isIndexingNecessary = true;
		if (XMLCustomWriter::createChildWithText($doc, $indexingNode, 'type', $article->getType(), false)!== null) $isIndexingNecessary = true;
		if (XMLCustomWriter::createChildWithText($doc, $indexingNode, 'subject', $article->getSubject(), false)!== null) $isIndexingNecessary = true;
		if (XMLCustomWriter::createChildWithText($doc, $indexingNode, 'subject_class', $article->getSubjectClass(), false)!== null) $isIndexingNecessary = true;

		$coverageNode = &XMLCustomWriter::createElement($doc, 'coverage');
		$isCoverageNecessary = false;

		if (XMLCustomWriter::createChildWithText($doc, $coverageNode, 'geographical', $article->getCoverageGeo(), false)!== null) $isCoverageNecessary = true;
		if (XMLCustomWriter::createChildWithText($doc, $coverageNode, 'chronological', $article->getCoverageChron(), false)!== null) $isCoverageNecessary = true;
		if (XMLCustomWriter::createChildWithText($doc, $coverageNode, 'sample', $article->getCoverageSample(), false)!== null) $isCoverageNecessary = true;

		if ($isCoverageNecessary) {
			XMLCustomWriter::appendChild($indexingNode, $coverageNode);
			$isIndexingNecessary = true;
		}

		if ($isIndexingNecessary) XMLCustomWriter::appendChild($root, $indexingNode);

		/* --- */

		XMLCustomWriter::createChildWithText($doc, $root, 'pages', $article->getPages(), false);

		/* --- Galleys --- */
		foreach ($article->getGalleys() as $galley) {
			$galleyNode =& NativeExportDom::generateGalleyDom($doc, $journal, $issue, $article, $galley);
			if ($galleyNode !== null) XMLCustomWriter::appendChild($root, $galleyNode);
			unset($galleyNode);
			
		}

		/* --- Supplementary Files --- */
		import('file.ArticleFileManager');
		$articleFileManager = &new ArticleFileManager($article->getArticleId());
		foreach ($article->getSuppFiles() as $suppFile) {
			$suppNode = &XMLCustomWriter::createElement($doc, 'supplemental_file');

			// FIXME: These should be constants!
			switch ($suppFile->getType()) {
				case Locale::translate('author.submit.suppFile.researchInstrument'):
					$suppFileType = 'research_instrument';
					break;
				case Locale::translate('author.submit.suppFile.researchMaterials'):
					$suppFileType = 'research_materials';
					break;
				case Locale::translate('author.submit.suppFile.researchResults'):
					$suppFileType = 'research_results';
					break;
				case Locale::translate('author.submit.suppFile.transcripts'):
					$suppFileType = 'transcripts';
					break;
				case Locale::translate('author.submit.suppFile.dataAnalysis'):
					$suppFileType = 'data_analysis';
					break;
				case Locale::translate('author.submit.suppFile.dataSet'):
					$suppFileType = 'data_set';
					break;
				case Locale::translate('author.submit.suppFile.sourceText'):
					$suppFileType = 'source_text';
					break;
				default:
					$suppFileType = 'other';
					break;
			}

			XMLCustomWriter::setAttribute($suppNode, 'type', $suppFileType);
			XMLCustomWriter::setAttribute($suppNode, 'public_id', $suppFile->getPublicSuppFileId(), false);
			XMLCustomWriter::setAttribute($suppNode, 'language', $suppFile->getLanguage(), false);
			
			XMLCustomWriter::appendChild($root, $suppNode);

			XMLCustomWriter::createChildWithText($doc, $suppNode, 'title', $suppFile->getTitle());
			XMLCustomWriter::createChildWithText($doc, $suppNode, 'creator', $suppFile->getCreator());
			XMLCustomWriter::createChildWithText($doc, $suppNode, 'subject', $suppFile->getSubject());
			if ($suppFileType == 'other') XMLCustomWriter::createChildWithText($doc, $suppNode, 'type_other', $suppFile->getTypeOther());
			XMLCustomWriter::createChildWithText($doc, $suppNode, 'description', $suppFile->getDescription(), false);
			XMLCustomWriter::createChildWithText($doc, $suppNode, 'publisher', $suppFile->getPublisher(), false);
			XMLCustomWriter::createChildWithText($doc, $suppNode, 'sponsor', $suppFile->getSponsor(), false);
			XMLCustomWriter::createChildWithText($doc, $suppNode, 'date_created', NativeExportDom::formatDate($suppFile->getDateCreated()), false);
			XMLCustomWriter::createChildWithText($doc, $suppNode, 'source', $suppFile->getSource(), false);

			$fileNode = &XMLCustomWriter::createElement($doc, 'file');
			XMLCustomWriter::appendChild($suppNode, $fileNode);
			$embedNode = &XMLCustomWriter::createChildWithText($doc, $fileNode, 'embed', base64_encode($articleFileManager->readFile($suppFile->getFileId())));
			XMLCustomWriter::setAttribute($embedNode, 'filename', $suppFile->getOriginalFileName());
			XMLCustomWriter::setAttribute($embedNode, 'encoding', 'base64');
			XMLCustomWriter::setAttribute($embedNode, 'mime_type', $suppFile->getFileType());
			unset($suppNode);
			unset($fileNode);
		}

		return $root;
	}

	function &generateAuthorDom(&$doc, &$journal, &$issue, &$article, &$author) {
		$root = &XMLCustomWriter::createElement($doc, 'author');
		if ($author->getPrimaryContact()) XMLCustomWriter::setAttribute($root, 'primary_contact', 'true');

		XMLCustomWriter::createChildWithText($doc, $root, 'firstname', $author->getFirstName());
		XMLCustomWriter::createChildWithText($doc, $root, 'middlename', $author->getMiddleName(), false);
		XMLCustomWriter::createChildWithText($doc, $root, 'lastname', $author->getLastName());

		XMLCustomWriter::createChildWithText($doc, $root, 'affiliation', $author->getAffiliation(), false);
		XMLCustomWriter::createChildWithText($doc, $root, 'email', $author->getEmail(), false);
		XMLCustomWriter::createChildWithText($doc, $root, 'biography', strip_tags($author->getBiography()), false);

		return $root;
	}

	function &generateGalleyDom(&$doc, &$journal, &$issue, &$article, &$galley) {
		$isHtml = $galley->isHTMLGalley();

		import('file.ArticleFileManager');
		$articleFileManager = &new ArticleFileManager($article->getArticleId());
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');

		$root = &XMLCustomWriter::createElement($doc, $isHtml?'htmlgalley':'galley');

		XMLCustomWriter::createChildWithText($doc, $root, 'label', $galley->getLabel());

		/* --- Galley file --- */
		$fileNode = &XMLCustomWriter::createElement($doc, 'file');
		XMLCustomWriter::appendChild($root, $fileNode);
		$embedNode = &XMLCustomWriter::createChildWithText($doc, $fileNode, 'embed', base64_encode($articleFileManager->readFile($galley->getFileId())));
		$articleFile = &$articleFileDao->getArticleFile($galley->getFileId());
		if (!$articleFile) return $articleFile; // Stupidity check
		XMLCustomWriter::setAttribute($embedNode, 'filename', $articleFile->getOriginalFileName());
		XMLCustomWriter::setAttribute($embedNode, 'encoding', 'base64');
		XMLCustomWriter::setAttribute($embedNode, 'mime_type', $articleFile->getFileType());

		/* --- HTML-specific data: Stylesheet and/or images --- */

		if ($isHtml) {
			$styleFile = $galley->getStyleFile();
			if ($styleFile) {
				$styleNode = &XMLCustomWriter::createElement($doc, 'stylesheet');
				XMLCustomWriter::appendChild($root, $styleNode);
				$embedNode = &XMLCustomWriter::createChildWithText($doc, $styleNode, 'embed', base64_encode($articleFileManager->readFile($styleFile->getFileId())));
				XMLCustomWriter::setAttribute($embedNode, 'filename', $styleFile->getOriginalFileName());
				XMLCustomWriter::setAttribute($embedNode, 'encoding', 'base64');
				XMLCustomWriter::setAttribute($embedNode, 'mime_type', 'text/css');
			}

			foreach ($galley->getImageFiles() as $imageFile) {
				$imageNode = &XMLCustomWriter::createElement($doc, 'image');
				XMLCustomWriter::appendChild($root, $imageNode);
				$embedNode = &XMLCustomWriter::createChildWithText($doc, $imageNode, 'embed', base64_encode($articleFileManager->readFile($imageFile->getFileId())));
				XMLCustomWriter::setAttribute($embedNode, 'filename', $imageFile->getOriginalFileName());
				XMLCustomWriter::setAttribute($embedNode, 'encoding', 'base64');
				XMLCustomWriter::setAttribute($embedNode, 'mime_type', $imageFile->getFileType());
				unset($imageNode);
				unset($embedNode);
			}
		}

		return $root;
	}

	function formatDate($date) {
		if ($date == '') return null;
		return date('Y-m-d', strtotime($date));
	}
}

?>
