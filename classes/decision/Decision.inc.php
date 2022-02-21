<?php

/**
 * @defgroup decision Decision
 */

/**
 * @file classes/decision/Decision.inc.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
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
    public const ACCEPT = 1;
    public const PENDING_REVISIONS = 2;
    public const RESUBMIT = 3;
    public const DECLINE = 4;
    public const SEND_TO_PRODUCTION = 7;
    public const EXTERNAL_REVIEW = 8;
    public const RECOMMEND_ACCEPT = 11;
    public const RECOMMEND_PENDING_REVISIONS = 12;
    public const RECOMMEND_RESUBMIT = 13;
    public const RECOMMEND_DECLINE = 14;
    public const NEW_EXTERNAL_ROUND = 16;
    public const REVERT_DECLINE = 17;
    public const SKIP_EXTERNAL_REVIEW = 19;
    public const BACK_TO_REVIEW = 20;
    public const BACK_TO_COPYEDITING = 21;
    public const BACK_TO_SUBMISSION_FROM_COPYEDITING = 22;
}

if (!PKP_STRICT_MODE) {
    // Some constants are not redefined here because they never existed as global constants
    define('SUBMISSION_EDITOR_DECISION_EXTERNAL_REVIEW', Decision::EXTERNAL_REVIEW);
    define('SUBMISSION_EDITOR_DECISION_ACCEPT', Decision::ACCEPT);
    define('SUBMISSION_EDITOR_DECISION_DECLINE', Decision::DECLINE);
    define('SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS', Decision::PENDING_REVISIONS);
    define('SUBMISSION_EDITOR_DECISION_RESUBMIT', Decision::RESUBMIT);
    define('SUBMISSION_EDITOR_RECOMMEND_ACCEPT', Decision::RECOMMEND_ACCEPT);
    define('SUBMISSION_EDITOR_RECOMMEND_PENDING_REVISIONS', Decision::RECOMMEND_PENDING_REVISIONS);
    define('SUBMISSION_EDITOR_RECOMMEND_RESUBMIT', Decision::RECOMMEND_RESUBMIT);
    define('SUBMISSION_EDITOR_RECOMMEND_DECLINE', Decision::RECOMMEND_DECLINE);
    define('SUBMISSION_EDITOR_DECISION_REVERT_DECLINE', Decision::REVERT_DECLINE);
    define('SUBMISSION_EDITOR_DECISION_SEND_TO_PRODUCTION', Decision::SEND_TO_PRODUCTION);
    define('SUBMISSION_EDITOR_DECISION_NEW_ROUND', Decision::NEW_EXTERNAL_ROUND);
}
