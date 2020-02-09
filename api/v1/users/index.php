<?php

/**
 * @defgroup api_v1_users User API requests
 */

/**
 * @file api/v1/users/index.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_users
 * @brief Handle requests for user API functions.
 *
 */

import('api.v1.users.UserHandler');
return new UserHandler();
