<?php

/**
 * @file classes/publication/Publication.inc.php
 *
 * Copyright (c) 2016-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Publication
 * @ingroup publication
 * @see PublicationDAO
 *
 * @brief Class for Publication.
 */
import('lib.pkp.classes.publication.PKPPublication');

class Publication extends PKPPublication {

	/**
	 * Get the URL to a localized cover image
	 *
	 * @param int $contextId
	 * @return string
	 */
	public function getLocalizedCoverImageUrl($contextId) {
		$coverImage = $this->getLocalizedData('coverImage');

		if (!$coverImage) {
			return '';
		}

		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();

		return join('/', [
			Application::get()->getRequest()->getBaseUrl(),
			$publicFileManager->getContextFilesPath($contextId),
			$coverImage['uploadName'],
		]);
	}
}


