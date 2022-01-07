<?php
/**
 * @file classes/submissionFile/Repository.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class submissionFile
 *
 * @brief A repository to find and manage submission files.
 */

namespace APP\submissionFile;

use APP\core\Application;
use Exception;
use PKP\db\DAORegistry;
use PKP\search\SubmissionSearch;
use PKP\submissionFile\Repository as BaseRepository;
use PKP\submissionFile\SubmissionFile;

class Repository extends BaseRepository
{
    public function add(SubmissionFile $submissionFile): int
    {
        $galley = null;

        if ($submissionFile->getData('assocType') === Application::ASSOC_TYPE_REPRESENTATION) {
            $galleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /** @var ArticleGalleyDAO $galleyDao */
            $galley = $galleyDao->getById($submissionFile->getData('assocId'));
            if (!$galley) {
                throw new Exception('Galley not found when adding submission file.');
            }
        }

        $submissionFileId = parent::add($submissionFile);

        if ($galley) {
            $galley->setFileId($submissionFile->getId());
            $galleyDao->updateObject($galley);
        }

        return $submissionFileId;
    }

    public function delete(SubmissionFile $submissionFile): void
    {
        $this->deleteRelatedSubmissionFileObjects($submissionFile);
        parent::delete($submissionFile);
    }

    /**
     * Delete related objects when a submission file is deleted
     */
    public function deleteRelatedSubmissionFileObjects(SubmissionFile $submissionFile): void
    {
        // Remove galley associations and update search index
        if ($submissionFile->getData('assocType') === Application::ASSOC_TYPE_REPRESENTATION) {
            $galleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /** @var ArticleGalleyDAO $galleyDao */
            $galley = $galleyDao->getById($submissionFile->getData('assocId'));
            if ($galley && $galley->getData('submissionFileId') == $submissionFile->getId()) {
                $galley->_data['submissionFileId'] = null; // Work around pkp/pkp-lib#5740
                $galleyDao->updateObject($galley);
            }
            $articleSearchIndex = Application::getSubmissionSearchIndex();
            $articleSearchIndex->deleteTextIndex($submissionFile->getData('submissionId'), SubmissionSearch::SUBMISSION_SEARCH_GALLEY_FILE, $submissionFile->getId());
        }
    }
}
