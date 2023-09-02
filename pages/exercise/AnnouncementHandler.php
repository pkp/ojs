<?php

/**
 * @file pages/about/AboutHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AboutHandler
 *
 * @ingroup pages_about
 *
 * @brief Handle requests for journal about functions.
 */

namespace APP\pages\exercise;

use APP\core\Request;
use APP\facades\Repo;
use APP\handler\Handler;
use APP\template\TemplateManager;
use JSONMessage;
use PKP\security\authorization\PKPSiteAccessPolicy;
use PKP\security\Role;

class AnnouncementHandler extends Handler
{
    /** @copydoc PKPHandler::_isBackendPage */
    public $_isBackendPage = true;

    public function __construct()
    {
        parent::__construct();

        $this->addRoleAssignment(
            [Role::ROLE_ID_SITE_ADMIN, Role::ROLE_ID_MANAGER],
            ['index', 'announcements', 'users']
        );
    }


    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new PKPSiteAccessPolicy($request, null, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    public function index($args, $request)
    {
        $currentUser = $request->getUser();
        if (!$currentUser) {
            return new JSONMessage(false);
        }

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'pageTitle' => __('exercises.exercises'),
        ]);
        $templateMgr->display('frontend/pages/exerciseIndex.tpl');
    }

    public function announcements($args, Request $request)
    {
        $this->setupTemplate($request);
        $currentJournalId = $request->getContext()->getId();

        $announcements = Repo::announcement()
            ->getCollector()
            ->filterByContextIds([$currentJournalId])
            ->getMany();

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'announcements' => $announcements,
            'pageTitle' => __('exercises.announcementsExample'),
        ]);
        $templateMgr->display('frontend/pages/announcements.tpl');
    }

    public function users($args, $request)
    {
        $currentUser = $request->getUser();
        if (!$currentUser) {
            return new JSONMessage(false);
        }

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'pageTitle' => __('exercises.users'),
        ]);
        $templateMgr->display('frontend/pages/users.tpl');
    }
}
