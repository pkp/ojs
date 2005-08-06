<?php

/**
 * EruditExportDom.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Erudit plugin DOM functions for export
 *
 * $Id$
 */

import('xml.XMLWriter');

class EruditExportDom {
	function &generateArticleDom(&$doc, &$journal, &$issue, &$article, &$galley) {
		$root = &XMLWriter::createElement($doc, 'article');
		XMLWriter::setAttribute($root, 'idprop', $journal->getJournalId() . '-' . $issue->getIssueId() . '-' . $article->getArticleId() . '-' . $galley->getGalleyId(), false);
		XMLWriter::setAttribute($root, 'arttype', 'article');
		XMLWriter::setAttribute($root, 'lang', $article->getLanguage(), false);
		XMLWriter::setAttribute($root, 'processing', 'cart');

		/* --- admin --- */

		$adminNode = &XMLWriter::createElement($doc, 'admin');
		XMLWriter::appendChild($root, $adminNode);

		/* --- articleinfo --- */

		$articleInfoNode = &XMLWriter::createElement($doc, 'articleinfo');
		XMLWriter::appendChild($adminNode, $articleInfoNode);
		XMLWriter::createChildWithText($doc, $articleInfoNode, 'idpublic', $article->getPublicArticleId(), false);

		/* --- journal --- */

		$journalNode = &XMLWriter::createElement($doc, 'journal');
		XMLWriter::appendChild($adminNode, $journalNode);
		XMLWriter::setAttribute($journalNode, 'id', $journal->getPath());
		XMLWriter::createChildWithText($doc, $journalNode, 'jtitle', $journal->getTitle());
		XMLWriter::createChildWithText($doc, $journalNode, 'jshorttitle', $journal->getSetting('journalInitials'), false);
		XMLWriter::createChildWithText($doc, $journalNode, 'idissn', $journal->getSetting('issn'), false);

		/* --- issue --- */

		$issueNode = &XMLWriter::createElement($doc, 'issue');
		XMLWriter::appendChild($adminNode, $issueNode);
		XMLWriter::setAttribute($issueNode, 'id', $issue->getBestIssueId());
		XMLWriter::createChildWithText($doc, $issueNode, 'volume', $issue->getVolume(), false);
		XMLWriter::createChildWithText($doc, $issueNode, 'issueno', $issue->getNumber(), false);

		$pubNode = &XMLWriter::createElement($doc, 'pub');
		XMLWriter::appendChild($issueNode, $pubNode);
		XMLWriter::createChildWithText($doc, $pubNode, 'year', $issue->getYear());

		$digPubNode = &XMLWriter::createElement($doc, 'digpub');
		XMLWriter::appendChild($issueNode, $digPubNode);
		XMLWriter::createChildWithText($doc, $digPubNode, 'date', EruditExportDom::formatDate($issue->getDatePublished()));

		/* --- Publisher & DTD --- */

		$publisher = &$journal->getSetting('publisher');
		if (isset($publisher) && !empty($publisher['institution'])) {
			$publisherNode = &XMLWriter::createElement($doc, 'publisher');
			XMLWriter::setAttribute($publisherNode, 'id', $journal->getJournalId() . '-' . $issue->getIssueId() . '-' . $article->getArticleId());
			XMLWriter::appendChild($adminNode, $publisherNode);
			XMLWriter::createChildWithText($doc, $publisherNode, 'orgname', $publisher['institution']);
			
		}

		$dtdNode = &XMLWriter::createElement($doc, 'dtd');
		XMLWriter::appendChild($adminNode, $dtdNode);
		XMLWriter::setAttribute($dtdNode, 'name', 'Erudit Article');
		XMLWriter::setAttribute($dtdNode, 'version', '3.0.0');

		/* --- frontmatter --- */

		$frontMatterNode = &XMLWriter::createElement($doc, 'frontmatter');
		XMLWriter::appendChild($root, $frontMatterNode);

		$titleGroupNode = &XMLWriter::createElement($doc, 'titlegr');
		XMLWriter::appendChild($frontMatterNode, $titleGroupNode);

		XMLWriter::createChildWithText($doc, $titleGroupNode, 'title', $article->getArticleTitle());

		/* --- authorgr --- */

		$authorGroupNode = &XMLWriter::createElement($doc, 'authorgr');
		XMLWriter::appendChild($frontMatterNode, $authorGroupNode);
		$authorNum = 1;
		foreach ($article->getAuthors() as $author) {
			$authorNode = &XMLWriter::createElement($doc, 'author');
			XMLWriter::appendChild($authorGroupNode, $authorNode);
			XMLWriter::setAttribute($authorNode, 'id', $journal->getJournalId() . '-' . $issue->getIssueId() . '-' . $article->getArticleId() . '-' . $galley->getGalleyId() . '-' . $authorNum);

			$persNameNode = &XMLWriter::createElement($doc, 'persname');
			XMLWriter::appendChild($authorNode, $persNameNode);

			XMLWriter::createChildWithText($doc, $persNameNode, 'firstname', $author->getFirstName());
			XMLWriter::createChildWithText($doc, $persNameNode, 'middlename', $author->getMiddleName(), false);
			XMLWriter::createChildWithText($doc, $persNameNode, 'familyname', $author->getLastName());

			if ($author->getAffiliation() != '') {
				$affiliationNode = &XMLWriter::createElement($doc, 'affiliation');
				XMLWriter::appendChild($authorNode, $affiliationNode);
				XMLWriter::createChildWithText($doc, $affiliationNode, 'blocktext', $author->getAffiliation(), false);
			}

			$authorNum++;
		}

		/* --- body --- */

		$bodyNode = &XMLWriter::createElement($doc, 'body');
		XMLWriter::appendChild($root, $bodyNode);

		import('file.ArticleFileManager');
		$articleFileManager = &new ArticleFileManager($article->getArticleId());
		$file = &$articleFileManager->getFile($galley->getFileId());
		
		$parser = &SearchFileParser::fromFile($file);
		if (isset($parser)) {
			if ($parser->open()) {
				// File supports text indexing.
				$textNode = &XMLWriter::createElement($doc, 'text');
				XMLWriter::appendChild($bodyNode, $textNode);
				
				while(($line = $parser->read()) !== false) {
					XMLWriter::createChildWithText($doc, $textNode, 'blocktext', $line, false);
				}
				$parser->close();
			}
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
		$articleFileManager = new ArticleFileManager($article->getArticleId());
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
