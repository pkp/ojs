<?php declare(strict_types = 1);

/**
 * @file pages/exercise/AdminExerciseHandler.inc.inc.php
 *
 * Copyright (c) 2020 Simon Fraser University
 * Copyright (c) 2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AdminExerciseHandler
 * @ingroup pages_index
 *
 * @brief Handle site index requests.
 */

import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
import('classes.handler.Handler');

class AdminExerciseHandler extends Handler
{
    protected $templateMgr;

    protected $currentRequest;

    public $_isBackendPage = true;

    public function __construct()
    {
        $roles = [ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER];

        $this->addRoleAssignment($roles, ['announcementsAdmin']);

        parent::__construct($roles);
    }

    public function authorize(
        $request,
        &$args,
        $roleAssignments
    ) {
        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));

        return parent::authorize($request, $args, $roleAssignments);
    }

    public function initialize($request)
    {
        AppLocale::requireComponents(
            LOCALE_COMPONENT_PKP_ADMIN,
            LOCALE_COMPONENT_APP_MANAGER,
            LOCALE_COMPONENT_APP_ADMIN,
            LOCALE_COMPONENT_APP_COMMON,
            LOCALE_COMPONENT_PKP_USER,
            LOCALE_COMPONENT_PKP_MANAGER
        );

        $this->templateMgr = TemplateManager::getManager($request);

        $this->templateMgr->assign([
            'announcementsLink' => $request->getRouter()->url($request, null, 'exercise', 'announcementsAdmin'),
            'pageTitle' => __('exercise.index.title'),
        ]);

        $this->currentRequest = $request;

        $this->setupTemplate($request);

        return parent::initialize($request);
    }

    public function announcementsAdmin($args, $request)
    {
        if (isset($args[0]) && is_numeric($args[0])) {
            return $this->viewAnnouncement((int) $args[0]);
        }

        $announcementsIterator = Services::get('announcement')->getMany([
            'isEnabled' => true,
            'contextIds' => [$request->getContext()->getId()],
        ]);

        $announcements = [];
        foreach ($announcementsIterator as $announcement) {
            $announcementId = $announcement->getData('id');
            $uri = $request->getRouter()->url(
                $request,
                null,
                'exercise',
                'announcementsAdmin',
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

        $this->templateMgr->display('exercise/admin/announcements.tpl');
    }

    public function viewAnnouncement(int $announcementId)
    {
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

        $this->templateMgr->display('exercise/admin/viewAnnouncement.tpl');
    }
}
