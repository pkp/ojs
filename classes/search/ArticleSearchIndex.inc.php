<?php

/**
 * ArticleSearchIndex.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package search
 *
 * Class to add content to the article search index.
 *
 * $Id$
 */

import('search.SearchFileParser');
import('search.SearchTextParser');
import('search.SearchHTMLParser');
import('search.SearchHelperParser');

define('SEARCH_STOPWORDS_FILE', 'registry/stopwords.txt');

class ArticleSearchIndex {

	/**
	 * Add a block of text to the search index.
	 * @param $articleId int
	 * @param $type int
	 * @param $text string
	 * @param $assocId int optional
	 */
	function updateTextIndex($articleId, $type, $text, $assocId = null) {
		$keywords = ArticleSearchIndex::textToKeywordsCount($text);
		
		if (!empty($keywords)) {
			$searchDao = &DAORegistry::getDAO('ArticleSearchDAO');
			$searchDao->deleteArticleKeywords($articleId, $type, $assocId);
			$searchDao->insertArticleKeywords($articleId, $keywords, $type, $assocId);
		}
	}
	
	/**
	 * Add a file to the search index.
	 * @param $articleId int
	 * @param $type int
	 * @param $fileId int
	 */
	function updateFileIndex($articleId, $type, $fileId) {
		import('file.ArticleFileManager');
		$fileMgr = &new ArticleFileManager($articleId);
		$file = &$fileMgr->getFile($fileId);
		
		if (isset($file)) {
			switch ($file->getFileType()) {
				case 'text/plain':
					$parser = &new SearchTextParser($file->getFilePath());
					break;
				case 'text/html':
				case 'application/xhtml':
				case 'application/xml':
					$parser = &new SearchHTMLParser($file->getFilePath());
					break;
				case 'application/pdf':
					$parser = &new SearchHelperParser('pdf', $file->getFilePath());
					break;
				case 'application/postscript':
					$parser = &new SearchHelperParser('ps', $file->getFilePath());
					break;
				case 'application/msword':
					$parser = &new SearchHelperParser('msword', $file->getFilePath());
					break;
					
				// FIXME Add other document types?
			}
		}
			
		if (isset($parser)) {
			// File type supports indexing
			$text = $parser->toText();
			
			if (!empty($text)) {
				return ArticleSearchIndex::updateTextIndex($articleId, $type, $text, $fileId);
			}
		}
	}
	
	/**
	 * Delete keywords from the search index.
	 * @param $articleId int
	 * @param $type int optional
	 * @param $assocId int optional
	 */
	function deleteTextIndex($articleId, $type = null, $assocId = null) {
		$searchDao = &DAORegistry::getDAO('ArticleSearchDAO');
		return $searchDao->deleteArticleKeywords($articleId, $type, $assocId);
	}

	/**
	 * Split a string into a clean array of keywords
	 * @return array of keywords
	 */
	function &getKeywords(&$text) {
		// Remove punctuation
		if (is_array($text)) {
			$text = join("\n", $text);
		}
		$cleanText = preg_replace('/[^\w\-\s_]/', '', $text);
		$cleanText = strtolower($cleanText);
		
		// Split into words
		// FIXME Weird performance issues with "u" modifier (with String class)
		$textArray = preg_split('/\s+/', $cleanText);
		return $textArray;
	}
	
	/**
	 * Parse a block of text into a set of keywords.
	 * @return array set of $keyword => $count elements
	 */
	function textToKeywordsCount($text) {
		$minLength = Config::getVar('search', 'min_word_length');
		$stopwords = &ArticleSearchIndex::loadStopwords();
		
		$textArray = &ArticleSearchIndex::getKeywords($text);

		// Split into unique keywords by count
		$keywords = array_count_values($textArray);
		
		// Remove stopwords
		foreach ($keywords as $k => $v) {
			if (isset($stopwords[$k]) || strlen($k) < $minLength || is_numeric($k)) {
				unset($keywords[$k]);
			}
		}
		
		// FIXME Make this smarter
		
		return $keywords;
	}
	
	/**
	 * Return list of stopwords.
	 * FIXME Should this be locale-specific?
	 * @return array with stopwords as keys
	 */
	function &loadStopwords() {
		static $searchStopwords;

		if (!isset($searchStopwords)) {
			// Load stopwords only once per request (FIXME Cache?)
			$searchStopwords = array_count_values(array_filter(file(SEARCH_STOPWORDS_FILE), create_function('&$a', 'return ($a = trim($a)) && !empty($a) && $a[0] != \'#\';')));
			$searchStopwords[''] = 1;
		}
		
		return $searchStopwords;
	}
	
	/**
	 * Index article metadata.
	 * @param $article Article
	 */
	function indexArticleMetadata(&$article) {
		// Build author keywords
		$authorText = array();
		$authors = $article->getAuthors();
		for ($i=0, $count=count($authors); $i < $count; $i++) {
			$author = &$authors[$i];
			array_push($authorText, $author->getFirstName());
			array_push($authorText, $author->getMiddleName());
			array_push($authorText, $author->getLastName());
			array_push($authorText, $author->getAffiliation());
			array_push($authorText, $author->getBiography());
		}
		
		// Update search index
		$articleId = $article->getArticleId();
		ArticleSearchIndex::updateTextIndex($articleId, ARTICLE_SEARCH_AUTHOR, $authorText);
		ArticleSearchIndex::updateTextIndex($articleId, ARTICLE_SEARCH_TITLE, array($article->getTitle(), $article->getTitleAlt1(), $article->getTitleAlt2()));
		ArticleSearchIndex::updateTextIndex($articleId, ARTICLE_SEARCH_ABSTRACT, array($article->getAbstract(), $article->getAbstractAlt1(), $article->getAbstractAlt2()));
		ArticleSearchIndex::updateTextIndex($articleId, ARTICLE_SEARCH_DISCIPLINE, $article->getDiscipline());
		ArticleSearchIndex::updateTextIndex($articleId, ARTICLE_SEARCH_SUBJECT, array($article->getSubjectClass(), $article->getSubject()));
		ArticleSearchIndex::updateTextIndex($articleId, ARTICLE_SEARCH_TYPE, $article->getType());
		ArticleSearchIndex::updateTextIndex($articleId, ARTICLE_SEARCH_COVERAGE, array($article->getCoverageGeo(), $article->getCoverageChron(), $article->getCoverageSample()));
		// FIXME Index sponsors too?
	}
	
	/**
	 * Index all article files (supplementary and galley).
	 * @param $article Article
	 */
	function indexArticleFiles(&$article) {
		// Index supplementary files
		$fileDao = &DAORegistry::getDAO('SuppFileDAO');
		$files = &$fileDao->getSuppFilesByArticle($article->getArticleId());
		foreach ($files as $file) {
			if ($file->getFileId()) {
				ArticleSearchIndex::updateFileIndex($article->getArticleId(), ARTICLE_SEARCH_SUPPLEMENTARY_FILE, $file->getFileId());
			}
		}
		
		// Index galley files
		$fileDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$files = &$fileDao->getGalleysByArticle($article->getArticleId());
		foreach ($files as $file) {
			if ($file->getFileId()) {
				ArticleSearchIndex::updateFileIndex($article->getArticleId(), ARTICLE_SEARCH_GALLEY_FILE, $file->getFileId());
			}
		}
	}
}

?>
