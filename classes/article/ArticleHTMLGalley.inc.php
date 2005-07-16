<?php

/**
 * ArticleHTMLGalley.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article
 *
 * ArticleHTMLGalley class.
 * An HTML galley may include an optional stylesheet and set of images.
 *
 * $Id$
 */

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
	function getHTMLContents($baseImageUrl) {
		import('file.ArticleFileManager');
		$fileManager = &new ArticleFileManager($this->getArticleId());
		$contents = $fileManager->readFile($this->getFileId());
		
		// Replace image references
		$images = &$this->getImageFiles();

		foreach ($images as $image) {
			$contents = preg_replace(
				'/src\s*=\s*"([^"]*' . preg_quote($image->getOriginalFileName()) .    ')"/', 
				'src="' . $baseImageUrl . '/' . $this->getArticleId() . '/' . $image->getFileId() . '"',
				$contents,
				1
			);
		}
		return $contents;
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
		$returner = $this->getData('styleFile');
		return $returner;
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
		$returner = $this->getData('images');
		return $returner;
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
