<?php

/**
 * @file classes/file/LibraryFileManager.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class LibraryFileManager
 * @ingroup file
 *
 * @brief Wrapper class for uploading files to a site/context' library directory.
 */

namespace APP\file;

use PKP\file\PKPLibraryFileManager;

class LibraryFileManager extends PKPLibraryFileManager
{
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\file\LibraryFileManager', '\LibraryFileManager');
}
