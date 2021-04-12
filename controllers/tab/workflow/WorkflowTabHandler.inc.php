<?php

/**
 * @file controllers/tab/workflow/WorkflowTabHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class WorkflowTabHandler
 * @ingroup controllers_tab_workflow
 *
 * @brief Handle AJAX operations for workflow tabs.
 */

// Import the base Handler.
import('lib.pkp.controllers.tab.workflow.PKPWorkflowTabHandler');
import('lib.pkp.classes.submission.SubmissionFile');

class WorkflowTabHandler extends PKPWorkflowTabHandler {


	var $_uploaderRoles;

	/**
	 * Constructor
	 */
	function __construct() {

		parent::__construct();

		AppLocale::requireComponents(
			LOCALE_COMPONENT_APP_COMMON,
			LOCALE_COMPONENT_APP_EDITOR,
			LOCALE_COMPONENT_APP_MANAGER,
			LOCALE_COMPONENT_PKP_SUBMISSION,
			LOCALE_COMPONENT_APP_SUBMISSION

		);

		$this->addRoleAssignment(array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER, ROLE_ID_ASSISTANT),
			array('createFile', 'deleteFile', 'displayFileCreateForm', 'displayFileUploadForm', 'finishFileSubmission', 'uploadFile'));
	}

	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @copydoc PKPHandler::initialize()
	 */
	function initialize($request) {
		parent::initialize($request);
		// Set the uploader roles (if given).
		$uploaderRoles = $request->getUserVar('uploaderRoles');
		if (!empty($uploaderRoles)) {
			$this->_uploaderRoles = array();
			$uploaderRoles = explode('-', $uploaderRoles);
			foreach ($uploaderRoles as $uploaderRole) {
				if (!is_numeric($uploaderRole)) fatalError('Invalid uploader role!');
				$this->_uploaderRoles[] = (int)$uploaderRole;
			}
		}
	}


	// Getters and Setters
	//
	/*
	* Get the workflow stage file storage that
	* we upload files to. One of the SUBMISSION_FILE_*
	* constants.
	* @return integer
	*/
	function getFileStage() {
		return SUBMISSION_FILE_PRODUCTION_READY;
	}

	/**
	 * The submission to which we upload files.
	 * @return Submission
	 */

	function getSubmission() {
		return $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
	}

	/**
	 * Get the authorized workflow stage.
	 * @return integer One of the WORKFLOW_STAGE_ID_* constants.
	 */
	function getStageId() {
		return $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
	}

	/**
	 * Get the uploader roles.
	 * @return array
	 */
	function getUploaderRoles() {
		return $this->_uploaderRoles;
	}

	/**
	 * @copydoc PKPWorkflowTabHandler::fetchTab
	 */
	function fetchTab($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$stageId = $this->getStageId();
		$templateMgr->assign(array(
			'submissionId' => $this->getSubmission()->getId(),
			'stageId' => $stageId,
			'uploaderRoles' => implode('-', (array)$this->getUploaderRoles()),
			'fileStage' => $this->getStageId(),
			'isReviewer' => '',
			'revisionOnly' => '',
			'reviewRoundId' => '',
			'revisedFileId' => '',
			'assocType' => '',
			'assocId' => '',
			'dependentFilesOnly' => '',
		));

		switch ($stageId) {
			case WORKFLOW_STAGE_ID_PRODUCTION:
				$dispatcher = $request->getDispatcher();
				import('lib.pkp.classes.linkAction.request.AjaxModal');
				$schedulePublicationLinkAction = new LinkAction(
					'schedulePublication',
					new AjaxModal(
						$dispatcher->url(
							$request, ROUTE_COMPONENT, null,
							'tab.issueEntry.IssueEntryTabHandler',
							'publicationMetadata', null,
							array('submissionId' => $submission->getId(), 'stageId' => $stageId)
						),
						__('submission.issueEntry.publicationMetadata')
					),
					__('editor.article.schedulePublication')
				);
				$templateMgr->assign('schedulePublicationLinkAction', $schedulePublicationLinkAction);

				$schedulePublicationLinkAction = $this->displaySchedulePublicationLinkAction($request);
				$templateMgr->assign('schedulePublicationLinkAction', $schedulePublicationLinkAction);

				$xmlFileCreateLinkAction = $this->xmlFileCreateLinkAction($request);
				$templateMgr->assign('xmlFileCreateLinkAction', $xmlFileCreateLinkAction);

				$xmlFileUploadLinkAction = $this->xmlFileUploadLinkAction($request);
				$templateMgr->assign('xmlFileUploadLinkAction', $xmlFileUploadLinkAction);


				// Get if jatsTemplatePlugin is enabled

				$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO');
				$context = $request->getContext();
				if ($pluginSettingsDao->settingExists($context->getId(), 'jatstemplateplugin', 'enabled')) {
					$isJatsTemplatePluginEnabled = $pluginSettingsDao->getSetting($context->getId(), 'jatstemplateplugin', 'enabled');
					$templateMgr->assign('isJatsTemplatePluginEnabled', $isJatsTemplatePluginEnabled);
				}
				break;

		}
		return parent::fetchTab($args, $request);
	}

	/**
	 * Get all production notification options to be used in the production stage tab.
	 * @param $submissionId int
	 * @return array
	 */
	protected function getProductionNotificationOptions($submissionId) {
		return array(
			NOTIFICATION_LEVEL_NORMAL => array(
				NOTIFICATION_TYPE_VISIT_CATALOG => array(ASSOC_TYPE_SUBMISSION, $submissionId),
				NOTIFICATION_TYPE_ASSIGN_PRODUCTIONUSER => array(ASSOC_TYPE_SUBMISSION, $submissionId),
				NOTIFICATION_TYPE_AWAITING_REPRESENTATIONS => array(ASSOC_TYPE_SUBMISSION, $submissionId),
				NOTIFICATION_TYPE_PUBLICATION_SCHEDULED => array(ASSOC_TYPE_SUBMISSION, $submissionId)
			),
			NOTIFICATION_LEVEL_TRIVIAL => array()
		);
	}

	/**
	 * @param $args array
	 * @param $request
	 * @return JSONMessage JSON object
	 */
	function displayFileUploadForm($args, $request) {
		import('lib.pkp.controllers.tab.workflow.form.FileUploadFormXML');
		$submission = $this->getSubmission();
		$fileForm = new FileUploadFormXML($request, $submission->getId(), $this->getStageId(), $this->getUploaderRoles(), $this->getFileStage());
		$fileForm->initData();

		return new JSONMessage(true, $fileForm->fetch($request));
	}


	/**
	 * @param $args array
	 * @param $request
	 * @return JSONMessage JSON object
	 */
	function displayFileCreateForm($args, $request) {
		import('lib.pkp.controllers.tab.workflow.form.FileCreateFormXML');
		$fileForm = new FileCreateFormXML($request, $this->getSubmission()->getId(), $this->getStageId(), $this->getFileStage());
		$fileForm->initData();

		return new JSONMessage(true, $fileForm->fetch($request));
	}

	/**
	 * @param $request
	 * @return LinkAction
	 */
	public function xmlFileUploadLinkAction($request) {
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$dispatcher = $request->getDispatcher();

		$actionArgs = array('submissionId' => $this->getSubmission()->getId(), 'stageId' => $this->getStageId(), 'fileStage' => $this->getFileStage());
		$xmlFileUploadLinkAction = new LinkAction(
			'xmlFileUpload',
			new AjaxModal(
				$dispatcher->url(
					$request, ROUTE_COMPONENT, null, null, 'displayFileUploadForm', null,
					$actionArgs
				),
				__('submission.upload.productionReadyXML.description')
			),
			__('submission.upload.productionReadyXML')
		);
		return $xmlFileUploadLinkAction;
	}


	/**
	 * @param $request
	 * @return LinkAction
	 */
	public function xmlFileCreateLinkAction($request) {

		$dispatcher = $request->getDispatcher();
		$actionArgs = array('submissionId' => $this->getSubmission()->getId(), 'stageId' => $this->getStageId(), 'fileStage' => $this->getFileStage());

		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$xmlFileCreateLinkAction = new LinkAction(
			'xmlFileUpload',
			new AjaxModal(
				$dispatcher->url(
					$request, ROUTE_COMPONENT, null,
					'tab.workflow.WorkflowTabHandler',
					'displayFileCreateForm', null,
					$actionArgs
				),
				__('submission.create.productionReadyXML.description')
			),
			__('submission.create.productionReadyXML')
		);
		return $xmlFileCreateLinkAction;
	}

	/**
	 * Creates XML file from jats template
	 *
	 * @param $args
	 * @param $request
	 * @return JSONMessage
	 */

	public function createFile($args, $request) {

		if ($request->checkCSRF()) {
			$doc = $this->_getJatsTemplate($request);
			$user = $request->getUser();

			$originalFileName = (strpos($args['fileName'], 'xml', -3)) ? $args['fileName'] : $args['fileName'] . '.xml';

			$tempFileName = tempnam(sys_get_temp_dir(), 'jatsTemplateXML');
			file_put_contents($tempFileName, $doc->saveXML());


			$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');

			import('lib.pkp.classes.submission.Genre');
			$submissionFile = $submissionFileDao->newDataObjectByGenreId(GENRE_CATEGORY_DOCUMENT);
			$submissionFile->setSubmissionId($this->getSubmission()->getId());
			$submissionFile->setSubmissionLocale($this->getSubmission()->getLocale());
			$submissionFile->setGenreId(GENRE_CATEGORY_DOCUMENT);
			$submissionFile->setFileStage(SUBMISSION_FILE_PRODUCTION_READY);
			$submissionFile->setDateUploaded(Core::getCurrentDate());
			$submissionFile->setDateModified(Core::getCurrentDate());
			$submissionFile->setUploaderUserId($user->getId());
			$submissionFile->setFileSize(filesize($tempFileName));
			$submissionFile->setFileType('text/xml');
			$submissionFile->setOriginalFileName($originalFileName);
			$submissionFile->setName($originalFileName, null);
			$submissionFileDao->insertObject($submissionFile, $tempFileName, false);

			return DAO::getDataChangedEvent();
		} else {
			return new JSONMessage(false);
		}

	}

	/***
	 * @param $request
	 * @return LinkAction
	 */
	protected function displaySchedulePublicationLinkAction($request) {
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$dispatcher = $request->getDispatcher();
		$schedulePublicationLinkAction = new LinkAction(
			'schedulePublication',
			new AjaxModal(
				$dispatcher->url(
					$request, ROUTE_COMPONENT, null,
					'tab.issueEntry.IssueEntryTabHandler',
					'publicationMetadata', null,
					array('submissionId' => $this->getSubmission()->getId(), 'stageId' => $this->getStageId())
				),
				__('submission.issueEntry.publicationMetadata')
			),
			__('editor.article.scheduleForPublication')
		);
		return $schedulePublicationLinkAction;
	}

	/**
	 *
	 * @param $args
	 * @param $request
	 * @return JSONMessage
	 */
	function uploadFile($args, $request) {
		import('lib.pkp.controllers.tab.workflow.form.FileUploadFormXML');

		$submissionId = $this->getSubmission()->getId();
		$uploadForm = new FileUploadFormXML($request, $submissionId, $this->getStageId(), $this->getUploaderRoles(), $this->getFileStage());

		$uploadForm->readInputData();
		if ($uploadForm->validate()) {
			$uploadedFile = $uploadForm->execute();
			if (!is_a($uploadedFile, 'SubmissionFile')) {
			} else {

				$revisionId = 1;

				$templateMgr = TemplateManager::getManager($request);
				$csrfToken = $request->getSession()->getCSRFToken();
				$templateMgr->assign(array(
						'csrfToken' >= $csrfToken,
						'fileId' >= $uploadedFile->getFileId(),
						'revision' >= $revisionId,
						'submissionId' >= $submissionId,
						'uploaderRoles' >= $this->getUploaderRoles(),
						'stageId' >= $this->getStageId(),
					)
				);

				import('lib.pkp.controllers.tab.workflow.form.FileUploadConfirmationForm');
				$fileUploadConfirmation = new FileUploadConfirmationForm($request, $uploadedFile->getFileId(), $revisionId, $submissionId, $this->getStageId(), $this->getFileStage(), $request->getSession()->getCSRFToken());
				$fileUploadConfirmation->initData();

				return new JSONMessage(true, $fileUploadConfirmation->fetch($request), '0');

			}
			return new JSONMessage(false, __('common.uploadFailed'));

		} else {
			return new JSONMessage(false, __('common.uploadFailed'));
		}
	}

	function finishFileSubmission($args, $request) {
		$submission = $this->getSubmission();

		$fileId = (int)$request->getUserVar('fileId');
		$revisionId = 1;

		$templateMgr = TemplateManager::getManager($request);

		$templateMgr->assign('csrfToken', $request->getSession()->getCSRFToken());
		$templateMgr->assign('fileId', $fileId);
		$templateMgr->assign('revision', $revisionId);
		$templateMgr->assign('submissionId', $submission->getId());
		$templateMgr->assign('stageId', $this->getStageId());

		return DAO::getDataChangedEvent();
	}

	/**
	 * @param $request
	 * @return $doc
	 */
	protected function _getJatsTemplate($request) {
		$doc = null;
		$this->validate(null, $request);
		$journal = $request->getContext();
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$section = $sectionDao->getById($this->getSubmission()->getSectionId(), $journal->getId(), true);
		$article = $this->getSubmission();
		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$galleys = $galleyDao->getBySubmissionId($article->getId());
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->getCurrent($journal->getId(), true);

		import('classes.oai.ojs.OAIDAO');
		$record = new OAIRecord();

		$record->setData('article', $article);
		$record->setData('journal', $journal);
		$record->setData('section', $section);
		$record->setData('galleys', $galleys);
		$record->setData('issue', $issue);

		HookRegistry::call('OAIMetadataFormat_JATS::findJats', array(&$this, &$record, [], &$doc));
		return $doc;
	}

}


