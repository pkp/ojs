<?php

/**
 * @defgroup api_v1_files File API requests
 */

/**
 * @file api/v1/files/index.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_files
 * @brief Handle requests for files API functions.
 *
 */

import('api.v1.files.FilesHandler');
return new FilesHandler();
