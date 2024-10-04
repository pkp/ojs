<?php

/**
 * @defgroup api_v1_emails Email API requests
 */

/**
 * @file api/v1/emails/index.php
 *
 * Copyright (c) 2023 Simon Fraser University
 * Copyright (c) 2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_emails
 *
 * @brief Handle requests for emails API functions.
 *
 */
return new \PKP\handler\APIHandler(new \PKP\API\v1\emails\PKPEmailController());
