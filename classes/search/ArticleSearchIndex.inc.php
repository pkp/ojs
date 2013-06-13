<?php

/**
 * @file classes/search/ArticleSearchIndex.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleSearchIndex
 * @ingroup search
 *
 * @brief Class to maintain the article search index.
 */

import('lib.pkp.classes.search.SubmissionSearchIndex');

class ArticleSearchIndex extends SubmissionSearchIndex {

	/**
	 * Signal to the indexing back-end that the metadata of an
	 * article changed.
	 *
	 * Push indexing implementations will try to immediately update
	 * the index to reflect the changes. Pull implementations will
	 * mark articles as "changed" and let the indexing back-end decide
	 * the best point in time to actually index the changed data.
	 *
	 * @see http://pkp.sfu.ca/wiki/index.php/OJSdeSearchConcept#Push_vs._Pull
	 * for a discussion of push vs. pull indexing.
	 *
	 * @param $article Article
	 */
	function articleMetadataChanged(&$article) {
		// Check whether a search plug-in jumps in.
		$hookResult = HookRegistry::call(
			'ArticleSearchIndex::articleMetadataChanged',
			array($article)
		);

		// If no search plug-in is activated then fall back to the
		// default database search implementation.
		if ($hookResult === false || is_null($hookResult)) {
			// Build author keywords
			$authorText = array();
			$authors = $article->getAuthors();
			for ($i=0, $count=count($authors); $i < $count; $i++) {
				$author = $authors[$i];
				array_push($authorText, $author->getFirstName());
				array_push($authorText, $author->getMiddleName());
				array_push($authorText, $author->getLastName());
				$affiliations = $author->getAffiliation(null);
				if (is_array($affiliations)) foreach ($affiliations as $affiliation) { // Localized
					array_push($authorText, $affiliation);
				}
				$bios = $author->getBiography(null);
				if (is_array($bios)) foreach ($bios as $bio) { // Localized
					array_push($authorText, strip_tags($bio));
				}
			}

			// Update search index
			$articleId = $article->getId();
			self::_updateTextIndex($articleId, SUBMISSION_SEARCH_AUTHOR, $authorText);
			self::_updateTextIndex($articleId, SUBMISSION_SEARCH_TITLE, $article->getTitle(null));
			self::_updateTextIndex($articleId, SUBMISSION_SEARCH_ABSTRACT, $article->getAbstract(null));

			self::_updateTextIndex($articleId, SUBMISSION_SEARCH_DISCIPLINE, (array) $article->getDiscipline(null));
			self::_updateTextIndex($articleId, SUBMISSION_SEARCH_SUBJECT, array_merge(array_values((array) $article->getSubjectClass(null)), array_values((array) $article->getSubject(null))));
			self::_updateTextIndex($articleId, SUBMISSION_SEARCH_TYPE, $article->getType(null));
			self::_updateTextIndex($articleId, SUBMISSION_SEARCH_COVERAGE, array_merge(array_values((array) $article->getCoverageGeo(null)), array_values((array) $article->getCoverageChron(null)), array_values((array) $article->getCoverageSample(null))));
			// FIXME Index sponsors too?
		}
	}

	/**
	 * Signal to the indexing back-end that an article file changed.
	 *
	 * @see ArticleSearchIndex::articleMetadataChanged() above for more
	 * comments.
	 *
	 * @param $articleId int
	 * @param $type int
	 * @param $fileId int
	 */
	function articleFileChanged($articleId, $type, $fileId) {
		// Check whether a search plug-in jumps in.
		$hookResult = HookRegistry::call(
			'ArticleSearchIndex::articleFileChanged',
			array($articleId, $type, $fileId)
		);

		// If no search plug-in is activated then fall back to the
		// default database search implementation.
		if ($hookResult === false || is_null($hookResult)) {
			import('classes.file.ArticleFileManager');
			$fileManager = new ArticleFileManager($articleId);
			$file = $fileManager->getFile($fileId);

			if (isset($file)) {
				$parser = SearchFileParser::fromFile($file);
			}

			if (isset($parser)) {
				if ($parser->open()) {
					$searchDao = DAORegistry::getDAO('ArticleSearchDAO');
					$objectId = $searchDao->insertObject($articleId, $type, $fileId);

					$position = 0;
					while(($text = $parser->read()) !== false) {
						self::_indexObjectKeywords($objectId, $text, $position);
					}
					$parser->close();
				}
			}
		}
	}

	/**
	 * Signal to the indexing back-end that all files (supplementary
	 * and galley) assigned to an article changed and must be re-indexed.
	 *
	 * @see ArticleSearchIndex::articleMetadataChanged() above for more
	 * comments.
	 *
	 * @param $article Article
	 */
	function articleFilesChanged(&$article) {
		// Check whether a search plug-in jumps in.
		$hookResult = HookRegistry::call(
			'ArticleSearchIndex::articleFilesChanged',
			array($article)
		);

		// If no search plug-in is activated then fall back to the
		// default database search implementation.
		if ($hookResult === false || is_null($hookResult)) {
			// Index supplementary files
			$fileDao = DAORegistry::getDAO('SuppFileDAO');
			$files = $fileDao->getSuppFilesByArticle($article->getId());
			foreach ($files as $file) {
				if ($file->getFileId()) {
					self::articleFileChanged($article->getId(), SUBMISSION_SEARCH_SUPPLEMENTARY_FILE, $file->getFileId());
				}
				self::suppFileMetadataChanged($file);
			}

			// Index galley files
			$fileDao = DAORegistry::getDAO('SubmissionFileDAO');
			$files = $submissionFiles = $fileDao->getLatestRevisions(
				$article->getId(), WORKFLOW_STAGE_ID_PRODUCTION
			);
			foreach ($files as $file) {
				if ($file->getFileId()) {
					self::articleFileChanged($article->getId(), SUBMISSION_SEARCH_GALLEY_FILE, $file->getFileId());
				}
			}
		}
	}

