<?php
/**
 * @file classes/submissionFile/Repository.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Repository
 *
 * @brief A repository to find and manage submission files.
 */

namespace APP\submissionFile;

use APP\core\Application;
use APP\facades\Repo;
use Exception;
use Illuminate\Support\Facades\App;
use PKP\observers\events\SubmissionFileDeleted;
use PKP\plugins\Hook;
use PKP\submissionFile\Collector;
use PKP\submissionFile\Repository as BaseRepository;
use PKP\submissionFile\SubmissionFile;

class Repository extends BaseRepository
{
    public array $reviewFileStages = [
        SubmissionFile::SUBMISSION_FILE_REVIEW_REVISION,
        SubmissionFile::SUBMISSION_FILE_REVIEW_ATTACHMENT,
        SubmissionFile::SUBMISSION_FILE_REVIEW_FILE,
    ];

    public function getCollector(): Collector
    {
        return App::makeWith(Collector::class, ['dao' => $this->dao]);
    }

    public function add(SubmissionFile $submissionFile): int
    {
        $galley = null;

        if ($submissionFile->getData('assocType') === Application::ASSOC_TYPE_REPRESENTATION) {
            $galley = Repo::galley()->get($submissionFile->getData('assocId'));
            if (!$galley) {
                throw new Exception('Galley not found when adding submission file.');
            }
        }

        $submissionFileId = parent::add($submissionFile);

        if ($galley) {
            Repo::galley()->edit($galley, ['submissionFileId' => $submissionFile->getId()]);
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
            $galley = Repo::galley()->get((int)$submissionFile->getData('assocId'));
            if ($galley && $galley->getData('submissionFileId') == $submissionFile->getId()) {
                $galley->_data['submissionFileId'] = null; // Work around pkp/pkp-lib#5740
                Repo::galley()->edit($galley, []);
            }

            event(
                new SubmissionFileDeleted(
                    (int)$submissionFile->getData('submissionId'),
                    (int)$submissionFile->getId()
                )
            );
        }
    }

    public function getFileStages(): array
    {
        $stages = [
            SubmissionFile::SUBMISSION_FILE_SUBMISSION,
            SubmissionFile::SUBMISSION_FILE_NOTE,
            SubmissionFile::SUBMISSION_FILE_REVIEW_FILE,
            SubmissionFile::SUBMISSION_FILE_REVIEW_ATTACHMENT,
            SubmissionFile::SUBMISSION_FILE_FINAL,
            SubmissionFile::SUBMISSION_FILE_COPYEDIT,
            SubmissionFile::SUBMISSION_FILE_PROOF,
            SubmissionFile::SUBMISSION_FILE_PRODUCTION_READY,
            SubmissionFile::SUBMISSION_FILE_ATTACHMENT,
            SubmissionFile::SUBMISSION_FILE_REVIEW_REVISION,
            SubmissionFile::SUBMISSION_FILE_DEPENDENT,
            SubmissionFile::SUBMISSION_FILE_QUERY,
        ];

        Hook::call('SubmissionFile::fileStages', [&$stages]);

        return $stages;
    }
}
