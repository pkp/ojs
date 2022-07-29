<?php

/**
 * @file pages/submission/SubmissionHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionHandler
 * @ingroup pages_submission
 *
 * @brief Handle requests for the submission wizard.
 */

use PKP\facades\Locale;
use PKP\security\Role;

import('lib.pkp.pages.submission.PKPSubmissionHandler');

class SubmissionHandler extends PKPSubmissionHandler
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            [Role::ROLE_ID_AUTHOR, Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_MANAGER],
            ['index', 'wizard', 'step', 'saveStep']
        );
    }

    /**
     * Get the step numbers and their corresponding title locale keys.
     *
     * @return array
     */
    public function getStepsNumberAndLocaleKeys()
    {
        return [
            1 => 'author.submit.start',
            2 => 'author.submit.upload',
            3 => 'author.submit.metadata',
            4 => 'author.submit.confirmation',
            5 => 'author.submit.nextSteps',
        ];
    }

    /**
     * Get the number of submission steps.
     *
     * @return int
     */
    public function getStepCount()
    {
        return 5;
    }
}
