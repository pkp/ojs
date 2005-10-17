<?php

/**
 * NativeExportDom.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Native import/export plugin DOM functions for export
 *
 * $Id$
 */

import('xml.XMLWriter');

class NativeExportDom {
	function &generateIssueDom(&$doc, &$journal, &$issue) {
		$root = &XMLWriter::createElement($doc, 'issue');

		XMLWriter::setAttribute($root, 'published', $issue->getPublished()?'true':'false');
		XMLWriter::setAttribute($root, 'current', $issue->getCurrent()?'true':'false');
		XMLWriter::setAttribute($root, 'public_id', $issue->getPublicIssueId(), false);

		XMLWriter::createChildWithText($doc, $root, 'title', $issue->getTitle());
		XMLWriter::createChildWithText($doc, $root, 'description', $issue->getDescription(), false);
		XMLWriter::createChildWithText($doc, $root, 'volume', $issue->getVolume(), false);
		XMLWriter::createChildWithText($doc, $root, 'number', $issue->getNumber(), false);
		XMLWriter::createChildWithText($doc, $root, 'year', $issue->getYear(), false);

		if ($issue->getShowCoverPage()) {
			$coverNode = &XMLWriter::createElement($doc, 'cover');
			XMLWriter::appendChild($root, $coverNode);
			XMLWriter::createChildWithText($doc, $coverNode, 'caption', $issue->getCoverPageDescription(), false);

			$coverFile = $issue->getFileName();
			if ($coverFile != '') {
				$imageNode = &XMLWriter::createElement($doc, 'image');
				XMLWriter::appendChild($coverNode, $imageNode);
				import('file.PublicFileManager');
				$publicFileManager = &new PublicFileManager();
				$coverPagePath = $publicFileManager->getJournalFilesPath($journal->getJournalId()) . '/';
				$coverPagePath .= $coverFile;
				$embedNode = &XMLWriter::createChildWithText($doc, $imageNode, 'embed', base64_encode($publicFileManager->readFile($coverPagePath)));
				XMLWriter::setAttribute($embedNode, 'filename', $issue->getOriginalFileName());
				XMLWriter::setAttribute($embedNode, 'encoding', 'base64');
				XMLWriter::setAttribute($embedNode, 'mime_type', String::mime_content_type($coverPagePath));
			}
		}

		XMLWriter::createChildWithText($doc, $root, 'date_published', NativeExportDom::formatDate($issue->getDatePublished()), false);

		if (XMLWriter::createChildWithText($doc, $root, 'access_date', NativeExportDom::formatDate($issue->getDatePublished()), false)==null) {
			// This may be an open access issue. Check and flag
			// as necessary.

			if ($issue->getAccessStatus()) {
				$accessNode = &XMLWriter::createElement($doc, 'open_access');
				XMLWriter::appendChild($root, $accessNode);
			}
		}

		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		foreach ($sectionDao->getSectionsForIssue($issue->getIssueId()) as $section) {
			$sectionNode = NativeExportDom::generateSectionDom($doc, $journal, $issue, $section);
			XMLWriter::appendChild($root, $sectionNode);
		}

		return $root;
	}

	function &generateSectionDom(&$doc, &$journal, &$issue, &$section) {
		$root = &XMLWriter::createElement($doc, 'section');
		XMLWriter::createChildWithText($doc, $root, 'title', $section->getTitle());
		XMLWriter::createChildWithText($doc, $root, 'abbrev', $section->getAbbrev(), false);

		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		foreach ($publishedArticleDao->getPublishedArticlesBySectionId($section->getSectionId(), $issue->getIssueId()) as $article) {
			$articleNode = NativeExportDom::generateArticleDom($doc, $journal, $issue, $section, $article);
			XMLWriter::appendChild($root, $articleNode);
		}
		return $root;
	}

