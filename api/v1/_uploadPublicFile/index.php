<?php
/**
 * @defgroup api_v1_uploadPublicFile Email templates API requests
 */

/**
 * @file api/v1/uploadPublicFile/index.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_uploadPublicFile
 * @brief Handle API requests for uploadPublicFile.
 */
import('lib.pkp.api.v1._uploadPublicFile.PKPUploadPublicFileHandler');
return new PKPUploadPublicFileHandler();
