<?php

/**
 * @file classes/article/ArticleHTMLGalley.inc.php
 *
 * Copyright (c) 2013-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleHTMLGalley
 * @ingroup article
 *
 * @brief An HTML galley may include an optional stylesheet and set of images.
 */

import('classes.article.ArticleGalley');

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
	* Eirik Hanssen, Oslo and Akershus University College of Applied Sciences 2015
	* Return string contains the <body> element of the HTML Galley file renamed to a <div> element.
	* The returned string is a html fragment where empty tags will be self closed, as required by xhtml rulesybe.
	* The purpose is to allow to allow OJS pages displaying the HTML Galley of an article to validate. Without filtering the HTML file string
	* through the getHTMLBodyContents() function, a valid (full) HTML document will simply be inserted in a <div> element in the OJS page,
	* and then that resulting webpage won't be valid HTML since it is illegal to have a full html-document inside a div tag.
	* This function requires the php DOMDocument module to operate. If DOMDocument module is not available, (often part of a php-xml package),
	* a warning in form of a html-comment is appended to the html string instead of performing this filtering.
	* @return string
	*/
	function getHTMLBodyContents($htmldoc){
		// first check if DOMDocument class is available
		if (class_exists('DOMDocument')) {
			$originalHtmlDocument = new DOMDocument();
			$htmlBodyContents = new DOMDocument();
			$originalHtmlDocument->loadHTML($htmldoc);

			// Get the body element node from the full html document.
			$htmlBody = $originalHtmlDocument->getElementsByTagName('body')->item(0);

			// LIBXML_NOEMPTYTAG makes sure no empty elements are shortened.
			// This has the side-effect of adding closing tags to void elements such as img and hr tags too.
			// This is a xhtml spec violation and needs to be fixed with regex

			$fulltextBody = $originalHtmlDocument->saveXML($htmlBody, LIBXML_NOEMPTYTAG);

			// preg_replace() here removes removes ending tags from elements that should be void and makes those tags self-closing
			// The following void elements might be encountered:
			// HTML4.01: area|base|basefont|br|col|frame|hr|img|input|isindex|link|meta|param
			// XHTML1.0: area|base|basefont|br|col|hr|img|input|isindex|link|meta|param
			// HTML5: area|base|br|col|embed|hr|img|input|keygen|link|meta|param|source|track|wbr
			$fulltextBody = preg_replace('/><\/(area|base|basefont|br|col|embed|frame|hr|img|input|isindex|keygen|link|meta|param|source|track|wbr)>/', '/>' ,$fulltextBody);

			// preg_replace() here renames the body tag to div, both opening and closing tag
			$fulltextBody = preg_replace('/<(\/)*(body)/', '<$1div', $fulltextBody);

			// the body contents is now wrapped in a div tag with xhtml tagging style of void elements
			return "\n<!-- htmlFulltext Insertion Begin -->\n" . $fulltextBody . "\n<!-- htmlFulltext Insertion END -->\n";
		} else {
			// If DOMDocument class is not available: Append a warning as a html comment to the html string and return it.
			return $htmldoc . "<!-- Warning: DOMDocument class is not available! Please check your PHP installation. DOMDocument module a part of php-xml package is required by getHTMLBodyContents()";
		}
	}

	/**
	 * Return string containing the contents of the HTML file.
	 * This function performs any necessary filtering, like image URL replacement.
	 * @param $baseImageUrl string base URL for image references
	 * @return string
	 */
	function getHTMLContents() {
		import('classes.file.ArticleFileManager');
		$fileManager = new ArticleFileManager($this->getArticleId());
		$contents = $fileManager->readFile($this->getFileId());
		$journal =& Request::getJournal();

		// Replace media file references
		$images =& $this->getImageFiles();

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
		$contents = preg_replace_callback(
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
			'issueTitle' => $issue?$issue->getIssueIdentification():__('editor.article.scheduleForPublication.toBeAssigned'),
			'journalTitle' => $journal->getLocalizedTitle(),
			'siteTitle' => $site->getLocalizedTitle(),
			'currentUrl' => Request::getRequestUrl()
		);

		foreach ($paramArray as $key => $value) {
			$contents = str_replace('{$' . $key . '}', $value, $contents);
		}

		// Eirik Hanssen 2014-05-15:
		// to make HTML Galley webpages in OJS validate, $contents will be filtered through the new getHTMLBodyContents() function
		// This will grab the <body> element of the article (with all it's contents), rename it to a <div> tag  and return that instead as the HTML Galley html-string.
		return $this->getHTMLBodyContents($contents);
	}

	function _handleOjsUrl($matchArray) {
		$url = $matchArray[2];
		$anchor = null;
		if (($i = strpos($url, '#')) !== false) {
			$anchor = substr($url, $i+1);
			$url = substr($url, 0, $i);
		}
		$urlParts = explode('/', $url);
		if (isset($urlParts[0])) switch(strtolower_codesafe($urlParts[0])) {
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
					import ('classes.file.PublicFileManager');
					$publicFileManager = new PublicFileManager();
					$url = Request::getBaseUrl() . '/' . $publicFileManager->getSiteFilesPath() . '/' . implode('/', $urlParts) . ($anchor?'#' . $anchor:'');
				break;
			case 'public':
					array_shift($urlParts);
					$journal =& Request::getJournal();
					import ('classes.file.PublicFileManager');
					$publicFileManager = new PublicFileManager();
					$url = Request::getBaseUrl() . '/' . $publicFileManager->getJournalFilesPath($journal->getId()) . '/' . implode('/', $urlParts) . ($anchor?'#' . $anchor:'');
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
		$styleFile =& $this->getData('styleFile');
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
		$images =& $this->getData('images');
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
