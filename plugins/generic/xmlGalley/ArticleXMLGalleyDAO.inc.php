<?php

/**
 * @file plugins/generic/xmlGalley/ArticleXMLGalleyDAO.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleXMLGalleyDAO
 * @ingroup plugins_generic_xmlGalley
 *
 * @brief Extended DAO methods for XML-derived galleys
 * NB: These require access to a number of hooks in ArticleGalleyDAO
 * to override the default methods; this is prime for refactoring!
 */

import('classes.article.ArticleGalleyDAO');


class ArticleXMLGalleyDAO extends ArticleGalleyDAO {
	/** @var $parentPluginName string Name of parent plugin */
	var $parentPluginName;

	/**
	 * Constructor
	 */
	function ArticleXMLGalleyDAO($parentPluginName) {
		$this->parentPluginName = $parentPluginName;
		parent::ArticleGalleyDAO();
	}

	/**
	 * Internal function to return an ArticleXMLGalley object from an XML galley Id
	 * @param $galleyId int
	 * @param $articleId int
	 * @return ArticleXMLGalley
	 */
	function _getXMLGalleyFromId($galleyId, $articleId = null) {
		$params = array((int) $galleyId);
		if ($articleId) $params[] = (int) $articleId;

		// get derived galley from DB
		$result =& $this->retrieve(
			'SELECT	x.*,
				x.galley_type AS file_type,
				g.file_id,
				g.html_galley,
				g.style_file_id,
				g.seq,
				g.locale,
				g.remote_url,
				a.file_name,
				a.original_file_name,
				a.file_stage,
				a.file_type,
				a.file_size,
				a.date_uploaded,
				a.date_modified
			FROM	article_xml_galleys x
				LEFT JOIN article_galleys g ON (x.galley_id = g.galley_id)
				LEFT JOIN article_files a ON (g.file_id = a.file_id)
			WHERE	x.galley_id = ?
				' . ($articleId?' AND x.article_id = ?':''),
			$params
		);

		// transform row into an ArticleXMLGalley object
		if ($result->RecordCount() != 0) {
			$articleGalley =& $this->_returnGalleyFromRow($result->GetRowAssoc(false));

			$xmlGalleyPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
			$xmlGalley = $xmlGalleyPlugin->_returnXMLGalleyFromArticleGalley($articleGalley);
			return $xmlGalley;
		}
	}

	/**
	 * Append XML-derived galleys (eg. PDF) to the list of galleys for an article
	 */
	function appendXMLGalleys($hookName, $args) {
		$galleys =& $args[0];
		$articleId =& $args[1];

		// we have to use this crazy loop because PHP4 can't access objects by reference in foreach()
		reset($galleys);
		while (list($key) = each($galleys)) {
			$galley =&  $galleys[$key];

			// if the galley is an XML galley, append XML-derived galleys
			if ($galley->getFileType() == "text/xml" || $galley->getFileType() == "application/xml") {

				// get derived galleys from DB for this article
				$result =& $this->retrieve(
					'SELECT	galley_id
					FROM	article_xml_galleys x
					WHERE	x.galley_id = ? AND
						x.article_id = ?
					ORDER BY galley_id',
					array($galley->getId(), $articleId)
				);

				$xmlGalleyPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
				$journal =& Request::getJournal();

				while (!$result->EOF) {
					$row = $result->GetRowAssoc(false);
					$xmlGalley = $this->_getXMLGalleyFromId($row['galley_id'], $articleId);

// WARNING: See bug #5152 and note below.
/*
					$xmlGalley->setId($row['xml_galley_id']);

					// only append PDF galleys if the correct plugin settings are set
					if ( ($xmlGalleyPlugin->getSetting($journal->getId(), 'nlmPDF') == 1 
							&& $xmlGalley->isPdfGalley()) || $xmlGalley->isHTMLGalley()) {
						array_push($galleys, $xmlGalley);
					} */
					$result->moveNext();
				}

				// hide source XML galley; this could be made a plugin setting/checkbox
// WARNING: See bug #5152 and note below.
//				if (isset($xmlGalley)) unset($galleys[$key]);
			}
			unset($galley);
		}

		return true;
	}

	/**
	 * Insert XML-derived galleys into article_xml_galleys
	 */
	function insertXMLGalleys($hookName, $args) {
		$galley =& $args[0];
		$galleyId =& $args[1];

		// If the galley is an XML file, then insert rows in the article_xml_galleys table
		if ($galley->getLabel() == "XML") {

			// create an XHTML galley
			$this->update(
				'INSERT INTO article_xml_galleys
					(galley_id, article_id, label, galley_type)
					VALUES
					(?, ?, ?, ?)',
				array(
					$galleyId,
					$galley->getArticleId(),
					'XHTML',
					'application/xhtml+xml'
				)
			);

// WARNING: The below code is disabled because of bug #5152. When a galley
// exists with the same galley_id as an entry in the article_xml_galleys table,
// editing the XML galley will corrupt the entry in the galleys table for the
// same galley_id. This has been fixed by retiring the article_xml_galleys
// table's xml_galley_id in favour of using the galley_id instead, but this
// means that only a single derived galley (=XHTML) is possible for an XML
// galley upload.
/*

			// if we have enabled XML-PDF galley generation (plugin setting)
			// and are using the built-in NLM stylesheet, append a PDF galley as well
			$journal =& Request::getJournal();
			$xmlGalleyPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);

			if ($xmlGalleyPlugin->getSetting($journal->getId(), 'nlmPDF') == 1 && 
				$xmlGalleyPlugin->getSetting($journal->getId(), 'XSLstylesheet') == 'NLM' ) {

				// create a PDF galley
				$this->update(
					'INSERT INTO article_xml_galleys
						(galley_id, article_id, label, galley_type)
						VALUES
						(?, ?, ?, ?)',
					array(
						$galleyId,
						$galley->getArticleId(),
						'PDF',
						'application/pdf'
					)
				);

			}*/
			return true;
		}
		return false;
	}

	/**
	 * Delete XML-derived galleys from article_xml_galleys 
	 * when the XML galley is deleted
	 */
	function deleteXMLGalleys($hookName, $args) {
		$galleyId =& $args[0];
		$articleId =& $args[1];

		if (isset($articleId)) {
			$this->update(
				'DELETE FROM article_xml_galleys WHERE galley_id = ? AND article_id = ?',
				array($galleyId, $articleId)
			);

		} else {
			$this->update(
				'DELETE FROM article_xml_galleys WHERE galley_id = ?', $galleyId
			);
		}

	}

	/**
	 * Increment views on XML-derived galleys
	 */
	function incrementXMLViews($hookName, $args) {
		$galleyId =& $args[0];

		return $this->update(
			'UPDATE article_xml_galleys SET views = views + 1 WHERE galley_id = ?',
			$galleyId
		);

	}
}

?>
