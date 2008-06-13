<?php

/**
 * @file ArticleSearchIndex.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package search
 * @class ArticleSearchIndex
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
			if ($searchDao->insertObjectKeyword($objectId, $keywords[$i], $position) !== null) {
				$position += 1;
			}
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
			$parser = &SearchFileParser::fromFile($file);
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

		// Join multiple lines into a single string
		if (is_array($text)) $text = join("\n", $text);

		$cleanText = Core::cleanVar($text);

		// Remove punctuation
		$cleanText = String::regexp_replace('/[!"\#\$%\'\(\)\.\?@\[\]\^`\{\}~]/', '', $cleanText);
		$cleanText = String::regexp_replace('/[\+,:;&\/<=>\|\\\]/', ' ', $cleanText);
		$cleanText = String::regexp_replace('/[\*]/', $allowWildcards ? '%' : ' ', $cleanText);
		$cleanText = String::strtolower($cleanText);

		// Split into words
		$words = String::regexp_split('/\s+/', $cleanText);

		// FIXME Do not perform further filtering for some fields, e.g., author names?

		// Remove stopwords
		$keywords = array();
		foreach ($words as $k) {
			if (!isset($stopwords[$k]) && String::strlen($k) >= $minLength && !is_numeric($k)) {
				$keywords[] = String::substr($k, 0, SEARCH_KEYWORD_MAX_LENGTH);
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
			$bios = $author->getBiography(null);
			if (is_array($bios)) foreach ($bios as $bio) { // Localized
				array_push($authorText, strip_tags($bio));
			}
		}

		// Update search index
		$articleId = $article->getArticleId();
		ArticleSearchIndex::updateTextIndex($articleId, ARTICLE_SEARCH_AUTHOR, $authorText);
		ArticleSearchIndex::updateTextIndex($articleId, ARTICLE_SEARCH_TITLE, $article->getTitle(null));
		ArticleSearchIndex::updateTextIndex($articleId, ARTICLE_SEARCH_ABSTRACT, $article->getAbstract(null));

		ArticleSearchIndex::updateTextIndex($articleId, ARTICLE_SEARCH_DISCIPLINE, (array) $article->getDiscipline(null));
		ArticleSearchIndex::updateTextIndex($articleId, ARTICLE_SEARCH_SUBJECT, array_merge(array_values((array) $article->getSubjectClass(null)), array_values((array) $article->getSubject(null))));
		ArticleSearchIndex::updateTextIndex($articleId, ARTICLE_SEARCH_TYPE, $article->getType(null));
		ArticleSearchIndex::updateTextIndex($articleId, ARTICLE_SEARCH_COVERAGE, array_merge(array_values((array) $article->getCoverageGeo(null)), array_values((array) $article->getCoverageChron(null)), array_values((array) $article->getCoverageSample(null))));
		// FIXME Index sponsors too?
	}

	/**
	 * Index supp file metadata.
	 * @param $suppFile object
	 */
	function indexSuppFileMetadata(&$suppFile) {
		// Update search index
		$articleId = $suppFile->getArticleId();
		ArticleSearchIndex::updateTextIndex(
			$articleId,
			ARTICLE_SEARCH_SUPPLEMENTARY_FILE,
			array_merge(
				array_values((array) $suppFile->getTitle(null)),
				array_values((array) $suppFile->getCreator(null)),
				array_values((array) $suppFile->getSubject(null)),
				array_values((array) $suppFile->getTypeOther(null)),
				array_values((array) $suppFile->getDescription(null)),
				array_values((array) $suppFile->getSource(null))
			),
			$suppFile->getFileId()
		);
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
			ArticleSearchIndex::indexSuppFileMetadata($file);
		}
		unset($files);

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
	function rebuildIndex($log = false) {
		// Clear index
		if ($log) echo 'Clearing index ... ';
		$searchDao = &DAORegistry::getDAO('ArticleSearchDAO');
		// FIXME Abstract into ArticleSearchDAO?
		$searchDao->update('DELETE FROM article_search_object_keywords');
		$searchDao->update('DELETE FROM article_search_objects');
		$searchDao->update('DELETE FROM article_search_keyword_list');
		$searchDao->setCacheDir(Config::getVar('files', 'files_dir') . '/_db');
		$searchDao->_dataSource->CacheFlush();
		if ($log) echo "done\n";

		// Build index
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$articleDao = &DAORegistry::getDAO('ArticleDAO');

		$journals = &$journalDao->getJournals();
		while (!$journals->eof()) {
			$journal = &$journals->next();
			$numIndexed = 0;

			if ($log) echo "Indexing \"", $journal->getJournalTitle(), "\" ... ";

			$articles = &$articleDao->getArticlesByJournalId($journal->getJournalId());
			while (!$articles->eof()) {
				$article = &$articles->next();
				if ($article->getDateSubmitted()) {
					ArticleSearchIndex::indexArticleMetadata($article);
					ArticleSearchIndex::indexArticleFiles($article);
					$numIndexed++;
				}
				unset($article);
			}

			if ($log) echo $numIndexed, " articles indexed\n";
			unset($journal);
		}
	}

}

?>
