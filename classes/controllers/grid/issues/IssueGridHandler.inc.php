<?php
/**
 * @defgroup controllers_grid_issues Issues Grid
 * The Issues Grid implements the management interface allowing editors to
 * manage future and archived issues.
 */

/**
 * @file controllers/grid/issues/IssueGridHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueGridHandler
 * @ingroup controllers_grid_issues
 *
 * @brief Handle issues grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('controllers.grid.issues.IssueGridRow');

class IssueGridHandler extends GridHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER),
			array(
				'fetchGrid', 'fetchRow',
				'addIssue', 'editIssue', 'editIssueData', 'updateIssue',
				'uploadFile', 'deleteCoverImage',
				'issueToc',
				'issueGalleys',
				'deleteIssue', 'publishIssue', 'unpublishIssue', 'setCurrentIssue',
				'identifiers', 'updateIdentifiers', 'clearPubId', 'clearIssueObjectsPubIds',
				'access', 'updateAccess',
			)
		);
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
		$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));

		// If a signoff ID was specified, authorize it.
		if ($request->getUserVar('issueId')) {
			import('classes.security.authorization.OjsIssueRequiredPolicy');
			$this->addPolicy(new OjsIssueRequiredPolicy($request, $args));
		}

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @copydoc GridHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR);
		// Load submission-specific translations
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION);

		// Grid columns.
		import('controllers.grid.issues.IssueGridCellProvider');
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
	 * @param $issueGridCellProvider IssueGridCellProvider
	 */
	protected function _addCenterColumns($issueGridCellProvider) {
		// Default implementation does nothing.
	}

	/**
	 * Get the row handler - override the default row handler
	 * @return IssueGridRow
	 */
	protected function getRowInstance() {
		return new IssueGridRow();
	}

	//
	// Public operations
	//
	/**
	 * An action to add a new issue
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function addIssue($args, $request) {
		// Calling editIssueData with an empty ID will add
		// a new issue.
		return $this->editIssueData($args, $request);
	}

	/**
	 * An action to edit an issue
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editIssue($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$templateMgr = TemplateManager::getManager($request);
		if ($issue) $templateMgr->assign('issueId', $issue->getId());
		return new JSONMessage(true, $templateMgr->fetch('controllers/grid/issues/issue.tpl'));
	}

	/**
	 * An action to edit an issue's identifying data
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editIssueData($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);

		import('controllers.grid.issues.form.IssueForm');
		$issueForm = new IssueForm($issue);
		$issueForm->initData();
		return new JSONMessage(true, $issueForm->fetch($request));
	}

	/**
	 * An action to upload an issue file. Used for issue cover images.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function uploadFile($args, $request) {
		$user = $request->getUser();

		import('lib.pkp.classes.file.TemporaryFileManager');
		$temporaryFileManager = new TemporaryFileManager();
		$temporaryFile = $temporaryFileManager->handleUpload('uploadedFile', $user->getId());
		if ($temporaryFile) {
			$json = new JSONMessage(true);
			$json->setAdditionalAttributes(array(
				'temporaryFileId' => $temporaryFile->getId()
			));
			return $json;
		} else {
			return new JSONMessage(false, __('common.uploadFailed'));
		}
	}

	/**
	 * Delete an uploaded cover image.
	 * @param $args array
	 *   `coverImage` string Filename of the cover image to be deleted.
	 *   `issueId` int Id of the issue this cover image is attached to
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function deleteCoverImage($args, $request) {
		assert(!empty($args['coverImage']) && !empty($args['issueId']));

		// Check if the passed filename matches the filename for this issue's
		// cover page.
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->getById((int) $args['issueId']);
		$locale = AppLocale::getLocale();
		if ($args['coverImage'] != $issue->getCoverImage($locale)) {
			return new JSONMessage(false, __('editor.issues.removeCoverImageFileNameMismatch'));
		}

		$file = $args['coverImage'];

		// Remove cover image and alt text from issue settings
		$issue->setCoverImage('', $locale);
		$issue->setCoverImageAltText('', $locale);
		$issueDao->updateObject($issue);

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
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateIssue($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);

		import('controllers.grid.issues.form.IssueForm');
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
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function access($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);

		import('controllers.grid.issues.form.IssueAccessForm');
		$issueAccessForm = new IssueAccessForm($issue);
		$issueAccessForm->initData();
		return new JSONMessage(true, $issueAccessForm->fetch($request));
	}

	/**
	 * Update an issue's access settings
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateAccess($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);

		import('controllers.grid.issues.form.IssueAccessForm');
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
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function deleteIssue($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		if (!$issue || !$request->checkCSRF()) return new JSONMessage(false);

		$journal = $request->getJournal();
		$isBackIssue = $issue->getPublished() > 0 ? true: false;

		// remove all published submissions and return original articles to editing queue
		$submissions = Services::get('submission')->getMany([
			'contextId' => $issue->getJournalId(),
			'count' => 5000, // large upper-limit
			'issueIds' => $issue->getId(),
		]);
		foreach ($submissions as $submission) {
			$submission = Services::get('submission')->edit($submission, ['status' => STATUS_QUEUED], $request);
			if ($isBackIssue) {
				$articleTombstoneManager->insertArticleTombstone($article, $journal);
			}
			$publications = (array) $submission->getData('publications');
			foreach ($publications as $publication) {
				$publication = Services::get('publication')->edit($publication, ['issueId' => '']);
			}
		}

		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issueDao->deleteObject($issue);
		if ($issue->getCurrent()) {
			$issues = $issueDao->getPublishedIssues($journal->getId());
			if (!$issues->eof()) {
				$issue = $issues->next();
				$issue->setCurrent(1);
				$issueDao->updateObject($issue);
			}
		}

		return DAO::getDataChangedEvent($issue->getId());
	}

	/**
	 * An action to edit issue pub ids
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function identifiers($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		import('controllers.tab.pubIds.form.PublicIdentifiersForm');
		$form = new PublicIdentifiersForm($issue);
		$form->initData();
		return new JSONMessage(true, $form->fetch($request));
	}

	/**
	 * Update issue pub ids
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateIdentifiers($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		import('controllers.tab.pubIds.form.PublicIdentifiersForm');
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
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function clearPubId($args, $request) {
		if (!$request->checkCSRF()) return new JSONMessage(false);

		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		import('controllers.tab.pubIds.form.PublicIdentifiersForm');
		$form = new PublicIdentifiersForm($issue);
		$form->clearPubId($request->getUserVar('pubIdPlugIn'));
		return new JSONMessage(true);
	}

	/**
	 * Clear issue objects pub ids
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function clearIssueObjectsPubIds($args, $request) {
		if (!$request->checkCSRF()) return new JSONMessage(false);

		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		import('controllers.tab.pubIds.form.PublicIdentifiersForm');
		$form = new PublicIdentifiersForm($issue);
		$form->clearIssueObjectsPubIds($request->getUserVar('pubIdPlugIn'));
		return new JSONMessage(true);
	}

	/**
	 * Display the table of contents
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function issueToc($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$templateMgr->assign('issue', $issue);
		return new JSONMessage(true, $templateMgr->fetch('controllers/grid/issues/issueToc.tpl'));
	}

	/**
	 * Displays the issue galleys page.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function issueGalleys($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$templateMgr = TemplateManager::getManager($request);
		$dispatcher = $request->getDispatcher();
		return $templateMgr->fetchAjax(
			'issueGalleysGridContainer',
			$dispatcher->url(
				$request, ROUTE_COMPONENT, null,
				'grid.issueGalleys.IssueGalleyGridHandler', 'fetchGrid', null,
				array('issueId' => $issue->getId())
			)
		);
	}

	/**
	 * Publish issue
	 * @param $args array
	 * @param $request Request
	 */
	function publishIssue($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$context = $request->getContext();
		$contextId = $context->getId();
		$wasPublished = $issue->getPublished();

		$articleSearchIndex = null;
		if (!$wasPublished) {
			$confirmationText = __('editor.issues.confirmPublish');
			import('controllers.grid.pubIds.form.AssignPublicIdentifiersForm');
			$formTemplate = $this->getAssignPublicIdentifiersFormTemplate();
			$assignPublicIdentifiersForm = new AssignPublicIdentifiersForm($formTemplate, $issue, true, $confirmationText);
			if (!$request->getUserVar('confirmed')) {
				// Display assign pub ids modal
				$assignPublicIdentifiersForm->initData();
				return new JSONMessage(true, $assignPublicIdentifiersForm->fetch($request));
			}
			// Asign pub ids
			$assignPublicIdentifiersForm->readInputData();
			$assignPublicIdentifiersForm->execute();
		}

		$issue->setCurrent(1);
		$issue->setPublished(1);
		$issue->setDatePublished(Core::getCurrentDate());

		// If subscriptions with delayed open access are enabled then
		// update open access date according to open access delay policy
		if ($context->getData('publishingMode') == PUBLISHING_MODE_SUBSCRIPTION && ($delayDuration = $context->getData('delayedOpenAccessDuration'))) {
			$delayYears = (int)floor($delayDuration/12);
			$delayMonths = (int)fmod($delayDuration,12);

			$curYear = date('Y');
			$curMonth = date('n');
			$curDay = date('j');

			$delayOpenAccessYear = $curYear + $delayYears + (int)floor(($curMonth+$delayMonths)/12);
 			$delayOpenAccessMonth = (int)fmod($curMonth+$delayMonths,12);

			$issue->setAccessStatus(ISSUE_ACCESS_SUBSCRIPTION);
			$issue->setOpenAccessDate(date('Y-m-d H:i:s',mktime(0,0,0,$delayOpenAccessMonth,$curDay,$delayOpenAccessYear)));
		}

		HookRegistry::call('IssueGridHandler::publishIssue', array(&$issue));

		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issueDao->updateCurrent($contextId,$issue);

		if (!$wasPublished) {
			// Publish all related publications
			$submissions = Services::get('submission')->getMany([
				'contextId' => $issue->getJournalId(),
				'issueIds' => $issue->getId(),
				'status' => STATUS_SCHEDULED,
				'count' => 5000, // large upper limit
			]);
			foreach ($submissions as $submission) {
				$publication = $submission->getLatestPublication();
				if ($publication->getData('status') === STATUS_SCHEDULED && $publication->getData('issueId') === (int) $issue->getId()) {
					$publication = Services::get('publication')->publish($publication);
				}
				if (!$articleSearchIndex) {
					$articleSearchIndex = Application::getSubmissionSearchIndex();
				}
				$articleSearchIndex->submissionMetadataChanged($submission);
				// delete article tombstone
				DAORegistry::getDAO('DataObjectTombstoneDAO')->deleteByDataObjectId($submission->getId());
			}
		}

		if ($articleSearchIndex) $articleSearchIndex->submissionChangesFinished();

		// Send a notification to associated users if selected and context is publishing content online with OJS
		if ($request->getUserVar('sendIssueNotification') && $context->getData('publishingMode') != PUBLISHING_MODE_NONE) {
			import('classes.notification.NotificationManager');
			$notificationManager = new NotificationManager();
			$notificationUsers = array();
			$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
			$allUsers = $userGroupDao->getUsersByContextId($contextId);
			while ($user = $allUsers->next()) {
				$notificationUsers[] = array('id' => $user->getId());
			}
			foreach ($notificationUsers as $userRole) {
				$notificationManager->createNotification(
					$request, $userRole['id'], NOTIFICATION_TYPE_PUBLISHED_ISSUE,
					$contextId
				);
			}
		}

		$json = DAO::getDataChangedEvent();
		$json->setGlobalEvent('issuePublished', array('id' => $issue->getId()));
		return $json;
	}

	/**
	 * Unpublish a previously-published issue
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function unpublishIssue($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$journal = $request->getJournal();

		if (!$request->checkCSRF()) return new JSONMessage(false);

		$issue->setCurrent(0);
		$issue->setPublished(0);
		$issue->setDatePublished(null);

		HookRegistry::call('IssueGridHandler::unpublishIssue', array(&$issue));

		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issueDao->updateObject($issue);

		// insert article tombstones for all articles
		import('classes.article.ArticleTombstoneManager');
		$articleTombstoneManager = new ArticleTombstoneManager();
		$submissions = Services::get('submission')->getMany([
			'contextId' => $issue->getJournalId(),
			'issueIds' => $issue->getId(),
			'count' => 5000, // large upper limit
		]);
		foreach ($submissions as $submission) {
			$articleTombstoneManager->insertArticleTombstone($submission, $journal);
			$submission = Services::get('submission')->edit($submission, ['status' => STATUS_QUEUED], $request);
		}

		$dispatcher = $request->getDispatcher();
		$json = new JSONMessage();
		$json->setEvent('containerReloadRequested', array('tabsUrl' => $dispatcher->url($request, ROUTE_PAGE, null, 'manageIssues', 'issuesTabs', null)));
		$json->setGlobalEvent('issueUnpublished', array('id' => $issue->getId()));
		return $json;
	}

	/**
	 * Set Issue as current
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function setCurrentIssue($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$journal = $request->getJournal();

		if (!$request->checkCSRF()) return new JSONMessage(false);

		$issue->setCurrent(1);

		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issueDao->updateCurrent($journal->getId(), $issue);

		$dispatcher = $request->getDispatcher();
		$json = new JSONMessage();
		$json->setEvent('containerReloadRequested', array('tabsUrl' => $dispatcher->url($request, ROUTE_PAGE, null, 'manageIssues', 'issuesTabs', null)));
		return $json;
	}

	/**
	 * Get the template for the assign public identifiers form.
	 * @return string
	 */
	function getAssignPublicIdentifiersFormTemplate() {
		return 'controllers/grid/pubIds/form/assignPublicIdentifiersForm.tpl';
	}
}