	function &generateArticleDom(&$doc, &$journal, &$issue, &$section, &$article) {
		$root = &XMLWriter::createElement($doc, 'article');

		/* --- Titles and Abstracts --- */
		$titleNode = XMLWriter::createChildWithText($doc, $root, 'title', $article->getTitle());
		XMLWriter::setAttribute($titleNode, 'locale', $journal->getLocale(), false);

		$titleAlt = $article->getTitleAlt1();
		if ($titleAlt) {
			$altLocale = $journal->getSetting('alternateLocale1');
			if ($altLocale) {
				$titleNode = XMLWriter::createChildWithText($doc, $root, 'title', $titleAlt);
				XMLWriter::setAttribute($titleNode, 'locale', $altLocale);
			}
		}

		$titleAlt = $article->getTitleAlt2();
		if ($titleAlt) {
			$altLocale = $journal->getSetting('alternateLocale2');
			if ($altLocale) {
				$titleNode = XMLWriter::createChildWithText($doc, $root, 'title', $titleAlt);
				XMLWriter::setAttribute($titleNode, 'locale', $altLocale);
			}
		}

		if (!$section->getAbstractsDisabled()) {
			$abstractNode = XMLWriter::createChildWithText($doc, $root, 'abstract', $article->getAbstract());
			XMLWriter::setAttribute($abstractNode, 'locale', $journal->getLocale(), false);

			$abstractAlt = $article->getAbstractAlt1();
			if ($abstractAlt) {
				$altLocale = $journal->getSetting('alternateLocale1');
				if ($altLocale) {
					$abstractNode = XMLWriter::createChildWithText($doc, $root, 'abstract', $abstractAlt);
					XMLWriter::setAttribute($abstractNode, 'locale', $altLocale);
				}
			}

			$abstractAlt = $article->getAbstractAlt2();
			if ($abstractAlt) {
				$altLocale = $journal->getSetting('alternateLocale2');
				if ($altLocale) {
					$abstractNode = XMLWriter::createChildWithText($doc, $root, 'abstract', $abstractAlt);
					XMLWriter::setAttribute($abstractNode, 'locale', $altLocale);
				}
			}
		}

		/* --- */

		XMLWriter::createChildWithText($doc, $root, 'date_published', NativeExportDom::formatDate($article->getDatePublished()), false);

		/* --- Authors --- */

		if ($article->getAccessStatus()) {
			$accessNode = &XMLWriter::createElement($doc, 'open_access');
			XMLWriter::appendChild($root, $accessNode);
		}

		foreach ($article->getAuthors() as $author) {
			$authorNode =& NativeExportDom::generateAuthorDom($doc, $journal, $issue, $article, $author);
			XMLWriter::appendChild($root, $authorNode);
			
		}

		/* --- Indexing --- */

		$indexingNode = &XMLWriter::createElement($doc, 'indexing');
		$isIndexingNecessary = false;

		if (XMLWriter::createChildWithText($doc, $indexingNode, 'discipline', $article->getDiscipline(), false)!== null) $isIndexingNecessary = true;
		if (XMLWriter::createChildWithText($doc, $indexingNode, 'subject_class', $article->getSubjectClass(), false)!== null) $isIndexingNecessary = true;
		if (XMLWriter::createChildWithText($doc, $indexingNode, 'subject', $article->getSubject(), false)!== null) $isIndexingNecessary = true;

		$coverageNode = &XMLWriter::createElement($doc, 'coverage');
		$isCoverageNecessary = false;

		if (XMLWriter::createChildWithText($doc, $coverageNode, 'geographical', $article->getCoverageGeo(), false)!== null) $isCoverageNecessary = true;
		if (XMLWriter::createChildWithText($doc, $coverageNode, 'chronological', $article->getCoverageChron(), false)!== null) $isCoverageNecessary = true;
		if (XMLWriter::createChildWithText($doc, $coverageNode, 'sample', $article->getCoverageSample(), false)!== null) $isCoverageNecessary = true;

		if ($isCoverageNecessary) {
			XMLWriter::appendChild($indexingNode, $coverageNode);
			$isIndexingNecessary = true;
		}

		if ($isIndexingNecessary) XMLWriter::appendChild($root, $indexingNode);

		/* --- */

		XMLWriter::createChildWithText($doc, $root, 'pages', $article->getPages(), false);

		/* --- Galleys --- */
		foreach ($article->getGalleys() as $galley) {
			$galleyNode = NativeExportDom::generateGalleyDom($doc, $journal, $issue, $article, $galley);
			XMLWriter::appendChild($root, $galleyNode);
			
		}

		/* --- Supplementary Files --- */
		import('file.ArticleFileManager');
		$articleFileManager = &new ArticleFileManager($article->getArticleId());
		foreach ($article->getSuppFiles() as $suppFile) {
			$suppNode = &XMLWriter::createElement($doc, 'supplemental_file');

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

			XMLWriter::setAttribute($suppNode, 'type', $suppFileType);
			XMLWriter::setAttribute($suppNode, 'public_id', $suppFile->getPublicSuppFileId(), false);
			XMLWriter::setAttribute($suppNode, 'language', $suppFile->getLanguage(), false);
			
			XMLWriter::appendChild($root, $suppNode);

			XMLWriter::createChildWithText($doc, $suppNode, 'title', $suppFile->getTitle());
			XMLWriter::createChildWithText($doc, $suppNode, 'creator', $suppFile->getCreator());
			XMLWriter::createChildWithText($doc, $suppNode, 'subject', $suppFile->getSubject());
			if ($suppFileType == 'other') XMLWriter::createChildWithText($doc, $suppNode, 'type_other', $suppFile->getTypeOther());
			XMLWriter::createChildWithText($doc, $suppNode, 'description', $suppFile->getDescription(), false);
			XMLWriter::createChildWithText($doc, $suppNode, 'publisher', $suppFile->getPublisher(), false);
			XMLWriter::createChildWithText($doc, $suppNode, 'sponsor', $suppFile->getSponsor(), false);
			XMLWriter::createChildWithText($doc, $suppNode, 'date_created', NativeExportDom::formatDate($suppFile->getDateCreated()), false);
			XMLWriter::createChildWithText($doc, $suppNode, 'source', $suppFile->getSource(), false);

			$fileNode = &XMLWriter::createElement($doc, 'file');
			XMLWriter::appendChild($suppNode, $fileNode);
			$embedNode = &XMLWriter::createChildWithText($doc, $fileNode, 'embed', base64_encode($articleFileManager->readFile($suppFile->getFileId())));
			XMLWriter::setAttribute($embedNode, 'filename', $suppFile->getOriginalFileName());
			XMLWriter::setAttribute($embedNode, 'encoding', 'base64');
			XMLWriter::setAttribute($embedNode, 'mime_type', $suppFile->getFileType());

		}

		return $root;
	}

