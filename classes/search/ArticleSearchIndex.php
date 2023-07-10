<?php

/**
 * @file classes/search/ArticleSearchIndex.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleSearchIndex
 *
 * @ingroup search
 *
 * @brief Class to maintain the article search index.
 */

namespace APP\search;

use APP\facades\Repo;
use APP\journal\Journal;
use APP\journal\JournalDAO;
use APP\submission\Submission;
use PKP\core\PKPApplication;
use PKP\db\DAORegistry;
use PKP\plugins\Hook;
use PKP\search\SearchFileParser;
use PKP\search\SubmissionSearch;
use PKP\search\SubmissionSearchIndex;
use PKP\submissionFile\SubmissionFile;

class ArticleSearchIndex extends SubmissionSearchIndex
{
    /**
     * @copydoc SubmissionSearchIndex::submissionMetadataChanged()
     */
    public function submissionMetadataChanged($submission)
    {
        // Check whether a search plug-in jumps in.
        $hookResult = Hook::call(
            'ArticleSearchIndex::articleMetadataChanged',
            [$submission]
        );

        if (!empty($hookResult)) {
            return;
        }

        $publication = $submission->getCurrentPublication();

        // Build author keywords
        $authorText = [];
        foreach ($publication->getData('authors') as $author) {
            $authorText = array_merge(
                $authorText,
                array_values((array) $author->getData('givenName')),
                array_values((array) $author->getData('familyName')),
                array_values((array) $author->getData('preferredPublicName')),
                array_values(array_map('strip_tags', (array) $author->getData('affiliation'))),
                array_values(array_map('strip_tags', (array) $author->getData('biography')))
            );
        }

        // Update search index
        $submissionId = $submission->getId();
        $this->_updateTextIndex($submissionId, SubmissionSearch::SUBMISSION_SEARCH_AUTHOR, $authorText);
        $this->_updateTextIndex($submissionId, SubmissionSearch::SUBMISSION_SEARCH_TITLE, $publication->getFullTitles());
        $this->_updateTextIndex($submissionId, SubmissionSearch::SUBMISSION_SEARCH_ABSTRACT, $publication->getData('abstract'));

        $this->_updateTextIndex($submissionId, SubmissionSearch::SUBMISSION_SEARCH_SUBJECT, (array) $this->_flattenLocalizedArray($publication->getData('subjects')));
        $this->_updateTextIndex($submissionId, SubmissionSearch::SUBMISSION_SEARCH_KEYWORD, (array) $this->_flattenLocalizedArray($publication->getData('keywords')));
        $this->_updateTextIndex($submissionId, SubmissionSearch::SUBMISSION_SEARCH_DISCIPLINE, (array) $this->_flattenLocalizedArray($publication->getData('disciplines')));
        $this->_updateTextIndex($submissionId, SubmissionSearch::SUBMISSION_SEARCH_TYPE, (array) $publication->getData('type'));
        $this->_updateTextIndex($submissionId, SubmissionSearch::SUBMISSION_SEARCH_COVERAGE, (array) $publication->getData('coverage'));
        // FIXME Index sponsors too?
    }

    /**
     * Delete keywords from the search index.
     *
     * @param int $articleId
     * @param int $type optional
     * @param int $assocId optional
     */
    public function deleteTextIndex($articleId, $type = null, $assocId = null)
    {
        $searchDao = DAORegistry::getDAO('ArticleSearchDAO'); /** @var ArticleSearchDAO $searchDao */
        return $searchDao->deleteSubmissionKeywords($articleId, $type, $assocId);
    }

