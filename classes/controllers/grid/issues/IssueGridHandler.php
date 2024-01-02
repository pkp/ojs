<?php
/**
 * @defgroup controllers_grid_issues Issues Grid
 * The Issues Grid implements the management interface allowing editors to
 * manage future and archived issues.
 */

/**
 * @file controllers/grid/issues/IssueGridHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueGridHandler
 *
 * @ingroup controllers_grid_issues
 *
 * @brief Handle issues grid requests.
 */

namespace APP\controllers\grid\issues;

use APP\controllers\grid\issues\form\IssueAccessForm;
use APP\controllers\grid\issues\form\IssueForm;
use APP\controllers\grid\pubIds\form\AssignPublicIdentifiersForm;
use APP\controllers\tab\pubIds\form\PublicIdentifiersForm;
use APP\core\Application;
use APP\core\Request;
use APP\facades\Repo;
use APP\file\PublicFileManager;
use APP\issue\Collector;
use APP\jobs\notifications\IssuePublishedNotifyUsers;
use APP\notification\Notification;
use APP\notification\NotificationManager;
use APP\publication\Publication;
use APP\security\authorization\OjsIssueRequiredPolicy;
use APP\submission\Submission;
use APP\template\TemplateManager;
use Illuminate\Support\Facades\Bus;
use PKP\controllers\grid\GridColumn;
use PKP\controllers\grid\GridHandler;
use PKP\core\Core;
use PKP\core\JSONMessage;
use PKP\core\PKPApplication;
use PKP\db\DAO;
use PKP\db\DAORegistry;
use PKP\facades\Locale;
use PKP\file\TemporaryFileManager;
use PKP\mail\Mailer;
use PKP\notification\NotificationSubscriptionSettingsDAO;
use PKP\notification\PKPNotification;
use PKP\plugins\Hook;
use PKP\plugins\PluginRegistry;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\Role;

