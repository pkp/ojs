<?php

/**
 * @defgroup api_v1_backend_users Backend API requests for users list
 */

/**
 * @file api/v1/backend/users/index.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_backend
 * @brief Handle requests for backend API users list.
 *
 */

import('api.v1.backend.users.BackendUsersHandler');
return new BackendUsersHandler();