    /**
     * Signal to the indexing back-end that an article file changed.
     *
     * @see ArticleSearchIndex::submissionMetadataChanged() above for more
     * comments.
     *
     * @param int $articleId
     * @param int $type
     * @param SubmissionFile $submissionFile
     */
    public function submissionFileChanged($articleId, $type, $submissionFile)
    {
        // Check whether a search plug-in jumps in.
        $hookResult = Hook::call(
            'ArticleSearchIndex::submissionFileChanged',
            [$articleId, $type, $submissionFile->getId()]
        );

        // If no search plug-in is activated then fall back to the
        // default database search implementation.
        if ($hookResult === false || is_null($hookResult)) {
            $parser = SearchFileParser::fromFile($submissionFile);
            if (isset($parser) && $parser->open()) {
                $searchDao = DAORegistry::getDAO('ArticleSearchDAO'); /** @var ArticleSearchDAO $searchDao */
                $objectId = $searchDao->insertObject($articleId, $type, $submissionFile->getId());

                while (($text = $parser->read()) !== false) {
                    $this->_indexObjectKeywords($objectId, $text);
                }
                $parser->close();
            }
        }
    }

    /**
     * Remove indexed file contents for a submission
     *
     * @param Submission $submission
     */
    public function clearSubmissionFiles($submission)
    {
        $searchDao = DAORegistry::getDAO('ArticleSearchDAO'); /** @var ArticleSearchDAO $searchDao */
        $searchDao->deleteSubmissionKeywords($submission->getId(), SubmissionSearch::SUBMISSION_SEARCH_GALLEY_FILE);
    }

