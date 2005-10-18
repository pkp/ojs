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
		$unavailableString = Locale::translate('plugins.importexport.erudit.unavailable');

		$root = &XMLWriter::createElement($doc, 'article');
		XMLWriter::setAttribute($root, 'idprop', $journal->getJournalId() . '-' . $issue->getIssueId() . '-' . $article->getArticleId() . '-' . $galley->getGalleyId(), false);
		XMLWriter::setAttribute($root, 'arttype', 'article');

		$lang = $article->getLanguage();
		XMLWriter::setAttribute($root, 'lang', isset($lang)?$lang:'en');
		XMLWriter::setAttribute($root, 'processing', 'cart');

		/* --- admin --- */

		$adminNode = &XMLWriter::createElement($doc, 'admin');
		XMLWriter::appendChild($root, $adminNode);

		/* --- articleinfo --- */

		$articleInfoNode = &XMLWriter::createElement($doc, 'articleinfo');
		XMLWriter::appendChild($adminNode, $articleInfoNode);

		// The first public ID should be a full URL to the article.
		$urlIdNode = &XMLWriter::createChildWithText($doc, $articleInfoNode, 'idpublic', Request::getPageUrl() . '/article/view/' . $article->getArticleId() . '/' . $galley->getGalleyId());
		XMLWriter::setAttribute($urlIdNode, 'scheme', 'sici');

		/* --- journal --- */

		$journalNode = &XMLWriter::createElement($doc, 'journal');
		XMLWriter::appendChild($adminNode, $journalNode);
		XMLWriter::setAttribute($journalNode, 'id', 'ojs-' . $journal->getPath());
		XMLWriter::createChildWithText($doc, $journalNode, 'jtitle', $journal->getTitle());
		XMLWriter::createChildWithText($doc, $journalNode, 'jshorttitle', $journal->getSetting('journalInitials'), false);

		if (!($issn = $journal->getSetting('issn'))) {
			$issn = $unavailableString;
		}
		XMLWriter::createChildWithText($doc, $journalNode, 'idissn', $issn);
		XMLWriter::createChildWithText($doc, $journalNode, 'iddigissn', $unavailableString);

		/* --- issue --- */

		$issueNode = &XMLWriter::createElement($doc, 'issue');
		XMLWriter::appendChild($adminNode, $issueNode);
		XMLWriter::setAttribute($issueNode, 'id', 'ojs-' . $issue->getBestIssueId());
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
		$publisherNode = &XMLWriter::createElement($doc, 'publisher');
		XMLWriter::setAttribute($publisherNode, 'id', 'ojs-' . $journal->getJournalId() . '-' . $issue->getIssueId() . '-' . $article->getArticleId());
		XMLWriter::appendChild($adminNode, $publisherNode);
		$publisherInstitution = $unavailableString;
		if (isset($publisher) && isset($publisher['institution']) && $publisher['institution'] != '') {
			$publisherInstitution = $publisher['institution'];
		}
		XMLWriter::createChildWithText($doc, $publisherNode, 'orgname', $publisherInstitution);

		$digprodNode = &XMLWriter::createElement($doc, 'digprod');
		XMLWriter::createChildWithText($doc, $digprodNode, 'orgname', $publisherInstitution);
		XMLWriter::setAttribute($digprodNode, 'id', 'ojs-prod-' . $journal->getJournalId() . '-' . $issue->getIssueId() . '-' . $article->getArticleId());
		XMLWriter::appendChild($adminNode, $digprodNode);

		$digdistNode = &XMLWriter::createElement($doc, 'digdist');
		XMLWriter::createChildWithText($doc, $digdistNode, 'orgname', $publisherInstitution);
		XMLWriter::setAttribute($digdistNode, 'id', 'ojs-dist-' . $journal->getJournalId() . '-' . $issue->getIssueId() . '-' . $article->getArticleId());
		XMLWriter::appendChild($adminNode, $digdistNode);


		$dtdNode = &XMLWriter::createElement($doc, 'dtd');
		XMLWriter::appendChild($adminNode, $dtdNode);
		XMLWriter::setAttribute($dtdNode, 'name', 'Erudit Article');
		XMLWriter::setAttribute($dtdNode, 'version', '3.0.0');

		/* --- copyright --- */
		$copyright = $journal->getSetting('copyrightNotice');
		XMLWriter::createChildWithText($doc, $adminNode, 'copyright', empty($copyright)?$unavailableString:$copyright);

		/* --- frontmatter --- */

		$frontMatterNode = &XMLWriter::createElement($doc, 'frontmatter');
		XMLWriter::appendChild($root, $frontMatterNode);

		$titleGroupNode = &XMLWriter::createElement($doc, 'titlegr');
		XMLWriter::appendChild($frontMatterNode, $titleGroupNode);

		XMLWriter::createChildWithText($doc, $titleGroupNode, 'title', strip_tags($article->getArticleTitle()));


		/* --- authorgr --- */

		$authorGroupNode = &XMLWriter::createElement($doc, 'authorgr');
		XMLWriter::appendChild($frontMatterNode, $authorGroupNode);
		$authorNum = 1;
		foreach ($article->getAuthors() as $author) {
			$authorNode = &XMLWriter::createElement($doc, 'author');
			XMLWriter::appendChild($authorGroupNode, $authorNode);
			XMLWriter::setAttribute($authorNode, 'id', 'ojs-' . $journal->getJournalId() . '-' . $issue->getIssueId() . '-' . $article->getArticleId() . '-' . $galley->getGalleyId() . '-' . $authorNum);

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


		/* --- abstract and keywords --- */
		if ($abstract = $article->getAbstract()) {
			$abstractNode = &XMLWriter::createElement($doc, 'abstract');
			XMLWriter::setAttribute ($abstractNode, 'lang', ($language = $article->getLanguage())?$language:'en');
			XMLWriter::appendChild($frontMatterNode, $abstractNode);
			XMLWriter::createChildWithText($doc, $abstractNode, 'blocktext', $abstract);
		}

		if ($keywords = $article->getSubject()) {
			$keywordGroupNode = &XMLWriter::createElement($doc, 'keywordgr');
			foreach (explode(';', $keywords) as $keyword) {
				XMLWriter::createChildWithText($doc, $keywordGroupNode, 'keyword', trim($keyword), false);
			}
			XMLWriter::appendChild($frontMatterNode, $keywordGroupNode);
		}

		if ($abstract = $article->getAbstractAlt1()) {
			$abstractNode = &XMLWriter::createElement($doc, 'abstract');
			XMLWriter::setAttribute ($abstractNode, 'lang', $journal->getSetting('alternateLocale1'));
			XMLWriter::appendChild($frontMatterNode, $abstractNode);
			XMLWriter::createChildWithText($doc, $abstractNode, 'blocktext', $abstract);
		}

		if ($abstract = $article->getAbstractAlt2()) {
			$abstractNode = &XMLWriter::createElement($doc, 'abstract');
			XMLWriter::setAttribute ($abstractNode, 'lang', $journal->getSetting('alternateLocale2'));
			XMLWriter::appendChild($frontMatterNode, $abstractNode);
			XMLWriter::createChildWithText($doc, $abstractNode, 'blocktext', $abstract);
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
					$line = trim($line);
					if ($line != '') XMLWriter::createChildWithText($doc, $textNode, 'blocktext', $line, false);
				}
				$parser->close();
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
