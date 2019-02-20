<?php
/**
 * @defgroup api_v1_emailTemplates Email templates API requests
 */

/**
 * @file api/v1/emailTemplates/index.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_emailTemplates
 * @brief Handle API requests for emailTemplates.
 */
import('lib.pkp.api.v1.emailTemplates.PKPEmailTemplateHandler');
return new PKPEmailTemplateHandler();
