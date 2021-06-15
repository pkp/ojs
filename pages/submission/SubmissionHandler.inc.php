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
            ['index', 'wizard', 'step', 'saveStep', 'fetchChoices']
        );
    }


    //
    // Public methods
    //
    /**
     * Retrieves a JSON list of available choices for a tagit metadata input field.
     *
     * @param $args array
     * @param $request Request
     */
    public function fetchChoices($args, $request)
    {
        $term = $request->getUserVar('term');
        $locale = $request->getUserVar('locale');
        if (!$locale) {
            $locale = AppLocale::getLocale();
        }
        switch ($request->getUserVar('list')) {
            case 'languages':
                $isoCodes = new \Sokil\IsoCodes\IsoCodesFactory(\Sokil\IsoCodes\IsoCodesFactory::OPTIMISATION_IO);
                $matches = [];
                foreach ($isoCodes->getLanguages() as $language) {
                    if (!$language->getAlpha2() || $language->getType() != 'L' || $language->getScope() != 'I') {
                        continue;
                    }
                    if (stristr($language->getLocalName(), $term)) {
                        $matches[$language->getAlpha3()] = $language->getLocalName();
                    }
                };
                header('Content-Type: text/json');
                echo json_encode($matches);
                // no break
            default:
                assert(false);
        }
    }


    //
    // Protected helper methods
    //
    /**
     * Setup common template variables.
     *
     * @param $request Request
     */
    public function setupTemplate($request)
    {
        AppLocale::requireComponents(LOCALE_COMPONENT_APP_AUTHOR);
        return parent::setupTemplate($request);
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
