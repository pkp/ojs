<?php 

/**
 * @defgroup api_v1_file File API requests
 */

/**
 * @file api/v1/file/index.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_file
 * @brief Handle requests for file API functions.
 *
 */

import('api.v1.file.FileHandler');
return FileHandler::init();
