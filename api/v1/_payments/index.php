<?php

/**
 * @defgroup api_v1_backend Backend API requests for payments settings
 */

/**
 * @file api/v1/_payments/index.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_backend
 * @brief Handle requests for backend API.
 *
 */

import('api.v1._payments.BackendPaymentsSettingsHandler');
return new BackendPaymentsSettingsHandler();
