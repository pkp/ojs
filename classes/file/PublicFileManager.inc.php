<?php

/**
 * @file classes/file/PublicFileManager.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicFileManager
 * @ingroup file
 *
 * @brief Wrapper class for uploading files to a site/journal's public directory.
 */

import('lib.pkp.classes.file.PKPPublicFileManager');

class PublicFileManager extends PKPPublicFileManager {
	/**
	 * @copydoc PKPPublicFileManager::getContextFilesPath()
	 */
	public function getContextFilesPath($contextId) {
		return Config::getVar('files', 'public_files_dir') . '/journals/' . (int) $contextId;
	}
}

