<?php

/**
 * @defgroup api_v1_backend Backend API requests for submissions
 */

/**
 * @file api/v1/_submissions/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_backend
 * @brief Handle requests for backend API.
 *
 */

import('api.v1._submissions.BackendSubmissionsHandler');
return new BackendSubmissionsHandler();
