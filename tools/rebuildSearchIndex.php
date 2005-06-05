<?php

/**
 * rebuildSearchIndex.php
 *
 * Copyright (c) 2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package tools
 *
 * CLI tool to rebuild the article keyword search database.
 *
 * $Id$
 */

require(dirname(__FILE__) . '/includes/cliTool.inc.php');

import('search.ArticleSearchIndex');

class rebuildSearchIndex extends CommandLineTool {
	
	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "Script to rebuild article search index\n"
			. "Usage: {$this->scriptName}\n";
	}
	
	/**
	 * Rebuild the search index for all articles in all journals.
	 */
	function execute() {
		$this->clearIndex();
		$this->buildIndex();
	}
	
	/**
	 * Clear old search index data.
	 */
	function clearIndex() {
		$searchDao = &DAORegistry::getDAO('ArticleSearchDAO');
		$searchDao->update('DELETE FROM article_search_keyword_index');
		$searchDao->update('DELETE FROM article_search_keyword_list');
	}
	
	/**
	 * Build search index data.
	 */
	function buildIndex() {
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		
		$journals = &$journalDao->getJournals();
		while (!$journals->eof()) {
			$journal = &$journals->next();
			$numIndexed = 0;
			
			echo "Indexing \"", $journal->getTitle(), "\" ... ";
			
			$articles = &$articleDao->getArticlesByJournalId($journal->getJournalId());
			while (!$articles->eof()) {
				if ($this->indexArticle($articles->next())) {
					$numIndexed++;
				}
			}
			
			echo $numIndexed, " articles indexed\n";
		}
	}
	
	/**
	 * Index a single article.
	 * @param $article Article
	 * @return boolean true if article was indexed
	 */
	function indexArticle(&$article) {
		if (!$article->getDateSubmitted()) {
			// Skip articles that have not been submitted
			return false;
		}
		
		ArticleSearchIndex::indexArticleMetadata($article);
		ArticleSearchIndex::indexArticleFiles($article);
		
		return true;
	}
	
}

$tool = &new rebuildSearchIndex(isset($argv) ? $argv : array());
$tool->execute();
?>
