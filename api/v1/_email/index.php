<?php

/**
 * @defgroup api_v1__email User API requests
 */

/**
 * @file api/v1/_email/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1__email
 * @brief Handle requests for user API functions.
 *
 */

import('lib.pkp.api.v1._email.PKPEmailHandler');
return new PKPEmailHandler();
