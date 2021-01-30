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

namespace APP\Services;

use \DAORegistry;
use \Application;

class SubmissionFileService extends \PKP\Services\PKPSubmissionFileService {

	/**
	 * Initialize hooks for extending PKPSubmissionService
	 */
	public function __construct() {
		\HookRegistry::register('SubmissionFile::delete::before', array($this, 'deleteSubmissionFile'));
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
	public function deleteSubmissionFile($hookName, $args) {
		$submissionFile = $args[0];

		// Remove galley associations and update search index
		if ($submissionFile->getData('assocType') == ASSOC_TYPE_REPRESENTATION) {
			$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */
			$galley = $galleyDao->getById($submissionFile->getData('assocId'));
			if ($galley && $galley->getData('submissionFileId') == $submissionFile->getId()) {
				$galley->_data['submissionFileId'] = null; // Work around pkp/pkp-lib#5740
				$galleyDao->updateObject($galley);
			}
			import('lib.pkp.classes.search.SubmissionSearch');
			$articleSearchIndex = Application::getSubmissionSearchIndex();
			$articleSearchIndex->deleteTextIndex($submissionFile->getData('submissionId'), SUBMISSION_SEARCH_GALLEY_FILE, $submissionFile->getId());
		}
	}
}
