<?php

/**
 * @file classes/article/ArticleHTMLGalley.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleHTMLGalley
 * @ingroup article
 *
 * @brief An HTML galley may include an optional stylesheet and set of images.
 */

// $Id$


import('article.ArticleGalley');

class ArticleHTMLGalley extends ArticleGalley {

	/**
	 * Constructor.
	 */
	function ArticleHTMLGalley() {
		parent::ArticleGalley();
	}

	/**
	 * Check if galley is an HTML galley.
	 * @return boolean
	 */
	function isHTMLGalley() {
		return true;
	}

	/**
	 * Return string containing the contents of the HTML file.
	 * This function performs any necessary filtering, like image URL replacement.
	 * @param $baseImageUrl string base URL for image references
	 * @return string
	 */
	function getHTMLContents() {
		import('file.ArticleFileManager');
		$fileManager = &new ArticleFileManager($this->getArticleId());
		$contents = $fileManager->readFile($this->getFileId());
		$journal =& Request::getJournal();

		// Replace media file references
		$images = &$this->getImageFiles();

		foreach ($images as $image) {
			$imageUrl = Request::url(null, 'article', 'viewFile', array($this->getArticleId(), $this->getBestGalleyId($journal), $image->getFileId()));
			$pattern = preg_quote(rawurlencode($image->getOriginalFileName()));
			
			$contents = preg_replace(
				'/([Ss][Rr][Cc]|[Hh][Rr][Ee][Ff]|[Dd][Aa][Tt][Aa])\s*=\s*"([^"]*' . $pattern . ')"/',
				'\1="' . $imageUrl . '"',
				$contents
			);
			
			// Replacement for Flowplayer
			$contents = preg_replace(
				'/[Uu][Rr][Ll]\s*\:\s*\'(' . $pattern . ')\'/', 
				'url:\'' . $imageUrl . '\'',
				$contents
			);
			
			// Replacement for other players (ested with odeo; yahoo and google player won't work w/ OJS URLs, might work for others)
			$contents = preg_replace(
				'/[Uu][Rr][Ll]=([^"]*' . $pattern . ')/', 
				'url=' . $imageUrl ,
				$contents
			);
			
		}

		// Perform replacement for ojs://... URLs
		$contents = String::regexp_replace_callback(
			'/(<[^<>]*")[Oo][Jj][Ss]:\/\/([^"]+)("[^<>]*>)/',
			array(&$this, '_handleOjsUrl'),
			$contents
		);

		// Perform variable replacement for journal, issue, site info
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issue =& $issueDao->getIssueByArticleId($this->getArticleId());

		$journal =& Request::getJournal();
		$site =& Request::getSite();

		$paramArray = array(
			'issueTitle' => $issue?$issue->getIssueIdentification():Locale::translate('editor.article.scheduleForPublication.toBeAssigned'),
			'journalTitle' => $journal->getJournalTitle(),
			'siteTitle' => $site->getSiteTitle(),
			'currentUrl' => Request::getRequestUrl()
		);

		foreach ($paramArray as $key => $value) {
			$contents = str_replace('{$' . $key . '}', $value, $contents);
		}

		return $contents;
	}

	function _handleOjsUrl($matchArray) {
		$url = $matchArray[2];
		$anchor = null;
		if (($i = strpos($url, '#')) !== false) {
			$anchor = substr($url, $i+1);
			$url = substr($url, 0, $i);
		}
		$urlParts = explode('/', $url);
		if (isset($urlParts[0])) switch(String::strtolower($urlParts[0])) {
			case 'journal':
				$url = Request::url(
					isset($urlParts[1]) ?
						$urlParts[1] :
						Request::getRequestedJournalPath(),
					null,
					null,
					null,
					null,
					$anchor
				);
				break;
			case 'article':
				if (isset($urlParts[1])) {
					$url = Request::url(
						null,
						'article',
						'view',
						$urlParts[1],
						null,
						$anchor
					);
				}
				break;
			case 'issue':
				if (isset($urlParts[1])) {
					$url = Request::url(
						null,
						'issue',
						'view',
						$urlParts[1],
						null,
						$anchor
					);
				} else {
					$url = Request::url(
						null,
						'issue',
						'current',
						null,
						null,
						$anchor
					);
				}
				break;
			case 'suppfile':
				if (isset($urlParts[1]) && isset($urlParts[2])) {
					$url = Request::url(
						null,
						'article',
						'downloadSuppFile',
						array($urlParts[1], $urlParts[2]),
						null,
						$anchor
					);
				}
				break;
			case 'sitepublic':
					array_shift($urlParts);
					import ('file.PublicFileManager');
					$publicFileManager = &new PublicFileManager();
					$url = Request::getBaseUrl() . '/' . $publicFileManager->getSiteFilesPath() . '/' . implode('/', $urlParts) . ($anchor?'#' . $anchor:'');
				break;
			case 'public':
					array_shift($urlParts);
					$journal = &Request::getJournal();
					import ('file.PublicFileManager');
					$publicFileManager = &new PublicFileManager();
					$url = Request::getBaseUrl() . '/' . $publicFileManager->getJournalFilesPath($journal->getJournalId()) . '/' . implode('/', $urlParts) . ($anchor?'#' . $anchor:'');
				break;
		}
		return $matchArray[1] . $url . $matchArray[3];
	}

	/**
	 * Check if the specified file is a dependent file.
	 * @param $fileId int
	 * @return boolean
	 */
	function isDependentFile($fileId) {
		if ($this->getStyleFileId() == $fileId) return true;
		foreach ($this->getImageFiles() as $image) {
			if ($image->getFileId() == $fileId) return true;
		}
		return false;
	}

	//
	// Get/set methods
	//

	/**
	 * Get ID of associated stylesheet file, if applicable.
	 * @return int
	 */
	function getStyleFileId() {
		return $this->getData('styleFileId');
	}

	/**
	 * Set ID of associated stylesheet file.
	 * @param $styleFileId int
	 */
	function setStyleFileId($styleFileId) {
		return $this->setData('styleFileId', $styleFileId);
	}

	/**
	 * Return the stylesheet file associated with this HTML galley, if applicable.
	 * @return ArticleFile
	 */
	function &getStyleFile() {
		$styleFile = &$this->getData('styleFile');
		return $styleFile;
	}

	/**
	 * Set the stylesheet file for this HTML galley.
	 * @param ArticleFile $styleFile
	 */
	function setStyleFile(&$styleFile) {
		$this->setData('styleFile', $styleFile);
	}

	/**
	 * Return array of image files for this HTML galley.
	 * @return array
	 */
	function &getImageFiles() {
		$images = &$this->getData('images');
		return $images;
	}

	/**
	 * Set array of image files for this HTML galley.
	 * @param $images array
	 * @return array
	 */
	function setImageFiles(&$images) {
		return $this->setData('images', $images);
	}

}

?>
