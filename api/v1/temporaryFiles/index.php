<?php
/**
 * @defgroup api_v1_temporaryFiles Temporary file upload API requests
 */

/**
 * @file api/v1/temporaryFiles/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_temporaryFiles
 * @brief Handle API requests for temporary file uploading.
 */
import('lib.pkp.api.v1.temporaryFiles.PKPTemporaryFilesHandler');
return new PKPTemporaryFilesHandler();
