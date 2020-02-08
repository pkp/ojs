<?php

/**
 * @defgroup api_v1_submissions Submission API requests
 */

/**
 * @file api/v1/submissions/index.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_submissions
 * @brief Handle requests for submission API functions.
 *
 */
import('lib.pkp.api.v1.submissions.PKPSubmissionHandler');
return new PKPSubmissionHandler();