	/**
	 * Signal to the indexing back-end that a file was deleted.
	 *
	 * @see ArticleSearchIndex::articleMetadataChanged() above for more
	 * comments.
	 *
	 * @param $articleId int
	 * @param $type int optional
	 * @param $assocId int optional
	 */
	function articleFileDeleted($articleId, $type = null, $assocId = null) {
		// Check whether a search plug-in jumps in.
		$hookResult = HookRegistry::call(
			'ArticleSearchIndex::articleFileDeleted',
			array($articleId, $type, $assocId)
		);

		// If no search plug-in is activated then fall back to the
		// default database search implementation.
		if ($hookResult === false || is_null($hookResult)) {
			$searchDao = DAORegistry::getDAO('ArticleSearchDAO'); /* @var $searchDao ArticleSearchDAO */
			return $searchDao->deleteSubmissionKeywords($articleId, $type, $assocId);
		}
	}

	/**
	 * Signal to the indexing back-end that the metadata of
	 * a supplementary file changed.
	 *
	 * @see ArticleSearchIndex::articleMetadataChanged() above for more
	 * comments.
	 *
	 * @param $suppFile object
	 */
	function suppFileMetadataChanged(&$suppFile) {
		// Check whether a search plug-in jumps in.
		$hookResult = HookRegistry::call(
			'ArticleSearchIndex::suppFileMetadataChanged',
			array($suppFile)
		);

		// If no search plug-in is activated then fall back to the
		// default database search implementation.
		if ($hookResult === false || is_null($hookResult)) {
			// Update search index
			$articleId = $suppFile->getArticleId();
			self::_updateTextIndex(
				$articleId,
				SUBMISSION_SEARCH_SUPPLEMENTARY_FILE,
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
	}

	/**
	 * Signal to the indexing back-end that the metadata of
	 * a supplementary file changed.
	 *
	 * @see ArticleSearchIndex::articleMetadataChanged() above for more
	 * comments.
	 *
	 * @param $articleId integer
	 */
	function articleDeleted($articleId) {
		// Trigger a hook to let the indexing back-end know that
		// an article was deleted.
		$hookResult = HookRegistry::call(
			'ArticleSearchIndex::articleDeleted',
			array($articleId)
		);

		// The default indexing back-end does nothing when an
		// article is deleted (FIXME?).
	}

	/**
	 * Let the indexing back-end know that the current transaction
	 * finished so that the index can be batch-updated.
	 */
	function articleChangesFinished() {
		// Trigger a hook to let the indexing back-end know that
		// the index may be updated.
		$hookResult = HookRegistry::call(
			'ArticleSearchIndex::articleChangesFinished'
		);

		// The default indexing back-end works completely synchronously
		// and will therefore not do anything here.
	}

	/**
	 * Rebuild the search index for one or all journals.
	 * @param $log boolean Whether to display status information
	 *  to stdout.
	 * @param $journal Journal If given the user wishes to
	 *  re-index only one journal. Not all search implementations
	 *  may be able to do so. Most notably: The default SQL
	 *  implementation does not support journal-specific re-indexing
	 *  as index data is not partitioned by journal.
	 * @param $switches array Optional index administration switches.
	 */
	function rebuildIndex($log = false, $journal = null, $switches = array()) {
		// Check whether a search plug-in jumps in.
		$hookResult = HookRegistry::call(
			'ArticleSearchIndex::rebuildIndex',
			array($log, $journal, $switches)
		);

		// If no search plug-in is activated then fall back to the
		// default database search implementation.
		if ($hookResult === false || is_null($hookResult)) {
			// Check that no journal was given as we do
			// not support journal-specific re-indexing.
			if (is_a($journal, 'Journal')) die(__('search.cli.rebuildIndex.indexingByJournalNotSupported') . "\n");

			// Clear index
			if ($log) echo __('search.cli.rebuildIndex.clearingIndex') . ' ... ';
			$searchDao = DAORegistry::getDAO('ArticleSearchDAO');
			$searchDao->clearIndex();
			if ($log) echo __('search.cli.rebuildIndex.done') . "\n";

			// Build index
			$journalDao = DAORegistry::getDAO('JournalDAO');
			$articleDao = DAORegistry::getDAO('ArticleDAO');

			$journals = $journalDao->getAll();
			while ($journal = $journals->next()) {
				$numIndexed = 0;

				if ($log) echo __('search.cli.rebuildIndex.indexing', array('journalName' => $journal->getLocalizedName())) . ' ... ';

				$articles = $articleDao->getByJournalId($journal->getId());
				while ($article = $articles->next()) {
					if ($article->getDateSubmitted()) {
						self::articleMetadataChanged($article);
						self::articleFilesChanged($article);
						$numIndexed++;
					}
				}

				if ($log) echo __('search.cli.rebuildIndex.result', array('numIndexed' => $numIndexed)) . "\n";
			}
		}
	}


	//
	// Private helper methods
	//
	/**
	 * Index a block of text for an object.
	 * @param $objectId int
	 * @param $text string
	 * @param $position int
	 */
	function _indexObjectKeywords($objectId, $text, &$position) {
		$searchDao = DAORegistry::getDAO('ArticleSearchDAO');
		$keywords = self::filterKeywords($text);
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
	function _updateTextIndex($articleId, $type, $text, $assocId = null) {
		$searchDao = DAORegistry::getDAO('ArticleSearchDAO');
		$objectId = $searchDao->insertObject($articleId, $type, $assocId);
		$position = 0;
		self::_indexObjectKeywords($objectId, $text, $position);
	}
}

?>
