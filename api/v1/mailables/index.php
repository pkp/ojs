<?php
/**
 * @defgroup api_v1_mailables Email templates API requests
 */

/**
 * @file api/v1/mailables/index.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2003-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_mailables
 * @brief Handle API requests for mailables.
 */

use PKP\api\v1\mailables\MailableHandler;

import('lib.pkp.api.v1.mailables.MailableHandler');
return new MailableHandler();
