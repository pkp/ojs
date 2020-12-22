<?php declare(strict_types = 1);

/**
 * @file pages/exercise/ExerciseHandler.inc.php
 *
 * Copyright (c) 2020 Simon Fraser University
 * Copyright (c) 2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ExerciseHandler
 * @ingroup pages_index
 *
 * @brief Handle site index requests.
 */

import('lib.pkp.classes.security.authorization.PKPSiteAccessPolicy');
import('classes.handler.Handler');

class ExerciseHandler extends Handler
{
    protected $templateMgr;

    protected $currentRequest;

    public function __construct() {
        $roles = [ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER];

        $this->addRoleAssignment($roles, ['announcements', 'users', 'index']);

        parent::__construct($roles);
    }

    public function authorize(
        $request,
        &$args,
        $roleAssignments
    ) {
        $this->addPolicy(new PKPSiteAccessPolicy($request, null, $roleAssignments));

        return parent::authorize($request, $args, $roleAssignments);
    }

    public function initialize($request) {
        $this->templateMgr = TemplateManager::getManager($request);

        $this->templateMgr->assign([
            'exerciseLink' => $request->getRouter()->url($request, null, 'exercise'),
            'announcementsLink' => $request->getRouter()->url($request, null, 'exercise', 'announcements'),
            'usersLink' => $request->getRouter()->url($request, null, 'exercise', 'users'),
            'pageTitle' => __('exercise.index.title'),
        ]);

        $this->currentRequest = $request;

        parent::initialize($request);
    }

    public function announcements($args, $request) {
        if (isset($args[0]) && is_numeric($args[0])) {
            return $this->viewAnnouncement((int) $args[0]);
        }

        $announcementsIterator = Services::get('announcement')->getMany([
            'isEnabled' => true,
        ]);

        $announcements = [];
        foreach ($announcementsIterator as $announcement) {
            $announcementId = $announcement->getData('id');
            $uri = $request->getRouter()->url(
                $request,
                null,
                'exercise',
                'announcements',
                $announcementId
            );

            $announcements[] = [
                'id' => $announcementId,
                'uri' => $uri,
                'datePosted' => $announcement->getData('datePosted'),
                'title' => $announcement->getLocalizedData('title'),
                'description' => $announcement->getLocalizedData('description'),
                'descriptionShort' => $announcement->getLocalizedData('descriptionShort'),
                'keyword' => $announcement->getData('keyword'),
            ];
        }

        $this->templateMgr->assign([
            'announcements' => $announcements,
            'pageTitle' => __('announcement.announcements'),
        ]);

        $this->templateMgr->display('exercise/announcements.tpl');
    }

    public function viewAnnouncement(int $announcementId) {
        $announcementObject = Services::get('announcement')->get($announcementId);

        $announcementId = $announcementObject->getData('id');
        $uri = $this->currentRequest->getRouter()->url($this->currentRequest, null, 'exercise', 'announcements', $announcementId);
        $announcementArray = [
            'id' => $announcementId,
            'uri' => $uri,
            'datePosted' => $announcementObject->getData('datePosted'),
            'title' => $announcementObject->getLocalizedData('title'),
            'description' => $announcementObject->getLocalizedData('description'),
            'descriptionShort' => $announcementObject->getLocalizedData('descriptionShort'),
            'keyword' => $announcementObject->getData('keyword'),
        ];

        $this->templateMgr->assign([
            'announcement' => $announcementArray,
            'pageTitle' => __('announcement.announcements'),
        ]);

        $this->templateMgr->display('exercise/viewAnnouncement.tpl');
    }

    public function users($args, $request) {
        $this->templateMgr->assign([
            'pageTitle' => __('users.title'),
        ]);

        $this->templateMgr->display('exercise/users.tpl');
    }

    public function index($args, $request) {
        $this->templateMgr->assign([
            'pageTitle' => __('exercise.index.title'),
        ]);

        $this->templateMgr->display('exercise/index.tpl');
    }
}