    /**
     * Signal to the indexing back-end that all files (supplementary
     * and galley) assigned to an article changed and must be re-indexed.
     *
     * @see ArticleSearchIndex::submissionMetadataChanged() above for more
     * comments.
     *
     * @param Submission $article
     */
    public function submissionFilesChanged($article)
    {
        // Check whether a search plug-in jumps in.
        $hookResult = Hook::call(
            'ArticleSearchIndex::submissionFilesChanged',
            [$article]
        );

        // If no search plug-in is activated then fall back to the
        // default database search implementation.
        if ($hookResult === false || is_null($hookResult)) {
            $submissionFiles = Repo::submissionFile()
                ->getCollector()
                ->filterBySubmissionIds([$article->getId()])
                ->filterByFileStages([SubmissionFile::SUBMISSION_FILE_PROOF])
                ->getMany();

            foreach ($submissionFiles as $submissionFile) {
                $this->submissionFileChanged($article->getId(), SubmissionSearch::SUBMISSION_SEARCH_GALLEY_FILE, $submissionFile);
                $dependentFiles = Repo::submissionFile()->getCollector()
                    ->filterByAssoc(
                        PKPApplication::ASSOC_TYPE_SUBMISSION_FILE,
                        [$submissionFile->getId()]
                    )
                    ->filterBySubmissionIds([$article->getId()])
                    ->filterByFileStages([SubmissionFile::SUBMISSION_FILE_DEPENDENT])
                    ->includeDependentFiles()
                    ->getMany();

                foreach ($dependentFiles as $dependentFile) {
                    $this->submissionFileChanged(
                        $article->getId(),
                        SubmissionSearch::SUBMISSION_SEARCH_SUPPLEMENTARY_FILE,
                        $dependentFile
                    );
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
     * @param int $articleId
     * @param int $type optional
     * @param int $assocId optional
     */
    public function submissionFileDeleted($articleId, $type = null, $assocId = null)
    {
        // Check whether a search plug-in jumps in.
        $hookResult = Hook::call(
            'ArticleSearchIndex::submissionFileDeleted',
            [$articleId, $type, $assocId]
        );

        // If no search plug-in is activated then fall back to the
        // default database search implementation.
        if ($hookResult === false || is_null($hookResult)) {
            $searchDao = DAORegistry::getDAO('ArticleSearchDAO'); /** @var ArticleSearchDAO $searchDao */
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
     * @param int $articleId
     */
    public function articleDeleted($articleId)
    {
        // Trigger a hook to let the indexing back-end know that
        // an article was deleted.
        Hook::call(
            'ArticleSearchIndex::articleDeleted',
            [$articleId]
        );

        // The default indexing back-end does nothing when an
        // article is deleted (FIXME?).
    }

    /**
     * @copydoc SubmissionSearchIndex::submissionChangesFinished()
     */
    public function submissionChangesFinished()
    {
        // Trigger a hook to let the indexing back-end know that
        // the index may be updated.
        Hook::call(
            'ArticleSearchIndex::articleChangesFinished'
        );

        // The default indexing back-end works completely synchronously
        // and will therefore not do anything here.
    }

    /**
     * Rebuild the search index for one or all journals.
     *
     * @param bool $log Whether to display status information
     *  to stdout.
     * @param Journal $journal If given the user wishes to
     *  re-index only one journal. Not all search implementations
     *  may be able to do so. Most notably: The default SQL
     *  implementation does not support journal-specific re-indexing
     *  as index data is not partitioned by journal.
     * @param array $switches Optional index administration switches.
     */
    public function rebuildIndex($log = false, $journal = null, $switches = [])
    {
        // Check whether a search plug-in jumps in.
        $hookResult = Hook::call(
            'ArticleSearchIndex::rebuildIndex',
            [$log, $journal, $switches]
        );

        // If no search plug-in is activated then fall back to the
        // default database search implementation.
        if ($hookResult === false || is_null($hookResult)) {
            // Check that no journal was given as we do
            // not support journal-specific re-indexing.
            if ($journal instanceof Journal) {
                exit(__('search.cli.rebuildIndex.indexingByJournalNotSupported') . "\n");
            }

            // Clear index
            if ($log) {
                echo __('search.cli.rebuildIndex.clearingIndex') . ' ... ';
            }
            $searchDao = DAORegistry::getDAO('ArticleSearchDAO'); /** @var ArticleSearchDAO $searchDao */
            $searchDao->clearIndex();
            if ($log) {
                echo __('search.cli.rebuildIndex.done') . "\n";
            }

            // Build index
            $journalDao = DAORegistry::getDAO('JournalDAO'); /** @var JournalDAO $journalDao */

            $journals = $journalDao->getAll();
            while ($journal = $journals->next()) {
                $numIndexed = 0;

                if ($log) {
                    echo __('search.cli.rebuildIndex.indexing', ['journalName' => $journal->getLocalizedName()]) . ' ... ';
                }

                $submissions = Repo::submission()
                    ->getCollector()
                    ->filterByContextIds([$journal->getId()])
                    ->getMany();

                foreach ($submissions as $submission) {
                    if (!$submission->getSubmissionProgress()) { // Submission has been submitted
                        $this->submissionMetadataChanged($submission);
                        $this->submissionFilesChanged($submission);
                        $numIndexed++;
                    }
                }
                $this->submissionChangesFinished();

                if ($log) {
                    echo __('search.cli.rebuildIndex.result', ['numIndexed' => $numIndexed]) . "\n";
                }
            }
        }
    }

    //
    // Private helper methods
    //
    /**
     * Index a block of text for an object.
     *
     * @param int $objectId
     * @param string|array $text
     */
    protected function _indexObjectKeywords($objectId, $text)
    {
        $searchDao = DAORegistry::getDAO('ArticleSearchDAO'); /** @var ArticleSearchDAO $searchDao */
        $keywords = $this->filterKeywords($text);
        $searchDao->insertObjectKeywords($objectId, $keywords);
    }

    /**
     * Add a block of text to the search index.
     *
     * @param int $articleId
     * @param int $type
     * @param string|array $text
     * @param int $assocId optional
     */
    protected function _updateTextIndex($articleId, $type, $text, $assocId = null)
    {
        $searchDao = DAORegistry::getDAO('ArticleSearchDAO'); /** @var ArticleSearchDAO $searchDao */
        $objectId = $searchDao->insertObject($articleId, $type, $assocId);
        $this->_indexObjectKeywords($objectId, $text);
    }

    /**
     * Flattens array of localized fields to a single, non-associative array of items
     *
     * @param array $arrayWithLocales Array of localized fields
     *
     * @return array
     */
    protected function _flattenLocalizedArray($arrayWithLocales)
    {
        $flattenedArray = [];
        foreach ($arrayWithLocales as $localeArray) {
            $flattenedArray = array_merge(
                $flattenedArray,
                $localeArray
            );
        }
        return $flattenedArray;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\search\ArticleSearchIndex', '\ArticleSearchIndex');
}
