<?php

/**
 * @defgroup decision Decision
 */

/**
 * @file classes/decision/Decision.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Decision
 * @ingroup decision
 *
 * @see DAO
 *
 * @brief An editorial decision taken on a submission, such as to accept, decline or request revisions.
 */

namespace APP\decision;

use PKP\decision\Decision as BaseDecision;

class Decision extends BaseDecision
{
    public const EXTERNAL_REVIEW = 8;
    public const ACCEPT = 1;
    public const DECLINE = 4;
    public const PENDING_REVISIONS = 2;
    public const RESUBMIT = 3;
    public const NEW_ROUND = 16;
}

if (!PKP_STRICT_MODE) {
    define('EXTERNAL_REVIEW', Decision::EXTERNAL_REVIEW);
    define('ACCEPT', Decision::ACCEPT);
    define('DECLINE', Decision::DECLINE);
    define('PENDING_REVISIONS', Decision::PENDING_REVISIONS);
    define('RESUBMIT', Decision::RESUBMIT);
    define('NEW_ROUND', Decision::NEW_ROUND);
}