	function &generateAuthorDom(&$doc, &$journal, &$issue, &$article, &$author) {
		$root = &XMLWriter::createElement($doc, 'author');
		if ($author->getPrimaryContact()) XMLWriter::setAttribute($root, 'primary_contact', 'true');

		XMLWriter::createChildWithText($doc, $root, 'firstname', $author->getFirstName());
		XMLWriter::createChildWithText($doc, $root, 'middlename', $author->getMiddleName(), false);
		XMLWriter::createChildWithText($doc, $root, 'lastname', $author->getLastName());

		XMLWriter::createChildWithText($doc, $root, 'affiliation', $author->getAffiliation(), false);
		XMLWriter::createChildWithText($doc, $root, 'email', $author->getEmail(), false);
		XMLWriter::createChildWithText($doc, $root, 'biography', $author->getBiography(), false);

		return $root;
	}

	function &generateGalleyDom(&$doc, &$journal, &$issue, &$article, &$galley) {
		$isHtml = $galley->isHTMLGalley();

		import('file.ArticleFileManager');
		$articleFileManager = &new ArticleFileManager($article->getArticleId());
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');

		$root = &XMLWriter::createElement($doc, $isHtml?'htmlgalley':'galley');

		XMLWriter::createChildWithText($doc, $root, 'label', $galley->getLabel());

		/* --- Galley file --- */
		$fileNode = &XMLWriter::createElement($doc, 'file');
		XMLWriter::appendChild($root, $fileNode);
		$embedNode = &XMLWriter::createChildWithText($doc, $fileNode, 'embed', base64_encode($articleFileManager->readFile($galley->getFileId())));
		$articleFile = &$articleFileDao->getArticleFile($galley->getFileId());
		XMLWriter::setAttribute($embedNode, 'filename', $articleFile->getOriginalFileName());
		XMLWriter::setAttribute($embedNode, 'encoding', 'base64');
		XMLWriter::setAttribute($embedNode, 'mime_type', $articleFile->getFileType());

		/* --- HTML-specific data: Stylesheet and/or images --- */

		if ($isHtml) {
			$styleFile = $galley->getStyleFile();
			if ($styleFile) {
				$styleNode = &XMLWriter::createElement($doc, 'stylesheet');
				XMLWriter::appendChild($root, $styleNode);
				$embedNode = &XMLWriter::createChildWithText($doc, $styleNode, 'embed', base64_encode($articleFileManager->readFile($styleFile->getFileId())));
				XMLWriter::setAttribute($embedNode, 'filename', $styleFile->getOriginalFileName());
				XMLWriter::setAttribute($embedNode, 'encoding', 'base64');
			}

			foreach ($galley->getImageFiles() as $imageFile) {
				$imageNode = &XMLWriter::createElement($doc, 'image');
				XMLWriter::appendChild($root, $imageNode);
				$embedNode = &XMLWriter::createChildWithText($doc, $imageNode, 'embed', base64_encode($articleFileManager->readFile($imageFile->getFileId())));
				XMLWriter::setAttribute($embedNode, 'filename', $imageFile->getOriginalFileName());
				XMLWriter::setAttribute($embedNode, 'encoding', 'base64');
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
