<?php

/**
 * @file plugins/generic/objectsForReview/ObjectsForReviewPlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ObjectForReviewPlugin
 * @ingroup plugins_generic_objectsForReview
 *
 * @brief Object for review plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');

define('OFR_MODE_FULL',		0x01);
define('OFR_MODE_METADATA',	0x02);

define('NOTIFICATION_TYPE_OFR_PLUGIN_BASE',		NOTIFICATION_TYPE_PLUGIN_BASE + 0x1000000);
// NOTIFICATION_TYPE_PLUGIN_BASE + 0x0000002 was previously ..._DISABLED (#7825)
define('NOTIFICATION_TYPE_OFR_OT_INSTALLED',		NOTIFICATION_TYPE_OFR_PLUGIN_BASE + 0x0000001);
define('NOTIFICATION_TYPE_OFR_OT_CREATED',		NOTIFICATION_TYPE_OFR_PLUGIN_BASE + 0x0000002);
define('NOTIFICATION_TYPE_OFR_OT_UPDATED',		NOTIFICATION_TYPE_OFR_PLUGIN_BASE + 0x0000003);
define('NOTIFICATION_TYPE_OFR_OT_ACTIVATED',		NOTIFICATION_TYPE_OFR_PLUGIN_BASE + 0x0000004);
define('NOTIFICATION_TYPE_OFR_OT_DEACTIVATED',		NOTIFICATION_TYPE_OFR_PLUGIN_BASE + 0x0000005);
define('NOTIFICATION_TYPE_OFR_OT_DELETED',		NOTIFICATION_TYPE_OFR_PLUGIN_BASE + 0x0000006);
define('NOTIFICATION_TYPE_OFR_CREATED',	NOTIFICATION_TYPE_OFR_PLUGIN_BASE + 0x0000007);
define('NOTIFICATION_TYPE_OFR_UPDATED',	NOTIFICATION_TYPE_OFR_PLUGIN_BASE + 0x0000008);
define('NOTIFICATION_TYPE_OFR_DELETED',	NOTIFICATION_TYPE_OFR_PLUGIN_BASE + 0x0000009);
define('NOTIFICATION_TYPE_OFR_REQUESTED',	NOTIFICATION_TYPE_OFR_PLUGIN_BASE + 0x000000A);
define('NOTIFICATION_TYPE_OFR_AUTHOR_ASSIGNED',	NOTIFICATION_TYPE_OFR_PLUGIN_BASE + 0x000000B);
define('NOTIFICATION_TYPE_OFR_AUTHOR_DENIED',	NOTIFICATION_TYPE_OFR_PLUGIN_BASE + 0x000000C);
define('NOTIFICATION_TYPE_OFR_AUTHOR_MAILED',	NOTIFICATION_TYPE_OFR_PLUGIN_BASE + 0x000000D);
define('NOTIFICATION_TYPE_OFR_AUTHOR_REMOVED',	NOTIFICATION_TYPE_OFR_PLUGIN_BASE + 0x000000E);
define('NOTIFICATION_TYPE_OFR_SUBMISSION_ASSIGNED',	NOTIFICATION_TYPE_OFR_PLUGIN_BASE + 0x000000F);
define('NOTIFICATION_TYPE_OFR_SETTINGS_SAVED',	NOTIFICATION_TYPE_OFR_PLUGIN_BASE + 0x0000010);


class ObjectsForReviewPlugin extends GenericPlugin {
	/**
	 * Constructor
	 */
	function ObjectsForReviewPlugin() {
		parent::GenericPlugin();
	}

	/**
	 * @see PKPPlugin::register()
	 * @return boolean true iff success
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();

		if ($success && $this->getEnabled()) {
			// Register DAOs.
			$this->registerDAOs();

			// Delete all plug-in data for a journal when the journal is deleted
			HookRegistry::register('JournalDAO::deleteJournalById', array($this, 'deleteJournalById'));

			// Editor links to reivew object types and objects for review pages
			HookRegistry::register('Templates::Editor::Index::AdditionalItems', array($this, 'displayLink'));

			// Handler for editor, author and public objects for review pages
			HookRegistry::register('LoadHandler', array($this, 'callbackLoadHandler'));

			// Enable TinyMCE for the text areas
			HookRegistry::register('TinyMCEPlugin::getEnableFields', array($this, 'enableTinyMCE'));

			// Enable notifications
			HookRegistry::register('NotificationManager::getNotificationContents', array($this, 'callbackNotificationContents'));

			// Ensure object for review user assignments are transferred when merging users
			HookRegistry::register('UserAction::mergeUsers', array($this, 'mergeObjectsForReviewAuthors'));

			$journal =& Request::getJournal();
			if ($journal) {
				// Register all supported/available locale/translation file
				$availableLocales = $journal->getSupportedLocaleNames();
				foreach ($availableLocales as $locale => $localeName) {
					$localePath = $this->getPluginPath() . '/locale/'. $locale . '/locale.xml';
					AppLocale::registerLocaleFile($locale, $localePath, true);
				}

				$mode = $this->getSetting($journal->getId(), 'mode');

				// If the menagment of objects reviewers should be supported
				// then include additional links and pages
				if ($mode == OFR_MODE_FULL) {
					// Navigation bar link to the public objects for review page
					HookRegistry::register('Templates::Common::Header::Navbar::CurrentJournal', array($this, 'displayLink'));

					// Author link to objects for review pages
					HookRegistry::register('Templates::Author::Index::AdditionalItems', array($this, 'displayLink'));
					// Display author's objects for review during submission
					HookRegistry::register('Author::SubmitHandler::saveSubmit', array($this, 'saveSubmitHandler'));
					HookRegistry::register('Templates::Author::Submit::Step5::AdditionalItems', array($this, 'displayAuthorObjectsForReview'));

				}

				// Display object metadata on article abstract page
				if ($this->getSetting($journal->getId(), 'displayAbstract')) {
					HookRegistry::register ('TemplateManager::display', array(&$this, 'handleTemplateDisplay'));
					HookRegistry::register ('Templates::Article::MoreInfo', array(&$this, 'displayAbstract'));
				}
			}
		}
		return $success;
	}

	/**
	 * @see PKPPlugin::getDisplayName()
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.objectsForReview.displayName');
	}

	/**
	 * @see PKPPlugin::getDescription()
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.objectsForReview.description');
	}

	/**
	 * @see PKPPlugin::getInstallSchemaFile()
	 * @return string
	 */
	function getInstallSchemaFile() {
		return $this->getPluginPath() . '/xml/schema.xml';
	}

	/**
	 * @see PKPPlugin::getInstallEmailTemplatesFile()
	 * @return string
	 */
	function getInstallEmailTemplatesFile() {
		return $this->getPluginPath() . '/xml/emailTemplates.xml';
	}

	/**
	 * @see PKPPlugin::getInstallEmailTemplateDataFile()
	 * @return string
	 */
	function getInstallEmailTemplateDataFile() {
		return $this->getPluginPath() . '/locale/{$installedLocale}/emailTemplates.xml';
	}

	/**
	 * @see PKPPlugin::getTemplatePath()
	 * @return string
	 */
	function getTemplatePath() {
		return parent::getTemplatePath() . 'templates/';
	}

	/**
	 * Get the handler path for this plugin.
	 * @return string
	 */
	function getHandlerPath() {
		return $this->getPluginPath() . '/pages/';
	}

	/**
	 * Get the stylesheet for this plugin.
	 * @return string
	 */
	function getStyleSheet() {
		return $this->getPluginPath() . '/styles/objectsForReview.css';
	}

	/**
	 * Instantiate and register the DAOs.
	 */
	function registerDAOs() {
		$this->import('classes.ReviewObjectTypeDAO');
		$this->import('classes.ReviewObjectMetadataDAO');
		$this->import('classes.ObjectForReviewPersonDAO');
		$this->import('classes.ObjectForReviewDAO');
		$this->import('classes.ObjectForReviewSettingsDAO');
		$this->import('classes.ObjectForReviewAssignmentDAO');

		$reviewObjectTypeDao = new ReviewObjectTypeDAO($this->getName());
		DAORegistry::registerDAO('ReviewObjectTypeDAO', $reviewObjectTypeDao);

		$reviewObjectMetadataDao = new ReviewObjectMetadataDAO($this->getName());
		DAORegistry::registerDAO('ReviewObjectMetadataDAO', $reviewObjectMetadataDao);

		$objectForReviewPersonDao = new ObjectForReviewPersonDAO($this->getName());
		DAORegistry::registerDAO('ObjectForReviewPersonDAO', $objectForReviewPersonDao);

		$objectForReviewDao = new ObjectForReviewDAO($this->getName());
		DAORegistry::registerDAO('ObjectForReviewDAO', $objectForReviewDao);

		$objectForReviewSettingsDao = new ObjectForReviewSettingsDAO($this->getName());
		DAORegistry::registerDAO('ObjectForReviewSettingsDAO', $objectForReviewSettingsDao);

		$objectForReviewAssignmentDao = new ObjectForReviewAssignmentDAO($this->getName());
		DAORegistry::registerDAO('ObjectForReviewAssignmentDAO', $objectForReviewAssignmentDao);
	}

	//
	// Application level hook implementations.
	//
	/**
	 * @see PKPPageRouter::route()
	 * @param $hookName string Hook name
	 * @param $params array Array of hook parameters
	 * @return boolean false to continue processing subsequent hooks
	 */
	function callbackLoadHandler($hookName, $params) {
		$page =& $params[0];
		$op =& $params[1];

		// Editor handler for review object types and for objects for review
		if ($page == 'editor') {
			if ($op) {
				$reviewObjectTypesEditorPages = $this->_getReviewObjectTypesEditorPages();
				$objectsForReviewEditorPages = $this->_getObjectsForReviewEditorPages();
				if (in_array($op, $reviewObjectTypesEditorPages)) {
					define('HANDLER_CLASS', 'ReviewObjectTypesEditorHandler');
					define('OBJECTS_FOR_REVIEW_PLUGIN_NAME', $this->getName());
					AppLocale::requireComponents(LOCALE_COMPONENT_APPLICATION_COMMON, LOCALE_COMPONENT_PKP_USER, LOCALE_COMPONENT_OJS_EDITOR);
					$handlerFile =& $params[2];
					$handlerFile = $this->getHandlerPath() . 'ReviewObjectTypesEditorHandler.inc.php';
				} elseif (in_array($op, $objectsForReviewEditorPages)) {
					define('HANDLER_CLASS', 'ObjectsForReviewEditorHandler');
					define('OBJECTS_FOR_REVIEW_PLUGIN_NAME', $this->getName());
					AppLocale::requireComponents(LOCALE_COMPONENT_APPLICATION_COMMON, LOCALE_COMPONENT_PKP_USER, LOCALE_COMPONENT_OJS_EDITOR);
					$handlerFile =& $params[2];
					$handlerFile = $this->getHandlerPath() . 'ObjectsForReviewEditorHandler.inc.php';
				}
			}
		}

		$journal =& Request::getJournal();
		if ($journal) {
			$mode = $this->getSetting($journal->getId(), 'mode');

			if ($mode == OFR_MODE_FULL) {
				if ($page == 'objectsForReview') { // Public pages handler for objects for review
					if ($op) {
						$publicPages = $this->_getObjectsForReviewPublicPages();
						if (in_array($op, $publicPages)) {
							define('HANDLER_CLASS', 'ObjectsForReviewHandler');
							define('OBJECTS_FOR_REVIEW_PLUGIN_NAME', $this->getName());
							AppLocale::requireComponents(LOCALE_COMPONENT_APPLICATION_COMMON);
							$handlerFile =& $params[2];
							$handlerFile = $this->getHandlerPath() . 'ObjectsForReviewHandler.inc.php';
						}
					}
				} else if ($page == 'author') { // Author handler for objects for reviews
					if ($op) {
						$objectsForReviewAuthorPages = $this->_getObjectsForReviewAuthorPages();
						if (in_array($op, $objectsForReviewAuthorPages)) {
							define('HANDLER_CLASS', 'ObjectsForReviewAuthorHandler');
							define('OBJECTS_FOR_REVIEW_PLUGIN_NAME', $this->getName());
							AppLocale::requireComponents(LOCALE_COMPONENT_APPLICATION_COMMON, LOCALE_COMPONENT_PKP_USER, LOCALE_COMPONENT_OJS_AUTHOR);
							$handlerFile =& $params[2];
							$handlerFile = $this->getHandlerPath() . 'ObjectsForReviewAuthorHandler.inc.php';
						}
					}
				}
			}
		}
	}

	/**
	 * Enable TinyMCE support for object for review text fields.
	 * @param $hookName string (TinyMCEPlugin::getEnableFields)
	 * @param $params array (plugin, fields)
	 * @return boolean false to continue processing subsequent hooks
	 */
	function enableTinyMCE($hookName, $params) {
		$fields =& $params[1];

		$page = Request::getRequestedPage();
		$op = Request::getRequestedOp();

		$reviewObjectTypeId = (int) Request::getUserVar('reviewObjectTypeId');
		$reviewObjectMetadataDao =& DAORegistry::getDAO('ReviewObjectMetadataDAO');
		$textareaReviewObjectMetadataIds = $reviewObjectMetadataDao->getTextareaReviewObjectMetadataIds($reviewObjectTypeId);

		if ($page == 'editor') {
			if ($op == 'createReviewObjectType' || $op == 'editReviewObjectType' || $op == 'updateReviewObjectType') {
				$fields[] = 'description';
			} elseif ($op == 'previewReviewObjectType') {
				$fields[] = 'textarea_metadata_type';
			} elseif ($op == 'createObjectForReview' || $op == 'editObjectForReview' || $op == 'updateObjectForReview') {
				$fields[] = 'notes';
				foreach ($textareaReviewObjectMetadataIds as $metadataId) {
					$fields[] = "ofrSettings-$metadataId";
				}
			} elseif ($op == 'objectsForReviewSettings') {
				$fields[] = 'additionalInformation';
			}
		}
		return false;
	}

	/**
	 * Hook registry function to provide notification messages
	 * @param $hookName string (NotificationManager::getNotificationContents)
	 * @param $args array ($notification, $message)
	 * @return boolean false to continue processing subsequent hooks
	 */
	function callbackNotificationContents($hookName, $args) {
		$notification =& $args[0];
		$message =& $args[1];

		$type = $notification->getType();
		assert(isset($type));
		switch ($type) {
			case NOTIFICATION_TYPE_OFR_OT_INSTALLED:
				$message = __('plugins.generic.objectsForReview.notification.objectTypeInstalled');
				break;
			case NOTIFICATION_TYPE_OFR_OT_CREATED:
				$message = __('plugins.generic.objectsForReview.notification.objectTypeCreated');
				break;
			case NOTIFICATION_TYPE_OFR_OT_UPDATED:
				$message = __('plugins.generic.objectsForReview.notification.objectTypeUpdated');
				break;
			case NOTIFICATION_TYPE_OFR_OT_ACTIVATED:
				$message = __('plugins.generic.objectsForReview.notification.objectTypeActivated');
				break;
			case NOTIFICATION_TYPE_OFR_OT_DEACTIVATED:
				$message = __('plugins.generic.objectsForReview.notification.objectTypeDeactivated');
				break;
			case NOTIFICATION_TYPE_OFR_OT_DELETED:
				$message = __('plugins.generic.objectsForReview.notification.objectTypeDeleted');
				break;
			case NOTIFICATION_TYPE_OFR_CREATED:
				$message = __('plugins.generic.objectsForReview.notification.ofrCreated');
				break;
			case NOTIFICATION_TYPE_OFR_UPDATED:
				$message = __('plugins.generic.objectsForReview.notification.ofrUpdated');
				break;
			case NOTIFICATION_TYPE_OFR_DELETED:
				$message = __('plugins.generic.objectsForReview.notification.ofrDeleted');
				break;
			case NOTIFICATION_TYPE_OFR_REQUESTED:
				$message = __('plugins.generic.objectsForReview.notification.ofrRequested');
				break;
			case NOTIFICATION_TYPE_OFR_AUTHOR_ASSIGNED:
				$message = __('plugins.generic.objectsForReview.notification.ofrAuthorAssigned');
				break;
			case NOTIFICATION_TYPE_OFR_AUTHOR_DENIED:
				$message = __('plugins.generic.objectsForReview.notification.ofrAuthorDenied');
				break;
			case NOTIFICATION_TYPE_OFR_AUTHOR_MAILED:
				$message = __('plugins.generic.objectsForReview.notification.ofrAuthorMailed');
				break;
			case NOTIFICATION_TYPE_OFR_AUTHOR_REMOVED:
				$message = __('plugins.generic.objectsForReview.notification.ofrAuthorRemoved');
				break;
			case NOTIFICATION_TYPE_OFR_SUBMISSION_ASSIGNED:
				$message = __('plugins.generic.objectsForReview.notification.ofrSubmissionAssigned');
				break;
			case NOTIFICATION_TYPE_OFR_SETTINGS_SAVED:
				$message = __('plugins.generic.objectsForReview.notification.ofrSettingsSaved');
				break;
		}
	}

	/**
	 * Transfer object for review user assignments when merging users.
	 * @param $hookName string (UserAction::mergeUsers)
	 * @param $args array ($oldUserId, $newUserId)
	 * @return boolean false to continue processing subsequent hooks
	 */
	function mergeObjectsForReviewAuthors($hookName, $params) {
		$oldUserId =& $params[0];
		$newUserId =& $params[1];

		$journal =& Request::getJournal();

		$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
		$objectForReviewAssignments =& $ofrAssignmentDao->getAllByUserId($oldUserId);
		// The user validation is presumed to happen earlier, before merge action is called
		$ofrAssignmentDao->transferAssignments($oldUserId, $newUserId);
		return false;
	}

	/**
	 * Delete all plug-in data for a journal when the journal is deleted
	 * @param $hookName string (JournalDAO::deleteJournalById)
	 * @param $args array (JournalDAO, journalId)
	 * @return boolean false to continue processing subsequent hooks
	 */
	function deleteJournalById($hookName, $params) {
		$journalId = $params[1];
		$reviewObjectTypeDao =& DAORegistry::getDAO('ReviewObjectTypeDAO');
		$reviewObjectTypeDao->deleteByContextId($journalId);
		$objectForReviewDao =& DAORegistry::getDAO('ObjectForReviewDAO');
		$objectForReviewDao->deleteByContextId($journalId);
		return false;
	}

	/**
	 * Display editor, author and public links.
	 * @param $hookName string
	 * (Templates::Editor::Index::AdditionalItems |
	 * Templates::Common::Header::Navbar::CurrentJournal |
	 * Templates::Author::Index::AdditionalItems)
	 * @param $args array
	 * @return boolean false to continue processing subsequent hooks
	 */
	function displayLink($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty =& $params[1];
			$output =& $params[2];
			$journal =& Request::getJournal();
			$templateMgr = TemplateManager::getManager();
			if ($hookName == 'Templates::Editor::Index::AdditionalItems') { // On editor's home page
				$output .= '<h3>' . __('plugins.generic.objectsForReview.editor.objectsForReview') . '</h3>
							<ul class="plain">
							<li>&#187; <a href="' . Request::url(null, 'editor', 'reviewObjectTypes') . '">' . __('plugins.generic.objectsForReview.editor.objectTypes') . '</a></li>
							<li>&#187; <a href="' . Request::url(null, 'editor', 'objectsForReview', 'all') . '">' . __('plugins.generic.objectsForReview.editor.objectsForReview') . '</a></li>
							</ul>';
			} elseif ($hookName == 'Templates::Author::Index::AdditionalItems') { // On author's home page
				$output .= '<br /><div class="separator"></div><h3>' . __('plugins.generic.objectsForReview.author.objectsForReview') . '</h3><ul class="plain"><li>&#187; <a href="' . Request::url(null, 'author', 'objectsForReview', 'all') . '">' . __('plugins.generic.objectsForReview.author.myObjectsForReview') . '</a></li></ul><br />';
			} elseif ($hookName == 'Templates::Common::Header::Navbar::CurrentJournal' && $this->getSetting($journal->getId(), 'displayListing')) { // In the main nav bar
				$output .= '<li><a href="' . Request::url(null, 'objectsForReview') . '" target="_parent">' . __('plugins.generic.objectsForReview.public.headerLink') . '</a></li>';
			}
		}
		return false;
	}

	/**
	 * Display author's objects for review during submission step 5.
	 * @param $hookName string (Templates::Author::Submit::Step5::AdditionalItems)
	 * @param $args array
	 * @return boolean false to continue processing subsequent hooks
	 */
	function displayAuthorObjectsForReview($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty =& $params[1];
			$output =& $params[2];

			// Get the journal and the submitting user
			$journal =& Request::getJournal();
			$user =& Request::getUser();
			if ($journal && $user) {
				// Get the assignemnts for this user
				$ofrDao =& DAORegistry::getDAO('ObjectForReviewDAO');
				$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
				$rangeInfo =& Handler::getRangeInfo('objectsForReview');
				$objectForReviewAssignments =& $ofrAssignmentDao->getAllByUserId($user->getId());
				$authorObjects = array();
				foreach ($objectForReviewAssignments as $objectForReviewAssignment) {
					// Consider only assigned and mailed assignments
					if ($objectForReviewAssignment->getStatus() == OFR_STATUS_ASSIGNED || $objectForReviewAssignment->getStatus() == BFR_STATUS_MAILED) {
						$objectForReview = $ofrDao->getById($objectForReviewAssignment->getObjectId(), $journal->getId());
						$authorObjects[$objectForReviewAssignment->getObjectId()] = substr($objectForReview->getTitle(), 0, 40);
					}
				}
				$smarty->assign('authorObjects', $authorObjects);
				$output .= $smarty->fetch($this->getTemplatePath() . 'author' . '/' . 'submissionObjectsForReview.tpl');
			}
		}
		return false;
	}

	/**
	 * Allow author to specify objects for review during article submission.
	 * @param $hookName string (Author::SubmitHandler::saveSubmit)
	 * @param $args array (step, article, submitForm)
	 * @return boolean false to continue processing subsequent hooks
	 */
	function saveSubmitHandler($hookName, $params) {
		$step =& $params[0];
		$article =& $params[1];

		// If it's the last submission step
		if ($step == 5) {
			// Get the journal and the submitting user
			$journal =& Request::getJournal();
			$user =& Request::getUser();
			if ($journal && $user) {
				$journalId = $journal->getId();
				$userId = $user->getId();
				// The submission/article could contain/be a review of several objects
				// Get the specified objects for review, this article is about
				$submissionObjectsForReview = Request::getUserVar('submissionObjectsForReview');
				if ($submissionObjectsForReview) {
					$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
					$ofrDao =& DAORegistry::getDAO('ObjectForReviewDAO');
					foreach ($submissionObjectsForReview as $objectForReviewId) {
						// Ensure the object exists i.e. is for this journal
						if ($ofrDao->objectForReviewExists($objectForReviewId, $journalId)) {
							// Ensure the assignment exists for the submitting user
							// and consider only assigned and mailed assignments
							$ofrAssignment =& $ofrAssignmentDao->getByObjectAndUserId($objectForReviewId, $userId);
							if (isset($ofrAssignment) && ($ofrAssignment->getStatus() == OFR_STATUS_ASSIGNED || $ofrAssignment->getStatus() == BFR_STATUS_MAILED)) {
								$ofrAssignment->setStatus(OFR_STATUS_SUBMITTED);
								$ofrAssignment->setSubmissionId($article->getId());
								$ofrAssignmentDao->updateObject($ofrAssignment);
							}
						}
					}
				}
			}
		}
		return false;
	}

	/**
	 * Add the plug-in stylesheets before displaying the article template.
	 * @param $hookName string (TemplateManager::display)
	 * @param $args array
	 * @return boolean false to continue processing subsequent hooks
	 */
	function handleTemplateDisplay($hookName, $args) {
		$templateMgr =& $args[0];
		$template =& $args[1];

		switch ($template) {
			case 'article/article.tpl':
				$templateMgr = TemplateManager::getManager();
				$templateMgr->addStyleSheet(Request::getBaseUrl() . '/' . $this->getStyleSheet());
				break;
		}
		return false;
	}

	/**
	 * Display object metadata on the article abstract pages.
	 * @param $hookName string (Templates::Article::MoreInfo)
	 * @param $args array
	 * @return boolean false to continue processing subsequent hooks
	 */
	function displayAbstract($hookName, $params) {
		$smarty =& $params[1];
		$output =& $params[2];

		// Get the journal and the article
		$journal =& Request::getJournal();
		$journalId = $journal->getId();
		$article = $smarty->get_template_vars('article');
		$pubObject = $smarty->get_template_vars('pubObject');
		// Only consider the abstract page
		if ($article && is_a($pubObject, 'Article')) {
			// Get the assignemnts for this article
			$objectsForReview = array();
			$ofrDao =& DAORegistry::getDAO('ObjectForReviewDAO');
			$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
			$objectForReviewAssignments =& $ofrAssignmentDao->getAllBySubmissionId($article->getId());
			foreach ($objectForReviewAssignments as $objectForReviewAssignment) {
				$objectForReview = $ofrDao->getById($objectForReviewAssignment->getObjectId(), $journalId);
				$objectsForReview[] = $objectForReview;
			}

			if (!empty($objectsForReview)) {
				// Get metadata for the review type
				$reviewObjectTypeDao =& DAORegistry::getDAO('ReviewObjectTypeDAO');
				$allTypes =& $reviewObjectTypeDao->getTypeIdsAlphabetizedByContext($journalId);
				$reviewObjectMetadataDao =& DAORegistry::getDAO('ReviewObjectMetadataDAO');
				$allReviewObjectsMetadata = array();
				foreach ($allTypes as $type) {
					$typeId = $type['typeId'];
					$typeMetadata = $reviewObjectMetadataDao->getArrayByReviewObjectTypeId($typeId);
					$allReviewObjectsMetadata[$typeId] = $typeMetadata;
				}

				$publicFileManager = new PublicFileManager();
				$coverPagePath = Request::getBaseUrl() . '/';
				$coverPagePath .= $publicFileManager->getJournalFilesPath($journalId) . '/';
				$smarty->assign('coverPagePath', $coverPagePath);

				$smarty->assign('objectsForReview', $objectsForReview);
				$smarty->assign('allReviewObjectsMetadata', $allReviewObjectsMetadata);
				$smarty->assign('multipleOptionsTypes', ReviewObjectMetadata::getMultipleOptionsTypes());
				$smarty->assign('ofrListing', true);
				$smarty->assign('ofrTemplatePath', $this->getTemplatePath());
				$output .= $smarty->fetch($this->getTemplatePath() . 'articleObjectsForReview.tpl');
			}
		}
		return false;
	}

	//
	// Private helper methods
	//
	/**
	 * Get editor pages for review object types
	 * @return array
	 */
	function _getReviewObjectTypesEditorPages() {
		return array(
					'reviewObjectTypes',
					'createReviewObjectType',
					'editReviewObjectType',
					'updateReviewObjectType',
					'previewReviewObjectType',
					'deleteReviewObjectType',
					'activateReviewObjectType',
					'deactivateReviewObjectType',
					'copyReviewObjectType',
					'updateOrInstallReviewObjectTypes',
					'reviewObjectMetadata',
					'createReviewObjectMetadata',
					'editReviewObjectMetadata',
					'updateReviewObjectMetadata',
					'deleteReviewObjectMetadata',
					'moveReviewObjectMetadata',
					'copyOrUpdateReviewObjectMetadata'
				);
	}

	/**
	 * Get editor pages for objects for review
	 * @return array
	 */
	function _getObjectsForReviewEditorPages() {
		return array(
					'objectsForReview',
					'objectsForReviewSettings',
					'createObjectForReview',
					'editObjectForReview',
					'updateObjectForReview',
					'removeObjectForReviewCoverPage',
					'deleteObjectForReview',
					'selectObjectForReviewAuthor',
					'assignObjectForReviewAuthor',
					'acceptObjectForReviewAuthor',
					'denyObjectForReviewAuthor',
					'notifyObjectForReviewMailed',
					'removeObjectForReviewAssignment',
					'selectObjectForReviewSubmission',
					'assignObjectForReviewSubmission',
					'editObjectForReviewAssignment',
					'updateObjectForReviewAssignment'
				);
	}

	/**
	 * Get public pages for objects for review
	 * @return array
	 */
	function _getObjectsForReviewPublicPages() {
		return array(
					'index',
					'viewObjectForReview'
				);
	}

	/**
	 * Get author pages for objects for review
	 * @return array
	 */
	function _getObjectsForReviewAuthorPages() {
		return array(
					'objectsForReview',
					'requestObjectForReview'
				);
	}
}
?>
