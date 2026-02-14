<?php

/**
 * @defgroup api_v1_genres Genre API requests
 */

/**
 * @file api/v1/genres/index.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_genres
 *
 * @brief Handle requests for genre API functions.
 *
 */

return new \PKP\handler\APIHandler(new \PKP\API\v1\genres\GenreController());
