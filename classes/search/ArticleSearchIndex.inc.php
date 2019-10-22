<?php

/**
 * @file classes/search/ArticleSearchIndex.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
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
	 * @copydoc SubmissionSearchIndex::submissionMetadataChanged()
	 */
	public function submissionMetadataChanged($submission) {
		// Check whether a search plug-in jumps in.
		$hookResult = HookRegistry::call(
			'ArticleSearchIndex::articleMetadataChanged',
			array($submission)
		);

		// If no search plug-in is activated then fall back to the
		// default database search implementation.
		if ($hookResult === false || is_null($hookResult)) {
			// Build author keywords
			$authorText = array();
			foreach ($submission->getCurrentPublication()->getData('authors') as $author) {
				foreach ((array) $author->getGivenName(null) as $givenName) { // Localized
					array_push($authorText, $givenName);
				}
				foreach ((array) $author->getFamilyName(null) as $familyName) { // Localized
					array_push($authorText, $familyName);
				}
				foreach ((array) $author->getAffiliation(null) as $affiliation) { // Localized
					array_push($authorText, $affiliation);
				}
				foreach ((array) $author->getBiography(null) as $bio) { // Localized
					array_push($authorText, strip_tags($bio));
				}
			}

			// Update search index
			$submissionId = $submission->getId();
			$this->_updateTextIndex($submissionId, SUBMISSION_SEARCH_AUTHOR, $authorText);
			$this->_updateTextIndex($submissionId, SUBMISSION_SEARCH_TITLE, $submission->getFullTitle(null));
			$this->_updateTextIndex($submissionId, SUBMISSION_SEARCH_ABSTRACT, $submission->getAbstract(null));

			$this->_updateTextIndex($submissionId, SUBMISSION_SEARCH_DISCIPLINE, (array) $submission->getDiscipline(null));
			$this->_updateTextIndex($submissionId, SUBMISSION_SEARCH_SUBJECT, (array) $submission->getSubject(null));
			$this->_updateTextIndex($submissionId, SUBMISSION_SEARCH_TYPE, $submission->getType(null));
			$this->_updateTextIndex($submissionId, SUBMISSION_SEARCH_COVERAGE, (array) $submission->getCoverage(null));
			// FIXME Index sponsors too?
		}
	}

	/**
	 * @copydoc SubmissionSearchIndex::submissionMetadataChanged()
	 */
	public function articleMetadataChanged($article) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated call to articleMetadataChanged. Use submissionMetadataChanged instead.');
		$this->submissionMetadataChanged($article);
	}

	/**
	 * Delete keywords from the search index.
	 * @param $articleId int
	 * @param $type int optional
	 * @param $assocId int optional
	 */
	public function deleteTextIndex($articleId, $type = null, $assocId = null) {
		$searchDao = DAORegistry::getDAO('ArticleSearchDAO');
		return $searchDao->deleteSubmissionKeywords($articleId, $type, $assocId);
	}

	/**
	 * Signal to the indexing back-end that an article file changed.
	 *
	 * @see ArticleSearchIndex::submissionMetadataChanged() above for more
	 * comments.
	 *
	 * @param $articleId int
	 * @param $type int
	 * @param $fileId int
	 */
	public function submissionFileChanged($articleId, $type, $fileId) {
		// Check whether a search plug-in jumps in.
		$hookResult = HookRegistry::call(
			'ArticleSearchIndex::submissionFileChanged',
			array($articleId, $type, $fileId)
		);

		// If no search plug-in is activated then fall back to the
		// default database search implementation.
		if ($hookResult === false || is_null($hookResult)) {
			$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
			$file = $submissionFileDao->getLatestRevision($fileId);
			if (isset($file)) {
				$parser = SearchFileParser::fromFile($file);
			}

			if (isset($parser) && $parser->open()) {
				$searchDao = DAORegistry::getDAO('ArticleSearchDAO');
				$objectId = $searchDao->insertObject($articleId, $type, $fileId);

				$position = 0;
				while(($text = $parser->read()) !== false) {
					$this->_indexObjectKeywords($objectId, $text, $position);
				}
				$parser->close();
			}
		}
	}

	/**
	 * Signal to the indexing back-end that all files (supplementary
	 * and galley) assigned to an article changed and must be re-indexed.
	 *
	 * @see ArticleSearchIndex::submissionMetadataChanged() above for more
	 * comments.
	 *
	 * @param $article Article
	 */
	public function submissionFilesChanged($article) {
		// Check whether a search plug-in jumps in.
		$hookResult = HookRegistry::call(
			'ArticleSearchIndex::submissionFilesChanged',
			array($article)
		);

		// If no search plug-in is activated then fall back to the
		// default database search implementation.
		if ($hookResult === false || is_null($hookResult)) {
			$fileDao = DAORegistry::getDAO('SubmissionFileDAO');
			import('lib.pkp.classes.submission.SubmissionFile'); // Constants
			// Index galley files
			$files = $fileDao->getLatestRevisions(
				$article->getId(), SUBMISSION_FILE_PROOF
			);
			foreach ($files as $file) {
				if ($file->getFileId()) {
					$this->submissionFileChanged($article->getId(), SUBMISSION_SEARCH_GALLEY_FILE, $file->getFileId());
					// Index dependent files associated with any galley files.
					$dependentFiles = $fileDao->getLatestRevisionsByAssocId(ASSOC_TYPE_SUBMISSION_FILE, $file->getFileId(), $article->getId(), SUBMISSION_FILE_DEPENDENT);
					foreach ($dependentFiles as $depFile) {
						if ($depFile->getFileId()) {
							$this->submissionFileChanged($article->getId(), SUBMISSION_SEARCH_SUPPLEMENTARY_FILE, $depFile->getFileId());
						}
					}
				}
			}
		}
	}

	/**
	 * Signal to the indexing back-end that a file was deleted.
	 *
	 * @see ArticleSearchIndex::submissionMetadataChanged() above for more
	 * comments.
	 *
	 * @param $articleId int
	 * @param $type int optional
	 * @param $assocId int optional
	 */
	public function submissionFileDeleted($articleId, $type = null, $assocId = null) {
		// Check whether a search plug-in jumps in.
		$hookResult = HookRegistry::call(
			'ArticleSearchIndex::submissionFileDeleted',
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
	 * @see ArticleSearchIndex::submissionMetadataChanged() above for more
	 * comments.
	 *
	 * @param $articleId integer
	 */
	public function articleDeleted($articleId) {
		// Trigger a hook to let the indexing back-end know that
		// an article was deleted.
		HookRegistry::call(
			'ArticleSearchIndex::articleDeleted',
			array($articleId)
		);

		// The default indexing back-end does nothing when an
		// article is deleted (FIXME?).
	}

	/**
	 * @copydoc SubmissionSearchIndex::submissionChangesFinished()
	 */
	public function submissionChangesFinished() {
		// Trigger a hook to let the indexing back-end know that
		// the index may be updated.
		HookRegistry::call(
			'ArticleSearchIndex::articleChangesFinished'
		);

		// The default indexing back-end works completely synchronously
		// and will therefore not do anything here.
	}

	/**
	 * @copydoc SubmissionSearchIndex::submissionChangesFinished()
	 */
	public function articleChangesFinished() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated call to articleChangesFinished. Use submissionChangesFinished instead.');
		$this->submissionChangesFinished();
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
	public function rebuildIndex($log = false, $journal = null, $switches = array()) {
		// Check whether a search plug-in jumps in.
		$hookResult = HookRegistry::call(
			'ArticleSearchIndex::rebuildIndex',
			array($log, $journal, $switches)
		);

		// If no search plug-in is activated then fall back to the
		// default database search implementation.
		if ($hookResult === false || is_null($hookResult)) {

			AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON);

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

			$journals = $journalDao->getAll();
			while ($journal = $journals->next()) {
				$numIndexed = 0;

				if ($log) echo __('search.cli.rebuildIndex.indexing', array('journalName' => $journal->getLocalizedName())) . ' ... ';

				$result = Services::get('submission')->getMany(['contextId' => $journal->getId()]);
				foreach ($result as $submission) {
					if ($submission->getSubmissionProgress() == 0) { // Not incomplete
						$this->submissionMetadataChanged($submission);
						$this->submissionFilesChanged($submission);
						$numIndexed++;
					}
				}
				$this->submissionChangesFinished();

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
	protected function _indexObjectKeywords($objectId, $text, &$position) {
		$searchDao = DAORegistry::getDAO('ArticleSearchDAO');
		$keywords = $this->filterKeywords($text);
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
	protected function _updateTextIndex($articleId, $type, $text, $assocId = null) {
		$searchDao = DAORegistry::getDAO('ArticleSearchDAO');
		$objectId = $searchDao->insertObject($articleId, $type, $assocId);
		$position = 0;
		$this->_indexObjectKeywords($objectId, $text, $position);
	}
}


