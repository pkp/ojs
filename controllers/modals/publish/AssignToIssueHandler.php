<?php

/**
 * @file controllers/modals/publish/AssignToIssueHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AssignToIssueHandler
 *
 * @ingroup controllers_modals_publish
 *
 * @brief A handler to assign a publication to an issue before scheduling for
 *   publication
 */

namespace APP\controllers\modals\publish;

use APP\components\forms\publication\AssignToIssueForm;
use APP\core\Application;
use APP\core\Request;
use APP\core\Services;
use APP\handler\Handler;
use APP\publication\Publication;
use APP\submission\Submission;
use APP\template\TemplateManager;
use PKP\core\JSONMessage;
use PKP\core\PKPApplication;
use PKP\security\authorization\PublicationAccessPolicy;
use PKP\security\authorization\SubmissionAccessPolicy;
use PKP\security\Role;

class AssignToIssueHandler extends Handler
{
    /** @var Submission */
    public $submission;

    /** @var Publication */
    public $publication;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            [Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN, Role::ROLE_ID_ASSISTANT],
            ['assign']
        );
    }


    //
    // Overridden methods from Handler
    //
    /**
     * @copydoc PKPHandler::initialize()
     */
    public function initialize($request)
    {
        parent::initialize($request);
        $this->submission = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_SUBMISSION);
        $this->publication = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_PUBLICATION);
        $this->setupTemplate($request);
    }

    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
        $this->addPolicy(new PublicationAccessPolicy($request, $args, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }


    //
    // Public handler methods
    //
    /**
     * Display a form to assign an issue to a publication
     *
     * @param array $args
     * @param Request $request
     *
     * @return JSONMessage JSON object
     */
    public function assign($args, $request)
    {
        $templateMgr = TemplateManager::getManager($request);

        $submissionContext = $request->getContext();
        if (!$submissionContext || $submissionContext->getId() !== $this->submission->getData('contextId')) {
            $submissionContext = Services::get('context')->get($this->submission->getData('contextId'));
        }

        $publicationApiUrl = $request->getDispatcher()->url($request, PKPApplication::ROUTE_API, $submissionContext->getPath(), 'submissions/' . $this->submission->getId() . '/publications/' . $this->publication->getId());
        $assignToIssueForm = new AssignToIssueForm($publicationApiUrl, $this->publication, $submissionContext);
        $settingsData = [
            'components' => [
                FORM_ASSIGN_TO_ISSUE => $assignToIssueForm->getConfig(),
            ],
        ];

        $templateMgr->assign('assignData', $settingsData);

        return $templateMgr->fetchJson('controllers/modals/publish/assignToIssue.tpl');
    }
}
