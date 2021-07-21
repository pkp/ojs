<?php
/**
 * @file classes/services/SubmissionFileService.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFileService
 * @ingroup services
 *
 * @brief Extends the base submission file service class with app-specific
 *  requirements.
 */

namespace APP\services;

use APP\core\Application;
use APP\facades\Repo;
use PKP\plugins\HookRegistry;

use PKP\search\SubmissionSearch;

class SubmissionFileService extends \PKP\services\PKPSubmissionFileService
{
    /**
     * Initialize hooks for extending PKPSubmissionFileService
     */
    public function __construct()
    {
        HookRegistry::register('SubmissionFile::delete::before', [$this, 'deleteSubmissionFile']);
    }

    /**
     * Delete related objects when a submission file is deleted
     *
     * @param string $hookName
     * @param array $args [
     *      @option SubmissionFile
     * ]
     *
     * @return array
     */
    public function deleteSubmissionFile($hookName, $args)
    {
        $submissionFile = $args[0];

        // Remove galley associations and update search index
        if ($submissionFile->getData('assocType') == ASSOC_TYPE_REPRESENTATION) {
            //TODO GalleyDAO review ok
            $galley = Repo::articleGalley()->get($submissionFile->getData('assocId'));
            if ($galley && $galley->getData('submissionFileId') == $submissionFile->getId()) {
                $galley->_data['submissionFileId'] = null; // Work around pkp/pkp-lib#5740
                Repo::articleGalley()->dao->update($galley);
            }
            $articleSearchIndex = Application::getSubmissionSearchIndex();
            $articleSearchIndex->deleteTextIndex($submissionFile->getData('submissionId'), SubmissionSearch::SUBMISSION_SEARCH_GALLEY_FILE, $submissionFile->getId());
        }
    }
}
