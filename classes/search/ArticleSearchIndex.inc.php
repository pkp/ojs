<?php

/**
 * ArticleSearchIndex.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package search
 *
 * Class to add content to the article search index.
 *
 * $Id$
 */

import('search.SearchFileParser');
import('search.SearchHTMLParser');
import('search.SearchHelperParser');

define('SEARCH_STOPWORDS_FILE', 'registry/stopwords.txt');

// Words are truncated to at most this length
define('SEARCH_KEYWORD_MAX_LENGTH', 40);

class ArticleSearchIndex {
	
	/**
	 * Index a block of text for an object.
	 * @param $objectId int
	 * @param $text string
	 * @param $position int
	 */
	function indexObjectKeywords($objectId, $text, &$position) {
		$searchDao = &DAORegistry::getDAO('ArticleSearchDAO');
		$keywords = &ArticleSearchIndex::filterKeywords($text);
		for ($i = 0, $count = count($keywords); $i < $count; $i++) {
			$searchDao->insertObjectKeyword($objectId, $keywords[$i], $position);
			$position += 1;
		}
	}

	/**
	 * Add a block of text to the search index.
	 * @param $articleId int
	 * @param $type int
	 * @param $text string
	 * @param $assocId int optional
	 */
	function updateTextIndex($articleId, $type, $text, $assocId = null) {
			$searchDao = &DAORegistry::getDAO('ArticleSearchDAO');
			$objectId = $searchDao->insertObject($articleId, $type, $assocId);
			$position = 0;
			ArticleSearchIndex::indexObjectKeywords($objectId, $text, $position);
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
					$parser = &new SearchFileParser($file->getFilePath());
					break;
				case 'text/html':
				case 'application/xhtml':
				case 'application/xml':
					$parser = &new SearchHTMLParser($file->getFilePath());
					break;
				default:
					$parser = &new SearchHelperParser($file->getFileType(), $file->getFilePath());
					break;					
			}
		}
			
		if (isset($parser)) {
			if ($parser->open()) {
				$searchDao = &DAORegistry::getDAO('ArticleSearchDAO');
				$objectId = $searchDao->insertObject($articleId, $type, $fileId);
				
				$position = 0;
				while(($text = $parser->read()) !== false) {
					ArticleSearchIndex::indexObjectKeywords($objectId, $text, $position);
				}
				$parser->close();
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
	 * @param $text string
	 * @param $allowWildcards boolean
	 * @return array of keywords
	 */
	function &filterKeywords($text, $allowWildcards = false) {
		$minLength = Config::getVar('search', 'min_word_length');
		$stopwords = &ArticleSearchIndex::loadStopwords();
		
		// Remove punctuation
		if (is_array($text)) {
			$text = join("\n", $text);
		}
		
		$cleanText = preg_replace('/[!"\#\$%\'\(\)\.\?@\[\]\^`\{\}~]/', '', $text);
		$cleanText = preg_replace('/\+,:;&\/<=>\|\\\/', ' ', $cleanText);
		$cleanText = preg_replace('/\*/', $allowWildcards ? '%' : ' ', $cleanText);
		$cleanText = strtolower($cleanText);
		
		// Split into words
		$words = preg_split('/\s+/', $cleanText);
		
		// FIXME Do not perform further filtering for some fields, e.g., author names?
		
		// Remove stopwords
		$keywords = array();
		foreach ($words as $k) {
			if (!isset($stopwords[$k]) && strlen($k) >= $minLength && !is_numeric($k)) {
				$keywords[] = substr($k, 0, SEARCH_KEYWORD_MAX_LENGTH);
			}
		}
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
	
	/**
	 * Rebuild the search index for all journals.
	 */
	function rebuildIndex() {
		// Clear index
		$searchDao = &DAORegistry::getDAO('ArticleSearchDAO');
		$searchDao->update('DELETE FROM article_search_object_keywords');
		$searchDao->update('DELETE FROM article_search_objects');
		$searchDao->update('DELETE FROM article_search_keyword_list');
		
		// Build index
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		
		$journals = &$journalDao->getJournals();
		while (!$journals->eof()) {
			$journal = &$journals->next();
			
			$articles = &$articleDao->getArticlesByJournalId($journal->getJournalId());
			while (!$articles->eof()) {
				$article = &$articles->next();
				if ($article->getDateSubmitted()) {
					ArticleSearchIndex::indexArticleMetadata($article);
					ArticleSearchIndex::indexArticleFiles($article);
				}
			}
		}
	}
	
}

?>
