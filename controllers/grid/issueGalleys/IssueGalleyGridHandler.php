<?php

/**
 * @file controllers/grid/issueGalleys/IssueGalleyGridHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueGalleyGridHandler
 * @ingroup issue_galley
 *
 * @brief Handle issues grid requests.
 */

use APP\file\IssueFileManager;
use APP\security\authorization\OjsIssueGalleyRequiredPolicy;
use APP\security\authorization\OjsIssueRequiredPolicy;
use PKP\controllers\grid\feature\OrderGridItemsFeature;
use PKP\controllers\grid\GridColumn;
use PKP\controllers\grid\GridHandler;
use PKP\core\JSONMessage;
use PKP\file\TemporaryFileManager;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;

use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\Role;

import('controllers.grid.issueGalleys.IssueGalleyGridRow');

class IssueGalleyGridHandler extends GridHandler
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
                'fetchGrid', 'fetchRow', 'saveSequence',
                'add', 'edit', 'upload', 'download', 'update', 'delete'
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

        $this->addPolicy(new OjsIssueRequiredPolicy($request, $args));

        // If a signoff ID was specified, authorize it.
        if ($request->getUserVar('issueGalleyId')) {
            $this->addPolicy(new OjsIssueGalleyRequiredPolicy($request, $args));
        }

        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * @copydoc GridHandler::getDataElementSequence()
     */
    public function getDataElementSequence($issueGalley)
    {
        return $issueGalley->getSequence();
    }

    /**
     * @copydoc GridHandler::setDataElementSequence()
     */
    public function setDataElementSequence($request, $rowId, $gridDataElement, $newSequence)
    {
        $issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO'); /** @var IssueGalleyDAO $issueGalleyDao */
        $gridDataElement->setSequence($newSequence);
        $issueGalleyDao->updateObject($gridDataElement);
    }

    /**
     * @copydoc GridHandler::addFeatures()
     */
    public function initFeatures($request, $args)
    {
        return [new OrderGridItemsFeature()];
    }

    /**
     * @copydoc GridDataProvider::getRequestArgs()
     */
    public function getRequestArgs()
    {
        $issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
        $issueGalley = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE_GALLEY);
        $requestArgs = (array) parent::getRequestArgs();
        $requestArgs['issueId'] = $issue->getId();
        if ($issueGalley) {
            $requestArgs['issueGalleyId'] = $issueGalley->getId();
        }
        return $requestArgs;
    }

    /**
     * @copydoc GridHandler::initialize()
     *
     * @param null|mixed $args
     */
    public function initialize($request, $args = null)
    {
        parent::initialize($request, $args);

        // Add action
        $router = $request->getRouter();
        $this->addAction(
            new LinkAction(
                'add',
                new AjaxModal(
                    $router->url(
                        $request,
                        null,
                        null,
                        'add',
                        null,
                        array_merge($this->getRequestArgs(), ['gridId' => $this->getId()])
                    ),
                    __('grid.action.addIssueGalley'),
                    'modal_add'
                ),
                __('grid.action.addIssueGalley'),
                'add_category'
            )
        );

        // Grid columns.
        import('controllers.grid.issueGalleys.IssueGalleyGridCellProvider');
        $issueGalleyGridCellProvider = new IssueGalleyGridCellProvider();

        // Issue identification
        $this->addColumn(
            new GridColumn(
                'label',
                'submission.layout.galleyLabel',
                null,
                null,
                $issueGalleyGridCellProvider
            )
        );

        // Language, if more than one is supported
        $journal = $request->getJournal();
        if (count($journal->getSupportedLocaleNames()) > 1) {
            $this->addColumn(
                new GridColumn(
                    'locale',
                    'common.language',
                    null,
                    null,
                    $issueGalleyGridCellProvider
                )
            );
        }

        // Public ID, if enabled
        $this->addColumn(
            new GridColumn(
                'publicGalleyId',
                'submission.publisherId',
                null,
                null,
                $issueGalleyGridCellProvider
            )
        );
    }

    /**
     * Get the row handler - override the default row handler
     *
     * @return IssueGalleyGridRow
     */
    protected function getRowInstance()
    {
        $issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
        return new IssueGalleyGridRow($issue->getId());
    }

    //
    // Public operations
    //
    /**
     * An action to add a new issue
     *
     * @param array $args
     * @param PKPRequest $request
     */
    public function add($args, $request)
    {
        // Calling editIssueData with an empty ID will add
        // a new issue.
        return $this->edit($args, $request);
    }

    /**
     * An action to edit a issue galley
     *
     * @param array $args
     * @param PKPRequest $request
     *
     * @return JSONMessage JSON object
     */
    public function edit($args, $request)
    {
        $issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
        $issueGalley = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE_GALLEY);

        import('controllers.grid.issues.form.IssueGalleyForm');
        $issueGalleyForm = new IssueGalleyForm($request, $issue, $issueGalley);
        $issueGalleyForm->initData();
        return new JSONMessage(true, $issueGalleyForm->fetch($request));
    }

    /**
     * An action to upload an issue galley file.
     *
     * @param array $args
     * @param PKPRequest $request
     *
     * @return JSONMessage JSON object
     */
    public function upload($args, $request)
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
     * An action to download an issue galley
     *
     * @param array $args
     * @param PKPRequest $request
     *
     * @return string Serialized JSON object
     */
    public function download($args, $request)
    {
        $issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
        $issueGalley = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE_GALLEY);
        $issueFileManager = new IssueFileManager($issue->getId());
        return $issueFileManager->downloadById($issueGalley->getFileId());
    }

    /**
     * Update a issue
     *
     * @param array $args
     * @param PKPRequest $request
     *
     * @return JSONMessage JSON object
     */
    public function update($args, $request)
    {
        $issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
        $issueGalley = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE_GALLEY);

        import('controllers.grid.issues.form.IssueGalleyForm');
        $issueGalleyForm = new IssueGalleyForm($request, $issue, $issueGalley);
        $issueGalleyForm->readInputData();

        if ($issueGalleyForm->validate()) {
            $issueId = $issueGalleyForm->execute();
            return DAO::getDataChangedEvent($issueId);
        }
        return new JSONMessage(false);
    }

    /**
     * Removes an issue galley
     *
     * @param array $args
     * @param PKPRequest $request
     */
    public function delete($args, $request)
    {
        $issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO'); /** @var IssueGalleyDAO $issueGalleyDao */
        $issueGalley = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE_GALLEY);
        if ($issueGalley && $request->checkCSRF()) {
            $issueGalleyDao->deleteObject($issueGalley);
            return DAO::getDataChangedEvent();
        }
        return new JSONMessage(false);
    }

    /**
     * @copydoc GridHandler::loadData
     */
    protected function loadData($request, $filter)
    {
        $issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
        $issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO'); /** @var IssueGalleyDAO $issueGalleyDao */
        return $issueGalleyDao->getByIssueId($issue->getId());
    }
}
