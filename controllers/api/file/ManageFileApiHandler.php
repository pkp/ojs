<?php

/**
 * @file controllers/api/file/ManageFileApiHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ManageFileApiHandler
 * @ingroup controllers_api_file
 *
 * @brief Class defining an AJAX API for file manipulation.
 */

namespace APP\controllers\api\file;

use APP\controllers\tab\pubIds\form\PublicIdentifiersForm;
use APP\core\Application;
use PKP\controllers\api\file\PKPManageFileApiHandler;
use PKP\core\JSONMessage;
use PKP\db\DAO;
use PKP\security\Role;

class ManageFileApiHandler extends PKPManageFileApiHandler
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN, Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_ASSISTANT, Role::ROLE_ID_REVIEWER, Role::ROLE_ID_AUTHOR],
            ['identifiers', 'updateIdentifiers', 'clearPubId',]
        );
    }

    /**
     * Edit proof submission file pub ids.
     *
     * @param array $args
     * @param PKPRequest $request
     *
     * @return JSONMessage JSON object
     */
    public function identifiers($args, $request)
    {
        $submissionFile = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_SUBMISSION_FILE);
        $stageId = $request->getUserVar('stageId');
        $form = new PublicIdentifiersForm($submissionFile, $stageId);
        $form->initData();
        return new JSONMessage(true, $form->fetch($request));
    }

    /**
     * Update proof submission file pub ids.
     *
     * @param array $args
     * @param PKPRequest $request
     *
     * @return JSONMessage JSON object
     */
    public function updateIdentifiers($args, $request)
    {
        $submissionFile = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_SUBMISSION_FILE);
        $stageId = $request->getUserVar('stageId');
        $form = new PublicIdentifiersForm($submissionFile, $stageId);
        $form->readInputData();
        if ($form->validate()) {
            $form->execute();
            return DAO::getDataChangedEvent($submissionFile->getId());
        } else {
            return new JSONMessage(true, $form->fetch($request));
        }
    }

    /**
     * Clear proof submission file pub id.
     *
     * @param array $args
     * @param Request $request
     *
     * @return JSONMessage JSON object
     */
    public function clearPubId($args, $request)
    {
        if (!$request->checkCSRF()) {
            return new JSONMessage(false);
        }

        $submissionFile = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_SUBMISSION_FILE);
        $stageId = $request->getUserVar('stageId');
        $form = new PublicIdentifiersForm($submissionFile, $stageId);
        $form->clearPubId($request->getUserVar('pubIdPlugIn'));
        return new JSONMessage(true);
    }
}