class IssueGridHandler extends GridHandler
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN],
            [
                'fetchGrid', 'fetchRow',
                'addIssue', 'editIssue', 'editIssueData', 'updateIssue',
                'uploadFile', 'deleteCoverImage',
                'issueToc',
                'issueGalleys',
                'deleteIssue', 'publishIssue', 'unpublishIssue', 'setCurrentIssue',
                'identifiers', 'updateIdentifiers', 'clearPubId', 'clearIssueObjectsPubIds',
                'access', 'updateAccess',
            ]
        );
    }


    //
    // Implement template methods from PKPHandler
    //
    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));

        // If a signoff ID was specified, authorize it.
        if ($request->getUserVar('issueId')) {
            $this->addPolicy(new OjsIssueRequiredPolicy($request, $args));
        }

        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * @copydoc GridHandler::initialize()
     *
     * @param null|mixed $args
     */
    public function initialize($request, $args = null)
    {
        parent::initialize($request, $args);

        // Grid columns.
        $issueGridCellProvider = new IssueGridCellProvider();

        // Issue identification
        $this->addColumn(
            new GridColumn(
                'identification',
                'issue.issue',
                null,
                null,
                $issueGridCellProvider
            )
        );

        $this->_addCenterColumns($issueGridCellProvider);

        // Number of articles
        $this->addColumn(
            new GridColumn(
                'numArticles',
                'editor.issues.numArticles',
                null,
                null,
                $issueGridCellProvider
            )
        );
    }

    /**
     * Private function to add central columns to the grid.
     * May be overridden by subclasses.
     *
     * @param IssueGridCellProvider $issueGridCellProvider
     */
    protected function _addCenterColumns($issueGridCellProvider)
    {
        // Default implementation does nothing.
    }

    /**
     * Get the row handler - override the default row handler
     *
     * @return IssueGridRow
     */
    protected function getRowInstance()
    {
        return new IssueGridRow();
    }

    //
    // Public operations
    //
    /**
     * An action to add a new issue
     *
     * @param array $args
     * @param Request $request
     */
    public function addIssue($args, $request)
    {
        // Calling editIssueData with an empty ID will add
        // a new issue.
        return $this->editIssueData($args, $request);
    }

    /**
     * An action to edit an issue
     *
     * @param array $args
     * @param Request $request
     *
     * @return JSONMessage JSON object
     */
    public function editIssue($args, $request)
    {
        $issue = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_ISSUE);
        $templateMgr = TemplateManager::getManager($request);
        if ($issue) {
            $templateMgr->assign('issueId', $issue->getId());
        }
        $publisherIdEnabled = in_array('issue', (array) $request->getContext()->getData('enablePublisherId'));
        $pubIdPlugins = PluginRegistry::getPlugins('pubIds');
        if ($publisherIdEnabled || count($pubIdPlugins)) {
            $templateMgr->assign('enableIdentifiers', true);
        }
        return new JSONMessage(true, $templateMgr->fetch('controllers/grid/issues/issue.tpl'));
    }

    /**
     * An action to edit an issue's identifying data
     *
     * @param array $args
     * @param Request $request
     *
     * @return JSONMessage JSON object
     */
    public function editIssueData($args, $request)
    {
        $issue = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_ISSUE);

        $issueForm = new IssueForm($issue);
        $issueForm->initData();
        return new JSONMessage(true, $issueForm->fetch($request));
    }

    /**
     * An action to upload an issue file. Used for issue cover images.
     *
     * @param array $args
     * @param Request $request
     *
     * @return JSONMessage JSON object
     */
    public function uploadFile($args, $request)
    {
        $user = $request->getUser();

        $temporaryFileManager = new TemporaryFileManager();
        $temporaryFile = $temporaryFileManager->handleUpload('uploadedFile', $user->getId());
        if ($temporaryFile) {
            $json = new JSONMessage(true);
            $json->setAdditionalAttributes([
                'temporaryFileId' => $temporaryFile->getId()
            ]);
            return $json;
        } else {
            return new JSONMessage(false, __('common.uploadFailed'));
        }
    }

    /**
     * Delete an uploaded cover image.
     *
     * @param array $args
     *   `coverImage` string Filename of the cover image to be deleted.
     *   `issueId` int Id of the issue this cover image is attached to
     * @param Request $request
     *
     * @return JSONMessage JSON object
     */
    public function deleteCoverImage($args, $request)
    {
        assert(!empty($args['coverImage']) && !empty($args['issueId']));

        // Check if the passed filename matches the filename for this issue's
        // cover page.
        $issue = Repo::issue()->get((int) $args['issueId']);
        $context = $request->getContext();
        if ($issue->getJournalId() != $context->getId()) {
            return new JSONMessage(false, __('editor.issues.removeCoverImageOnDifferentContextNowAllowed'));
        }

        $locale = Locale::getLocale();
        if ($args['coverImage'] != $issue->getCoverImage($locale)) {
            return new JSONMessage(false, __('editor.issues.removeCoverImageFileNameMismatch'));
        }

        $file = $args['coverImage'];

        // Remove cover image and alt text from issue settings
        $issue->setCoverImage('', $locale);
        $issue->setCoverImageAltText('', $locale);
        Repo::issue()->edit($issue, []);
        // Remove the file
        $publicFileManager = new PublicFileManager();
        if ($publicFileManager->removeContextFile($issue->getJournalId(), $file)) {
            $json = new JSONMessage(true);
            $json->setEvent('fileDeleted');
            return $json;
        } else {
            return new JSONMessage(false, __('editor.issues.removeCoverImageFileNotFound'));
        }
    }


    /**
     * Update an issue
     *
     * @param array $args
     * @param Request $request
     *
     * @return JSONMessage JSON object
     */
    public function updateIssue($args, $request)
    {
        $issue = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_ISSUE);

        $issueForm = new IssueForm($issue);
        $issueForm->readInputData();

        if ($issueForm->validate()) {
            $issueForm->execute();
            $notificationManager = new NotificationManager();
            $notificationManager->createTrivialNotification($request->getUser()->getId());
            return DAO::getDataChangedEvent();
        } else {
            return new JSONMessage(true, $issueForm->fetch($request));
        }
    }

    /**
     * An action to edit an issue's access settings
     *
     * @param array $args
     * @param Request $request
     *
     * @return JSONMessage JSON object
     */
    public function access($args, $request)
    {
        $issue = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_ISSUE);

        $issueAccessForm = new IssueAccessForm($issue);
        $issueAccessForm->initData();
        return new JSONMessage(true, $issueAccessForm->fetch($request));
    }

    /**
     * Update an issue's access settings
     *
     * @param array $args
     * @param Request $request
     *
     * @return JSONMessage JSON object
     */
    public function updateAccess($args, $request)
    {
        $issue = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_ISSUE);

        $issueAccessForm = new IssueAccessForm($issue);
        $issueAccessForm->readInputData();

        if ($issueAccessForm->validate()) {
            $issueAccessForm->execute();
            $notificationManager = new NotificationManager();
            $notificationManager->createTrivialNotification($request->getUser()->getId());
            return DAO::getDataChangedEvent();
        } else {
            return new JSONMessage(true, $issueAccessForm->fetch($request));
        }
    }

    /**
     * Removes an issue
     *
     * @param array $args
     * @param Request $request
     */
    public function deleteIssue($args, $request)
    {
        $issue = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_ISSUE);
        if (!$issue || !$request->checkCSRF()) {
            return new JSONMessage(false);
        }

        $journal = $request->getJournal();

        if ($issue->getJournalId() != $journal->getId()) {
            return new JSONMessage(false);
        }

        // remove all published submissions and return original articles to editing queue
        $submissions = Repo::submission()
            ->getCollector()
            ->filterByContextIds([$issue->getData('journalId')])
            ->filterByIssueIds([$issue->getId()])
            ->getMany();

        foreach ($submissions as $submission) {
            $publications = $submission->getData('publications');
            foreach ($publications as $publication) {
                if ($publication->getData('issueId') === (int) $issue->getId()) {
                    Repo::publication()->edit($publication, ['issueId' => '', 'status' => Submission::STATUS_QUEUED]);
                }
            }
            $newSubmission = Repo::submission()->get($submission->getId());
            Repo::submission()->updateStatus($newSubmission);
        }

        Repo::issue()->delete($issue);
        $currentIssue = Repo::issue()->getCurrent($issue->getJournalId());
        if ($currentIssue != null && $issue->getId() == $currentIssue->getId()) {
            $issues = Repo::issue()->getCollector()
                ->filterByContextIds([$journal->getId()])
                ->filterByPublished(true)
                ->orderBy(Collector::ORDERBY_PUBLISHED_ISSUES)
                ->getMany();
            if ($issue = $issues->first()) {
                Repo::issue()->updateCurrent($journal->getId(), $issue);
            }
        }

        return DAO::getDataChangedEvent($issue->getId());
    }

    /**
     * An action to edit issue pub ids
     *
     * @param array $args
     * @param Request $request
     *
     * @return JSONMessage JSON object
     */
    public function identifiers($args, $request)
    {
        $issue = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_ISSUE);
        $form = new PublicIdentifiersForm($issue);
        $form->initData();
        return new JSONMessage(true, $form->fetch($request));
    }

    /**
     * Update issue pub ids
     *
     * @param array $args
     * @param Request $request
     *
     * @return JSONMessage JSON object
     */
    public function updateIdentifiers($args, $request)
    {
        $issue = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_ISSUE);
        $form = new PublicIdentifiersForm($issue);
        $form->readInputData();
        if ($form->validate()) {
            $form->execute();
            return DAO::getDataChangedEvent($issue->getId());
        } else {
            return new JSONMessage(true, $form->fetch($request));
        }
    }

    /**
     * Clear issue pub id
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

        $issue = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_ISSUE);
        $form = new PublicIdentifiersForm($issue);
        $form->clearPubId($request->getUserVar('pubIdPlugIn'));
        $json = new JSONMessage(true);
        $json->setEvent('reloadTab', [['tabsSelector' => '#editIssueTabs', 'tabSelector' => '#identifiersTab']]);
        return $json;
    }

    /**
     * Clear issue objects pub ids
     *
     * @param array $args
     * @param Request $request
     *
     * @return JSONMessage JSON object
     */
    public function clearIssueObjectsPubIds($args, $request)
    {
        if (!$request->checkCSRF()) {
            return new JSONMessage(false);
        }

        $issue = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_ISSUE);
        $form = new PublicIdentifiersForm($issue);
        $form->clearIssueObjectsPubIds($request->getUserVar('pubIdPlugIn'));
        return new JSONMessage(true);
    }

    /**
     * Display the table of contents
     *
     * @param array $args
     * @param Request $request
     *
     * @return JSONMessage JSON object
     */
    public function issueToc($args, $request)
    {
        $templateMgr = TemplateManager::getManager($request);
        $issue = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_ISSUE);
        $templateMgr->assign('issue', $issue);
        return new JSONMessage(true, $templateMgr->fetch('controllers/grid/issues/issueToc.tpl'));
    }

    /**
     * Displays the issue galleys page.
     *
     * @param array $args
     * @param Request $request
     *
     * @return JSONMessage JSON object
     */
    public function issueGalleys($args, $request)
    {
        $issue = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_ISSUE);
        $templateMgr = TemplateManager::getManager($request);
        $dispatcher = $request->getDispatcher();
        return $templateMgr->fetchAjax(
            'issueGalleysGridContainer',
            $dispatcher->url(
                $request,
                PKPApplication::ROUTE_COMPONENT,
                null,
                'grid.issueGalleys.IssueGalleyGridHandler',
                'fetchGrid',
                null,
                ['issueId' => $issue->getId()]
            )
        );
    }

    /**
     * Publish issue
     *
     * @param array $args
     * @param Request $request
     */
    public function publishIssue($args, $request)
    {
        $issue = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_ISSUE);
        $context = $request->getContext();
        $contextId = $context->getId();
        $wasPublished = $issue->getPublished();

        if (!$wasPublished) {
            $confirmationText = __('editor.issues.confirmPublish');
            $formTemplate = $this->getAssignPublicIdentifiersFormTemplate();
            $assignPublicIdentifiersForm = new AssignPublicIdentifiersForm($formTemplate, $issue, true, $confirmationText);
            if (!$request->getUserVar('confirmed')) {
                // Display assign pub ids modal
                $assignPublicIdentifiersForm->initData();
                return new JSONMessage(true, $assignPublicIdentifiersForm->fetch($request));
            }
            // Assign pub ids
            $assignPublicIdentifiersForm->readInputData();
            if (!$assignPublicIdentifiersForm->validate()) {
                return new JSONMessage(true, $assignPublicIdentifiersForm->fetch($request));
            }
            $assignPublicIdentifiersForm->execute();
            Repo::issue()->createDoi($issue);
        }

        if (!$request->checkCSRF()) {
            return new JSONMessage(false);
        }

        $issue->setPublished(1);
        $issue->setDatePublished(Core::getCurrentDate());

        // If subscriptions with delayed open access are enabled then
        // update open access date according to open access delay policy
        if ($context->getData('publishingMode') == \APP\journal\Journal::PUBLISHING_MODE_SUBSCRIPTION && ($delayDuration = $context->getData('delayedOpenAccessDuration'))) {
            $delayYears = (int)floor($delayDuration / 12);
            $delayMonths = (int)fmod($delayDuration, 12);

            $curYear = date('Y');
            $curMonth = date('n');
            $curDay = date('j');

            $delayOpenAccessYear = $curYear + $delayYears + (int)floor(($curMonth + $delayMonths) / 12);
            $delayOpenAccessMonth = (int)fmod($curMonth + $delayMonths, 12);

            $issue->setAccessStatus(\APP\issue\Issue::ISSUE_ACCESS_SUBSCRIPTION);
            $issue->setOpenAccessDate(date('Y-m-d H:i:s', mktime(0, 0, 0, $delayOpenAccessMonth, $curDay, $delayOpenAccessYear)));
        }

        Hook::call('IssueGridHandler::publishIssue', [&$issue]);

        Repo::issue()->updateCurrent($contextId, $issue);

        if (!$wasPublished) {
            Repo::doi()->issueUpdated($issue);

            // Publish all related publications
            // Include published submissions in order to support cases where two
            // versions of the same submission are published in distinct issues. In
            // such cases, the submission will be STATUS_PUBLISHED but the
            // publication will be STATUS_SCHEDULED.
            $submissions = Repo::submission()->getCollector()
                ->filterByContextIds([$issue->getJournalId()])
                ->filterByIssueIds([$issue->getId()])
                ->filterByStatus([Submission::STATUS_SCHEDULED, Submission::STATUS_PUBLISHED])
                ->getMany();

            foreach ($submissions as $submission) { /** @var Submission $submission */
                $publications = $submission->getData('publications');

                foreach ($publications as $publication) { /** @var Publication $publication */
                    if ($publication->getData('status') === Submission::STATUS_SCHEDULED && $publication->getData('issueId') === (int) $issue->getId()) {
                        Repo::publication()->publish($publication);
                    }
                }
            }
        }

        // Send a notification to associated users if selected and context is publishing content online with OJS
        if ($request->getUserVar('sendIssueNotification') && $context->getData('publishingMode') != \APP\journal\Journal::PUBLISHING_MODE_NONE) {
            // Notify users
            /** @var NotificationSubscriptionSettingsDAO $notificationSubscriptionSettingsDao */
            $notificationSubscriptionSettingsDao = DAORegistry::getDAO('NotificationSubscriptionSettingsDAO');

            $userIdsToNotify = $notificationSubscriptionSettingsDao->getSubscribedUserIds(
                [NotificationSubscriptionSettingsDAO::BLOCKED_NOTIFICATION_KEY],
                [Notification::NOTIFICATION_TYPE_PUBLISHED_ISSUE],
                [$contextId]
            );

            $userIdsToMail = $notificationSubscriptionSettingsDao->getSubscribedUserIds(
                [
                    NotificationSubscriptionSettingsDAO::BLOCKED_NOTIFICATION_KEY,
                    NotificationSubscriptionSettingsDAO::BLOCKED_EMAIL_NOTIFICATION_KEY
                ],
                [Notification::NOTIFICATION_TYPE_PUBLISHED_ISSUE],
                [$contextId]
            );

            $userIdsToNotifyAndMail = $userIdsToNotify->intersect($userIdsToMail);
            $userIdsToNotify = $userIdsToNotify->diff($userIdsToMail);

            $jobs = [];
            foreach ($userIdsToNotify->chunk(PKPNotification::NOTIFICATION_CHUNK_SIZE_LIMIT) as $notifyUserIds) {
                $jobs[] = new IssuePublishedNotifyUsers(
                    $notifyUserIds,
                    $contextId,
                    $issue,
                    Locale::getLocale(),
                );
            }

            foreach ($userIdsToNotifyAndMail->chunk(Mailer::BULK_EMAIL_SIZE_LIMIT) as $mailUserIds) {
                $jobs[] = new IssuePublishedNotifyUsers(
                    $mailUserIds,
                    $contextId,
                    $issue,
                    Locale::getLocale(),
                    $request->getUser()
                );
            }
            Bus::batch($jobs)->dispatch();
        }

        $json = DAO::getDataChangedEvent();
        $json->setGlobalEvent('issuePublished', ['id' => $issue->getId()]);
        return $json;
    }

    /**
     * Unpublish a previously-published issue
     *
     * @param array $args
     * @param Request $request
     */
    public function unpublishIssue($args, $request)
    {
        $issue = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_ISSUE);
        $journal = $request->getJournal();

        if (!$request->checkCSRF()) {
            return new JSONMessage(false);
        }

        // NB: Data set via params because setData('datePublished', null)
        // removes the entry into _data rather than updating 'datePublished' to null.
        $updateParams = [
            'published' => 0,
            'datePublished' => null
        ];

        Hook::call('IssueGridHandler::unpublishIssue', [&$issue]);

        Repo::issue()->edit($issue, $updateParams);
        Repo::issue()->updateCurrent($request->getContext()->getId());

        Repo::doi()->issueUpdated($issue);

        // insert article tombstones for all articles
        $submissions = Repo::submission()->getCollector()
            ->filterByContextIds([$issue->getJournalId()])
            ->filterByIssueIds([$issue->getId()])
            ->getMany();

        foreach ($submissions as $submission) { /** @var Submission $submission */
            $publications = $submission->getData('publications');
            foreach ($publications as $publication) { /** @var Publication $publication */
                if ($publication->getData('status') === Submission::STATUS_PUBLISHED && $publication->getData('issueId') === (int) $issue->getId()) {
                    // Republish the publication in the issue, now that it's status has changed,
                    // to ensure the publication's status is restored to Submission::STATUS_SCHEDULED
                    // rather than Submission::STATUS_QUEUED
                    Repo::publication()->unpublish($publication);
                    Repo::publication()->publish($publication);
                }
            }
        }

        $json = DAO::getDataChangedEvent($issue->getId());
        $json->setGlobalEvent('issueUnpublished', ['id' => $issue->getId()]);
        return $json;
    }

    /**
     * Set Issue as current
     *
     * @param array $args
     * @param Request $request
     */
    public function setCurrentIssue($args, $request)
    {
        $issue = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_ISSUE);
        $journal = $request->getJournal();

        if (!$request->checkCSRF()) {
            return new JSONMessage(false);
        }

        Repo::issue()->updateCurrent($journal->getId(), $issue);

        $dispatcher = $request->getDispatcher();
        return DAO::getDataChangedEvent();
    }

    /**
     * Get the template for the assign public identifiers form.
     *
     * @return string
     */
    public function getAssignPublicIdentifiersFormTemplate()
    {
        return 'controllers/grid/pubIds/form/assignPublicIdentifiersForm.tpl';
    }
}
