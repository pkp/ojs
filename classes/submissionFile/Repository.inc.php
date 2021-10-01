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

use Exception;
use PKP\core\PKPApplication;
use PKP\db\DAORegistry;
use PKP\submissionFile\Repository as BaseRepository;
use PKP\submissionFile\SubmissionFile;

class Repository extends BaseRepository
{
    public function add(SubmissionFile $submissionFile): int
    {
        $submissionId = parent::add($submissionFile);

        if ($submissionFile->getData('assocType') === PKPApplication::ASSOC_TYPE_REPRESENTATION) {
            $galleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */
            $galley = $galleyDao->getById($submissionFile->getData('assocId'));
            if (!$galley) {
                throw new Exception('Galley not found when adding submission file.');
            }
            $galley->setFileId($submissionFile->getId());
            $galleyDao->updateObject($galley);
        }

        return $submissionId;
    }
}
