<?php

/**
 * EruditExportDom.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Erudit plugin DOM functions for export
 *
 * $Id$
 */

import('xml.XMLCustomWriter');

class EruditExportDom {
	function &generateArticleDom(&$doc, &$journal, &$issue, &$article, &$galley) {
		$unavailableString = Locale::translate('plugins.importexport.erudit.unavailable');

		$root = &XMLCustomWriter::createElement($doc, 'article');
		XMLCustomWriter::setAttribute($root, 'idprop', $journal->getJournalId() . '-' . $issue->getIssueId() . '-' . $article->getArticleId() . '-' . $galley->getGalleyId(), false);
		XMLCustomWriter::setAttribute($root, 'arttype', 'article');

		$lang = $article->getLanguage();
		XMLCustomWriter::setAttribute($root, 'lang', isset($lang)?$lang:'en');
		XMLCustomWriter::setAttribute($root, 'processing', 'cart');

		/* --- admin --- */

		$adminNode = &XMLCustomWriter::createElement($doc, 'admin');
		XMLCustomWriter::appendChild($root, $adminNode);

		/* --- articleinfo --- */

		$articleInfoNode = &XMLCustomWriter::createElement($doc, 'articleinfo');
		XMLCustomWriter::appendChild($adminNode, $articleInfoNode);

		// The first public ID should be a full URL to the article.
		$urlIdNode = &XMLCustomWriter::createChildWithText($doc, $articleInfoNode, 'idpublic', Request::url($journal->getPath(), 'article', 'view', array($article->getArticleId(), $galley->getGalleyId())));
		XMLCustomWriter::setAttribute($urlIdNode, 'scheme', 'sici');

		/* --- journal --- */

		$journalNode = &XMLCustomWriter::createElement($doc, 'journal');
		XMLCustomWriter::appendChild($adminNode, $journalNode);
		XMLCustomWriter::setAttribute($journalNode, 'id', 'ojs-' . $journal->getPath());
		XMLCustomWriter::createChildWithText($doc, $journalNode, 'jtitle', $journal->getTitle());
		XMLCustomWriter::createChildWithText($doc, $journalNode, 'jshorttitle', $journal->getSetting('journalInitials'), false);

		if (!($printIssn = $journal->getSetting('printIssn'))) {
			$printIssn = $unavailableString;
		}
		XMLCustomWriter::createChildWithText($doc, $journalNode, 'idissn', $printIssn);
		if (!($onlineIssn = $journal->getSetting('onlineIssn'))) {
			$onlineIssn = $unavailableString;
		}
		XMLCustomWriter::createChildWithText($doc, $journalNode, 'iddigissn', $onlineIssn);

		/* --- issue --- */

		$issueNode = &XMLCustomWriter::createElement($doc, 'issue');
		XMLCustomWriter::appendChild($adminNode, $issueNode);
		XMLCustomWriter::setAttribute($issueNode, 'id', 'ojs-' . $issue->getBestIssueId());
		XMLCustomWriter::createChildWithText($doc, $issueNode, 'volume', $issue->getVolume(), false);
		XMLCustomWriter::createChildWithText($doc, $issueNode, 'issueno', $issue->getNumber(), false);

		$pubNode = &XMLCustomWriter::createElement($doc, 'pub');
		XMLCustomWriter::appendChild($issueNode, $pubNode);
		XMLCustomWriter::createChildWithText($doc, $pubNode, 'year', $issue->getYear());

		$digPubNode = &XMLCustomWriter::createElement($doc, 'digpub');
		XMLCustomWriter::appendChild($issueNode, $digPubNode);
		XMLCustomWriter::createChildWithText($doc, $digPubNode, 'date', EruditExportDom::formatDate($issue->getDatePublished()));

		/* --- Publisher & DTD --- */

		$publisher = &$journal->getSetting('publisher');
		$publisherNode = &XMLCustomWriter::createElement($doc, 'publisher');
		XMLCustomWriter::setAttribute($publisherNode, 'id', 'ojs-' . $journal->getJournalId() . '-' . $issue->getIssueId() . '-' . $article->getArticleId());
		XMLCustomWriter::appendChild($adminNode, $publisherNode);
		$publisherInstitution = $unavailableString;
		if (isset($publisher) && isset($publisher['institution']) && $publisher['institution'] != '') {
			$publisherInstitution = $publisher['institution'];
		}
		XMLCustomWriter::createChildWithText($doc, $publisherNode, 'orgname', $publisherInstitution);

		$digprodNode = &XMLCustomWriter::createElement($doc, 'digprod');
		XMLCustomWriter::createChildWithText($doc, $digprodNode, 'orgname', $publisherInstitution);
		XMLCustomWriter::setAttribute($digprodNode, 'id', 'ojs-prod-' . $journal->getJournalId() . '-' . $issue->getIssueId() . '-' . $article->getArticleId());
		XMLCustomWriter::appendChild($adminNode, $digprodNode);

		$digdistNode = &XMLCustomWriter::createElement($doc, 'digdist');
		XMLCustomWriter::createChildWithText($doc, $digdistNode, 'orgname', $publisherInstitution);
		XMLCustomWriter::setAttribute($digdistNode, 'id', 'ojs-dist-' . $journal->getJournalId() . '-' . $issue->getIssueId() . '-' . $article->getArticleId());
		XMLCustomWriter::appendChild($adminNode, $digdistNode);


		$dtdNode = &XMLCustomWriter::createElement($doc, 'dtd');
		XMLCustomWriter::appendChild($adminNode, $dtdNode);
		XMLCustomWriter::setAttribute($dtdNode, 'name', 'Erudit Article');
		XMLCustomWriter::setAttribute($dtdNode, 'version', '3.0.0');

		/* --- copyright --- */
		$copyright = $journal->getSetting('copyrightNotice');
		XMLCustomWriter::createChildWithText($doc, $adminNode, 'copyright', empty($copyright)?$unavailableString:$copyright);

		/* --- frontmatter --- */

		$frontMatterNode = &XMLCustomWriter::createElement($doc, 'frontmatter');
		XMLCustomWriter::appendChild($root, $frontMatterNode);

		$titleGroupNode = &XMLCustomWriter::createElement($doc, 'titlegr');
		XMLCustomWriter::appendChild($frontMatterNode, $titleGroupNode);

		XMLCustomWriter::createChildWithText($doc, $titleGroupNode, 'title', strip_tags($article->getArticleTitle()));


		/* --- authorgr --- */

		$authorGroupNode = &XMLCustomWriter::createElement($doc, 'authorgr');
		XMLCustomWriter::appendChild($frontMatterNode, $authorGroupNode);
		$authorNum = 1;
		foreach ($article->getAuthors() as $author) {
			$authorNode = &XMLCustomWriter::createElement($doc, 'author');
			XMLCustomWriter::appendChild($authorGroupNode, $authorNode);
			XMLCustomWriter::setAttribute($authorNode, 'id', 'ojs-' . $journal->getJournalId() . '-' . $issue->getIssueId() . '-' . $article->getArticleId() . '-' . $galley->getGalleyId() . '-' . $authorNum);

			$persNameNode = &XMLCustomWriter::createElement($doc, 'persname');
			XMLCustomWriter::appendChild($authorNode, $persNameNode);

			XMLCustomWriter::createChildWithText($doc, $persNameNode, 'firstname', $author->getFirstName());
			XMLCustomWriter::createChildWithText($doc, $persNameNode, 'middlename', $author->getMiddleName(), false);
			XMLCustomWriter::createChildWithText($doc, $persNameNode, 'familyname', $author->getLastName());

			if ($author->getAffiliation() != '') {
				$affiliationNode = &XMLCustomWriter::createElement($doc, 'affiliation');
				XMLCustomWriter::appendChild($authorNode, $affiliationNode);
				XMLCustomWriter::createChildWithText($doc, $affiliationNode, 'blocktext', $author->getAffiliation(), false);
			}

			$authorNum++;
		}


		/* --- abstract and keywords --- */
		$abstract = strip_tags($article->getAbstract());
		if (!empty($abstract)) {
			$abstractNode = &XMLCustomWriter::createElement($doc, 'abstract');
			XMLCustomWriter::setAttribute ($abstractNode, 'lang', ($language = $article->getLanguage())?$language:'en');
			XMLCustomWriter::appendChild($frontMatterNode, $abstractNode);
			XMLCustomWriter::createChildWithText($doc, $abstractNode, 'blocktext', $abstract);
			unset($abstractNode);
		}

		if ($keywords = $article->getSubject()) {
			$keywordGroupNode = &XMLCustomWriter::createElement($doc, 'keywordgr');
			XMLCustomWriter::setAttribute ($keywordGroupNode, 'lang', ($language = $article->getLanguage())?$language:'en');
			foreach (explode(';', $keywords) as $keyword) {
				XMLCustomWriter::createChildWithText($doc, $keywordGroupNode, 'keyword', trim($keyword), false);
			}
			XMLCustomWriter::appendChild($frontMatterNode, $keywordGroupNode);
		}

		$abstract = strip_tags($article->getAbstractAlt1());
		if (!empty($abstract)) {
			$abstractNode = &XMLCustomWriter::createElement($doc, 'abstract');
			XMLCustomWriter::setAttribute ($abstractNode, 'lang', $journal->getSetting('alternateLocale1'));
			XMLCustomWriter::appendChild($frontMatterNode, $abstractNode);
			XMLCustomWriter::createChildWithText($doc, $abstractNode, 'blocktext', $abstract);
			unset($abstractNode);
		}

		$abstract = strip_tags($article->getAbstractAlt2());
		if (!empty($abstract)) {
			$abstractNode = &XMLCustomWriter::createElement($doc, 'abstract');
			XMLCustomWriter::setAttribute ($abstractNode, 'lang', $journal->getSetting('alternateLocale2'));
			XMLCustomWriter::appendChild($frontMatterNode, $abstractNode);
			XMLCustomWriter::createChildWithText($doc, $abstractNode, 'blocktext', $abstract);
			unset($abstractNode);
		}

		/* --- body --- */

		$bodyNode = &XMLCustomWriter::createElement($doc, 'body');
		XMLCustomWriter::appendChild($root, $bodyNode);

		import('file.ArticleFileManager');
		$articleFileManager = &new ArticleFileManager($article->getArticleId());
		$file = &$articleFileManager->getFile($galley->getFileId());
		
		$parser = &SearchFileParser::fromFile($file);
		if (isset($parser)) {
			if ($parser->open()) {
				// File supports text indexing.
				$textNode = &XMLCustomWriter::createElement($doc, 'text');
				XMLCustomWriter::appendChild($bodyNode, $textNode);
				
				while(($line = $parser->read()) !== false) {
					$line = trim($line);
					if ($line != '') XMLCustomWriter::createChildWithText($doc, $textNode, 'blocktext', $line, false);
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
