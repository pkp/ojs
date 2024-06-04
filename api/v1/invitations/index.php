<?php

/**
 * @defgroup api_v1_invitations Invitations API requests
 */

/**
 * @file api/v1/invitations/index.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_invitations
 *
 * @brief Handle API requests for invitations.
 */

use PKP\API\v1\invitations\InvitationController;

return new \PKP\handler\APIHandler(new InvitationController());
