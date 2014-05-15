<?php

/**
 * @file plugins/generic/objectsForReview/pages/ReviewObjectTypesEditorHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewObjectTypesEditorHandler
 * @ingroup plugins_generic_objectsForReview
 *
 * @brief Handle requests for editor objects for review functions.
 */

import('classes.handler.Handler');

class ReviewObjectTypesEditorHandler extends Handler {

	/**
	 * Display objects for review listing pages.
	 */
	function reviewObjectTypes($args, &$request) {
		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$rangeInfo = $this->getRangeInfo('reviewObjectTypes');
		$reviewObjectTypeDao =& DAORegistry::getDAO('ReviewObjectTypeDAO');
		$types =& $reviewObjectTypeDao->getTypeIdsAlphabetizedByContext($journalId);
		$totalResults = count($types);
		$types = array_slice($types, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
		import('lib.pkp.classes.core.VirtualArrayIterator');
		$results = new VirtualArrayIterator($types, $totalResults, $rangeInfo->getPage(), $rangeInfo->getCount());

		$this->setupTemplate($request);
		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign_by_ref('results', $results);
		$plugin =& $this->_getObjectsForReviewPlugin();
		$pluginLocales = $this->_getPluginLocales();
		$missingReviewObjects = $this->_getMissingDefaultReviewObjectsKeys($journalId);
		$templateMgr->assign_by_ref('pluginLocales', $pluginLocales);
		$templateMgr->assign_by_ref('missingReviewObjects', $missingReviewObjects);
		$templateMgr->display($plugin->getTemplatePath() . 'editor/reviewObjectTypes.tpl');

	}

	/**
	 * Create a new review object type.
	 */
	function createReviewObjectType($args, &$request) {
		$this->editReviewObjectType($args, &$request);
	}

	/**
	 * Create/edit a review object type.
	 */
	function editReviewObjectType($args, &$request) {
		$typeId = array_shift($args);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$reviewObjectTypeDao =& DAORegistry::getDAO('ReviewObjectTypeDAO');
		$reviewObjectType =& $reviewObjectTypeDao->getById($typeId, $journalId);
		if ($typeId && (!isset($reviewObjectType))) {
			$request->redirect(null, null, 'reviewObjectTypes');
		}

		$this->setupTemplate($request, true, $reviewObjectType);
		$templateMgr =& TemplateManager::getManager($request);
		if ($typeId) {
			$templateMgr->assign('pageTitle', 'plugins.generic.objectsForReview.editor.objectType.edit');
		} else {
			$templateMgr->assign('pageTitle', 'plugins.generic.objectsForReview.editor.objectType.create');
		}

		$plugin =& $this->_getObjectsForReviewPlugin();
		$plugin->import('classes.form.ReviewObjectTypeForm');
		$reviewObjectTypeForm = new ReviewObjectTypeForm(OBJECTS_FOR_REVIEW_PLUGIN_NAME, $typeId);
		if ($reviewObjectTypeForm->isLocaleResubmit()) {
			$reviewObjectTypeForm->readInputData();
		} else {
			$reviewObjectTypeForm->initData();
		}
		$reviewObjectTypeForm->display($request);
	}

	/**
	 * Update a review object type.
	 */
	function updateReviewObjectType($args, &$request) {
		$typeId = (int) $request->getUserVar('typeId');

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$reviewObjectTypeDao =& DAORegistry::getDAO('ReviewObjectTypeDAO');
		$reviewObjectType =& $reviewObjectTypeDao->getById($typeId, $journalId);
		if ($typeId && (!isset($reviewObjectType))) {
				$request->redirect(null, null, 'reviewObjectTypes');
		}

		$plugin =& $this->_getObjectsForReviewPlugin();
		$plugin->import('classes.form.ReviewObjectTypeForm');
		$reviewObjectTypeForm = new ReviewObjectTypeForm(OBJECTS_FOR_REVIEW_PLUGIN_NAME, $typeId);
		$reviewObjectTypeForm->readInputData();
		if (!$typeId) {
			$formLocale = $reviewObjectTypeForm->getFormLocale();
			// Reorder option items
			$options = $reviewObjectTypeForm->getData('possibleOptions');
			if (isset($options[$formLocale]) && is_array($options[$formLocale])) {
				usort($options[$formLocale], create_function('$a,$b','return $a[\'order\'] == $b[\'order\'] ? 0 : ($a[\'order\'] < $b[\'order\'] ? -1 : 1);'));
			}
			$reviewObjectTypeForm->setData('possibleOptions', $options);

			if ($request->getUserVar('addOption')) {
				// Add an option item
				$editData = true;
				$options = $reviewObjectTypeForm->getData('possibleOptions');
				if (!isset($options[$formLocale]) || !is_array($options[$formLocale])) {
					$options[$formLocale] = array();
					$lastOrder = 0;
				} else {
					$lastOrder = $options[$formLocale][count($options[$formLocale])-1]['order'];
				}
				array_push($options[$formLocale], array('order' => $lastOrder+1));
				$reviewObjectTypeForm->setData('possibleOptions', $options);

			} else if (($delOption = $request->getUserVar('delOption')) && count($delOption) == 1) {
				// Delete a response item
				$editData = true;
				list($delOption) = array_keys($delOption);
				$delOption = (int) $delOption;
				$options = $reviewObjectTypeForm->getData('possibleOptions');
				if (!isset($options[$formLocale])) $options[$formLocale] = array();
				array_splice($options[$formLocale], $delOption, 1);
				$reviewObjectTypeForm->setData('possibleOptions', $options);
			}
		}

		if (!isset($editData) && $reviewObjectTypeForm->validate()) {
			$reviewObjectTypeForm->execute();
			// Notification
			if ($typeId) {
				$notificationType = NOTIFICATION_TYPE_OFR_OT_UPDATED;
			} else {
				$notificationType = NOTIFICATION_TYPE_OFR_OT_CREATED;
			}
			$this->_createTrivialNotification($notificationType, $request);
			$request->redirect(null, 'editor', 'reviewObjectTypes');
		} else {
			$this->setupTemplate($request, true, $reviewObjectType);
			$templateMgr =& TemplateManager::getManager($request);
			if ($typeId) {
				$templateMgr->assign('pageTitle', 'plugins.generic.objectsForReview.editor.objectType.edit');
			} else {
				$templateMgr->assign('pageTitle', 'plugins.generic.objectsForReview.editor.objectType.create');
			}
			$reviewObjectTypeForm->display($request);
		}
	}

	/**
	 * Preview a review object type.
	 */
	function previewReviewObjectType($args, &$request) {
		$typeId = array_shift($args);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$reviewObjectTypeDao =& DAORegistry::getDAO('ReviewObjectTypeDAO');
		$reviewObjectType =& $reviewObjectTypeDao->getById($typeId, $journalId);
		if (!isset($reviewObjectType)) {
			$request->redirect(null, 'editor', 'reviewObjectTypes');
		}
		$reviewObjectMetadataDao =& DAORegistry::getDAO('ReviewObjectMetadataDAO');
		$reviewObjectMetadata =& $reviewObjectMetadataDao->getArrayByReviewObjectTypeId($typeId);

		$this->setupTemplate($request, true, $reviewObjectType);
		$templateMgr =& TemplateManager::getManager($request);

		$templateMgr->assign('pageTitle', 'plugins.generic.objectsForReview.editor.objectType.preview');
		$templateMgr->assign_by_ref('reviewObjectType', $reviewObjectType);
		$templateMgr->assign('reviewObjectMetadata', $reviewObjectMetadata);

		$languageDao =& DAORegistry::getDAO('LanguageDAO');
		$languages =& $languageDao->getLanguages();
		$validLanguages = array('' => __('plugins.generic.objectsForReview.editor.objectForReview.chooseLanguage'));
		while (list(, $language) = each($languages)) {
			$validLanguages[$language->getCode()] = $language->getName();
		}
		$templateMgr->assign('validLanguages', $validLanguages);
		$plugin =& $this->_getObjectsForReviewPlugin();
		$templateMgr->display($plugin->getTemplatePath() . 'editor/previewReviewObjectType.tpl');
	}

	/**
	 * Delete a review object type.
	 */
	function deleteReviewObjectType($args, &$request) {
		$typeId = array_shift($args);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$reviewObjectTypeDao =& DAORegistry::getDAO('ReviewObjectTypeDAO');
		if ($reviewObjectTypeDao->reviewObjectTypeExists($typeId, $journalId)) {
			$reviewObjectTypeDao->deleteById($typeId, $journalId);
		}

		$this->_createTrivialNotification(NOTIFICATION_TYPE_OFR_OT_DELETED, $request);

		$request->redirect(null, 'editor', 'reviewObjectTypes');
	}

	/**
	 * Activate a review object type to be used.
	 */
	function activateReviewObjectType($args, &$request) {
		$typeId = array_shift($args);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$reviewObjectTypeDao =& DAORegistry::getDAO('ReviewObjectTypeDAO');
		$reviewObjectType =& $reviewObjectTypeDao->getById($typeId, $journalId);
		if (isset($reviewObjectType) && !$reviewObjectType->getActive()) {
				$reviewObjectType->setActive(1);
				$reviewObjectTypeDao->updateObject($reviewObjectType);
		}

		$this->_createTrivialNotification(NOTIFICATION_TYPE_OFR_OT_ACTIVATED, $request);

		$request->redirect(null, 'editor', 'reviewObjectTypes');
	}

	/**
	 * Deactivate a review object type.
	 */
	function deactivateReviewObjectType($args, &$request) {
		$typeId = array_shift($args);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$reviewObjectTypeDao =& DAORegistry::getDAO('ReviewObjectTypeDAO');
		$reviewObjectType =& $reviewObjectTypeDao->getById($typeId, $journalId);
		if (isset($reviewObjectType) && $reviewObjectType->getActive()) {
				$reviewObjectType->setActive(0);
				$reviewObjectTypeDao->updateObject($reviewObjectType);
		}

		$this->_createTrivialNotification(NOTIFICATION_TYPE_OFR_OT_DEACTIVATED, $request);

		$request->redirect(null, 'editor', 'reviewObjectTypes');
	}

	/**
	 * Update review object locale data.
	 */
	function updateOrInstallReviewObjectTypes($args, &$request) {
		$journal =& $request->getJournal();
		$plugin =& $this->_getObjectsForReviewPlugin();

		if ($request->getUserVar('updateLocaleData')) {
			$reviewObjectTypes = $request->getUserVar('update');
			$locales = $request->getUserVar('updateLocales');
			$this->_updateOrInstallReviewObjectTypes($journal, $reviewObjectTypes, $locales, 'update');
			$notificationType = NOTIFICATION_TYPE_OFR_OT_UPDATED;
		} elseif ($request->getUserVar('installReviewObjects')) {
			$reviewObjectTypes = $request->getUserVar('reviewObjects');
			$locales = $request->getUserVar('installLocales');
			$this->_updateOrInstallReviewObjectTypes($journal, $reviewObjectTypes, $locales, 'install');
			$notificationType = NOTIFICATION_TYPE_OFR_OT_INSTALLED;
		}
		$this->_createTrivialNotification($notificationType, $request);
		$request->redirect(null, 'editor', 'reviewObjectTypes');
	}

	/**
	 * Display a list of the metadata within a review object type.
	 */
	function reviewObjectMetadata($args, &$request) {
		$typeId = array_shift($args);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$reviewObjectTypeDao =& DAORegistry::getDAO('ReviewObjectTypeDAO');
		$reviewObjectType =& $reviewObjectTypeDao->getById($typeId, $journalId);
		if (!isset($reviewObjectType)) {
			$request->redirect(null, 'editor', 'reviewObjectTypes');
		}

		$reviewObjectMetadataDao =& DAORegistry::getDAO('ReviewObjectMetadataDAO');
		$reviewObjectMetadata =& $reviewObjectMetadataDao->getByReviewObjectTypeId($typeId);

		$allTypes =& $reviewObjectTypeDao->getTypeIdsAlphabetizedByContext($journalId);
		$typeOptions = array();
		foreach ($allTypes as $type) {
			$typeOptions[$type['typeId']] = $type['typeName'];
		}
		$this->setupTemplate($request, true, $reviewObjectType);
		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->addJavaScript('lib/pkp/js/lib/jquery/plugins/jquery.tablednd.js');
		$templateMgr->addJavaScript('lib/pkp/js/functions/tablednd.js');
		$templateMgr->assign_by_ref('reviewObjectMetadata', $reviewObjectMetadata);
		$templateMgr->assign_by_ref('typeOptions', $typeOptions);
		$templateMgr->assign('typeId', $typeId);
		$plugin =& $this->_getObjectsForReviewPlugin();
		$templateMgr->display($plugin->getTemplatePath() . 'editor/reviewObjectMetadata.tpl');
	}

	/**
	 * Create a new review object metadata.
	 */
	function createReviewObjectMetadata($args, &$request) {
		$this->editReviewObjectMetadata($args, &$request);
	}

	/**
	 * Create/edit a review object metadata.
	 */
	function editReviewObjectMetadata($args, &$request) {
		$typeId = array_shift($args);
		$metadataId = array_shift($args);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$reviewObjectTypeDao =& DAORegistry::getDAO('ReviewObjectTypeDAO');
		$reviewObjectType =& $reviewObjectTypeDao->getById($typeId, $journalId);
		$reviewObjectMetadataDao =& DAORegistry::getDAO('ReviewObjectMetadataDAO');
		if (!isset($reviewObjectType) || ($metadataId && !$reviewObjectMetadataDao->reviewObjectMetadataExists($metadataId, $typeId))) {
			$request->redirect(null, 'editor', 'reviewObjectMetadata', array($typeId));
		}

		$this->setupTemplate($request, true, $reviewObjectType);
		$templateMgr =& TemplateManager::getManager($request);
		if ($metadataId) {
			$templateMgr->assign('pageTitle', 'plugins.generic.objectsForReview.editor.objectMetadata.edit');
		} else {
			$templateMgr->assign('pageTitle', 'plugins.generic.objectsForReview.editor.objectMetadata.create');
		}

		$plugin =& $this->_getObjectsForReviewPlugin();
		$plugin->import('classes.form.ReviewObjectMetadataForm');
		$reviewObjectMetadataForm = new ReviewObjectMetadataForm(OBJECTS_FOR_REVIEW_PLUGIN_NAME, $typeId, $metadataId);
		if ($reviewObjectMetadataForm->isLocaleResubmit()) {
			$reviewObjectMetadataForm->readInputData();
		} else {
			$reviewObjectMetadataForm->initData();
		}
		$reviewObjectMetadataForm->display($request);
	}

	/**
	 * Update a review object metadata.
	 */
	function updateReviewObjectMetadata($args, &$request) {
		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$typeId = (int) $request->getUserVar('reviewObjectTypeId');
		$metadataId = (int) $request->getUserVar('metadataId');

		$reviewObjectTypeDao =& DAORegistry::getDAO('ReviewObjectTypeDAO');
		$reviewObjectType =& $reviewObjectTypeDao->getById($typeId, $journalId);
		$reviewObjectMetadataDao =& DAORegistry::getDAO('ReviewObjectMetadataDAO');
		if (!isset($reviewObjectType) || ($metadataId && !$reviewObjectMetadataDao->reviewObjectMetadataExists($metadataId, $typeId))) {
			$request->redirect(null, null, 'reviewObjectMetadata', array($typeId));
		}

		$plugin =& $this->_getObjectsForReviewPlugin();
		$plugin->import('classes.form.ReviewObjectMetadataForm');
		$reviewObjectMetadataForm = new ReviewObjectMetadataForm(OBJECTS_FOR_REVIEW_PLUGIN_NAME, $typeId, $metadataId);
		$reviewObjectMetadataForm->readInputData();
		$formLocale = $reviewObjectMetadataForm->getFormLocale();
		// Reorder option items
		$options = $reviewObjectMetadataForm->getData('possibleOptions');
		if (isset($options[$formLocale]) && is_array($options[$formLocale])) {
			usort($options[$formLocale], create_function('$a,$b','return $a[\'order\'] == $b[\'order\'] ? 0 : ($a[\'order\'] < $b[\'order\'] ? -1 : 1);'));
		}
		$reviewObjectMetadataForm->setData('possibleOptions', $options);

		if ($request->getUserVar('addOption')) {
			// Add an option item
			$editData = true;
			$options = $reviewObjectMetadataForm->getData('possibleOptions');
			if (!isset($options[$formLocale]) || !is_array($options[$formLocale])) {
				$options[$formLocale] = array();
				$lastOrder = 0;
			} else {
				$lastOrder = $options[$formLocale][count($options[$formLocale])-1]['order'];
			}
			array_push($options[$formLocale], array('order' => $lastOrder+1));
			$reviewObjectMetadataForm->setData('possibleOptions', $options);

		} else if (($delOption = $request->getUserVar('delOption')) && count($delOption) == 1) {
			// Delete a response item
			$editData = true;
			list($delOption) = array_keys($delOption);
			$delOption = (int) $delOption;
			$options = $reviewObjectMetadataForm->getData('possibleOptions');
			if (!isset($options[$formLocale])) $options[$formLocale] = array();
			array_splice($options[$formLocale], $delOption, 1);
			$reviewObjectMetadataForm->setData('possibleOptions', $options);
		}

		if (!isset($editData) && $reviewObjectMetadataForm->validate()) {
			$reviewObjectMetadataForm->execute();
			$this->_createTrivialNotification(NOTIFICATION_TYPE_OFR_OT_UPDATED, $request);
			$request->redirect(null, 'editor', 'reviewObjectMetadata', array($typeId));
		} else {
			$this->setupTemplate($request, true, $reviewObjectType);
			$templateMgr =& TemplateManager::getManager($request);
			if ($metadataId) {
				$templateMgr->assign('pageTitle', 'plugins.generic.objectsForReview.editor.objectMetadata.edit');
			} else {
				$templateMgr->assign('pageTitle', 'plugins.generic.objectsForReview.editor.objectMetadata.create');
			}
			$reviewObjectMetadataForm->display($request);
		}

	}

	/**
	 * Delete a review object metadata.
	 */
	function deleteReviewObjectMetadata($args, &$request) {
		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$typeId = array_shift($args);
		$metadataId = array_shift($args);

		$reviewObjectMetadataDao =& DAORegistry::getDAO('ReviewObjectMetadataDAO');
		$reviewObjectMetadataDao->deleteById($metadataId, $typeId);

		$this->_createTrivialNotification(NOTIFICATION_TYPE_OFR_OT_UPDATED, $request);

		$request->redirect(null, 'editor', 'reviewObjectMetadata', array($typeId));
	}

	/**
	 * Change the sequence of a review object metadata.
	 */
	function moveReviewObjectMetadata($args, &$request) {
		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$reviewObjectMetadataDao =& DAORegistry::getDAO('ReviewObjectMetadataDAO');
		$reviewObjectMetadata =& $reviewObjectMetadataDao->getById($request->getUserVar('id'));

		if (!isset($reviewObjectMetadata)) {
			$request->redirect(null, 'editor', 'reviewObjectTypes');
		}

		$direction = $request->getUserVar('d');

		if ($direction != null) {
			// moving with up or down arrow
			$reviewObjectMetadata->setSequence($reviewObjectMetadata->getSequence() + ($direction == 'u' ? -1.5 : 1.5));

		} else {
			// drag and drop
			$prevId = $request->getUserVar('prevId');
			if ($prevId == null)
				$prevSeq = 0;
			else {
				$prevReviewObjectMetadata = $reviewObjectMetadataDao->getById($prevId);
				$prevSeq = $prevReviewObjectMetadata->getSequence();
			}

			$reviewObjectMetadata->setSequence($prevSeq + .5);
		}

		$reviewObjectMetadataDao->updateObject($reviewObjectMetadata);
		$reviewObjectMetadataDao->resequence($reviewObjectMetadata->getReviewObjectTypeId());

		// Moving up or down with the arrows requires a page reload.
		// In the case of a drag and drop move, the display has been
		// updated on the client side, so no reload is necessary.
		if ($direction != null) {
			$request->redirect(null, 'editor', 'reviewObjectMetadata', array($reviewObjectMetadata->getReviewObjectTypeId()));
		}
	}

	/**
	 * Copy review object metadata to another review object.
	 */
	function copyOrUpdateReviewObjectMetadata($args, &$request) {
		$typeId = array_shift($args);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$copy = $request->getUserVar('copy');

		$reviewObjectTypeDao =& DAORegistry::getDAO('ReviewObjectTypeDAO');
		$reviewObjectType =& $reviewObjectTypeDao->getById($typeId, $journalId);
		if (isset($reviewObjectType)) {
			$reviewObjectMetadataDao =& DAORegistry::getDAO('ReviewObjectMetadataDAO');
			if ($request->getUserVar('save')) {
				$requiredMetadata = $request->getUserVar('required');
				$displayMetadata = $request->getUserVar('display');
				$allReviewObjectMetadata =& $reviewObjectMetadataDao->getArrayByReviewObjectTypeId($typeId);
				foreach ($allReviewObjectMetadata as $metadata) {
					if ($metadata->getKey() != REVIEW_OBJECT_METADATA_KEY_TITLE) {
						in_array($metadata->getId(), $requiredMetadata) ? $metadata->setRequired(1) : $metadata->setRequired(0);
						in_array($metadata->getId(), $displayMetadata) ? $metadata->setDisplay(1) : $metadata->setDisplay(0);
						$reviewObjectMetadataDao->updateObject($metadata);
					}
				}
			} elseif (is_array($copy)) {
				$targetTypeId = $request->getUserVar('targetReviewObjectTypeId');
				foreach ($copy as $metadataId) {
					$reviewObjectMetadata =& $reviewObjectMetadataDao->getById($metadataId, $typeId);
					// If metadata and the target review object type exist,
					// and it's not the default metadata (doesn't have a key)
					if (isset($reviewObjectMetadata) && ($reviewObjectMetadata->getKey() == null) && $reviewObjectTypeDao->reviewObjectTypeExists($targetTypeId, $journalId)) {
						$reviewObjectMetadata->setReviewObjectTypeId($targetTypeId);
						$reviewObjectMetadata->setSequence(REALLY_BIG_NUMBER);
						$reviewObjectMetadataDao->insertObject($reviewObjectMetadata);
						$reviewObjectMetadataDao->resequence($targetTypeId);
					}
					unset($reviewObjectMetadata);
				}
				$request->redirect(null, 'editor', 'reviewObjectMetadata', array($targetTypeId));
			}
		}
		$this->_createTrivialNotification(NOTIFICATION_TYPE_OFR_OT_UPDATED, $request);
		$request->redirect(null, 'editor', 'reviewObjectMetadata', array($typeId));
	}


	/**
	 * Ensure that we have a journal, plugin is enabled, and user is editor.
	 */
	function authorize(&$request, &$args, $roleAssignments) {
		$journal =& $request->getJournal();
		if (!isset($journal)) return false;

		$plugin =& $this->_getObjectsForReviewPlugin();

		if (!isset($plugin)) return false;

		if (!$plugin->getEnabled()) return false;

		if (!Validation::isEditor($journal->getId())) Validation::redirectLogin();;

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Setup common template variables.
	 * @param $request PKPRequest
	 * @param $subclass boolean (optional) set to true if caller is below this handler in the hierarchy
	 * @param $reviewObjectType ReviewObjectType (optional)
	 */
	function setupTemplate(&$request, $subclass = false, $reviewObjectType = null) {
		$templateMgr =& TemplateManager::getManager($request);
		$pageCrumbs = array(
			array(
				$request->url(null, 'user'),
				'navigation.user'
			),
			array(
				$request->url(null, 'editor'),
				'user.role.editor'
			)
		);

		if ($subclass) {
			$pageCrumbs[] = array(
				$request->url(null, 'editor', 'reviewObjectTypes'),
				AppLocale::Translate('plugins.generic.objectsForReview.editor.objectTypes'),
				true
			);
		}
		if ($reviewObjectType) {
			$pageCrumbs[] = array(
				$request->url(null, 'editor', 'editReviewObjectType', $reviewObjectType->getId()),
				$reviewObjectType->getLocalizedName(),
				true
			);
		}

		$templateMgr->assign('pageHierarchy', $pageCrumbs);
		$plugin =& $this->_getObjectsForReviewPlugin();
		$templateMgr->addStyleSheet($request->getBaseUrl() . '/' . $plugin->getStyleSheet());

	}

	//
	// Private helper methods
	//
	/**
	 * Get the objectForReview plugin object
	 * @return ObjectsForReviewPlugin
	 */
	function &_getObjectsForReviewPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', OBJECTS_FOR_REVIEW_PLUGIN_NAME);
		return $plugin;
	}

	/**
	 * Get plugin locales i.e. the languages the plug-in is translated into
	 * @return array of plugin locales
	 */
	function _getPluginLocales() {
		$plugin =& $this->_getObjectsForReviewPlugin();
		$pluginLocales = array();
		$allLocales =& AppLocale::getAllLocales();
		foreach ($allLocales as $locale => $localeName) {
			$localeFilename = $plugin->getPluginPath() . "/locale/$locale/locale.xml";
			if (file_exists($localeFilename)) $pluginLocales[$locale] = $localeName;
		}
		return $pluginLocales;
	}

	/**
	 * Get the missing/not installed review objects/keys
	 * @param $journalId int
	 * @return array of missing review objects keys
	 */
	function _getMissingDefaultReviewObjectsKeys($journalId) {
		$plugin =& $this->_getObjectsForReviewPlugin();
		$missingReviewObjectKeys = array();
		// Get the installed review objects/keys
		$reviewObjectTypeDao =& DAORegistry::getDAO('ReviewObjectTypeDAO');
		$installedReviewObjectKeys = $reviewObjectTypeDao->getTypeKeys($journalId);
		// Get all existing review objects
		foreach (glob($plugin->getPluginPath() . '/xml/reviewObjects/*.xml') as $filePath) {
			$objectKey = basename($filePath, '.xml');
			if (!in_array($objectKey, $installedReviewObjectKeys)) {
				$missingReviewObjectKeys[$objectKey] = $objectKey;
			}
		}
		return $missingReviewObjectKeys;
	}

	/**
	 * Update or install review objects
	 * @param $journal Journal
	 * @param $reviewObjects array of review object types keys or ids
	 * @param $locales array of locales
	 * @param $action string (install or update)
	 */
	function _updateOrInstallReviewObjectTypes($journal, $reviewObjects, $locales, $action) {
		$plugin =& $this->_getObjectsForReviewPlugin();
		if (!isset($journal) || !isset($reviewObjects) || !isset($locales) || !isset($action)) return false;
		$journalId = $journal->getId();
		$plugin->import('classes.ReviewObjectType');
		$plugin->import('classes.ReviewObjectMetadata');
		$reviewObjectTypeDao =& DAORegistry::getDAO('ReviewObjectTypeDAO');
		$reviewObjectMetadataDao =& DAORegistry::getDAO('ReviewObjectMetadataDAO');
		$onlyCommonMetadata = false;
		foreach ($reviewObjects as $keyOrId) {
			if ($action == 'install') {
				// Create a new review object type
				$reviewObjectType = $reviewObjectTypeDao->newDataObject();
				$reviewObjectType->setContextId($journalId);
				$reviewObjectType->setActive(0);
				$reviewObjectType->setKey($keyOrId);
			} elseif ($action == 'update') {
				// Get the review object type
				$reviewObjectType =& $reviewObjectTypeDao->getById($keyOrId, $journalId);
				if (!isset($reviewObjectType)) return false;
				// If the type was created by the user, update only the common metadata
				if ($reviewObjectType->getKey() == NULL) {
					$onlyCommonMetadata = true;
				}
			}

			// Callect the metadata in the array
			$reviewObjectMetadataArray = array();
			// For all languages
			foreach ($locales as $locale) {
				// Register the locale/translation file
				$localePath = $plugin->getPluginPath() . '/locale/'. $locale . '/locale.xml';
				AppLocale::registerLocaleFile($locale, $localePath, true);

				$xmlDao = new XMLDAO();
				// Get common metadata
				$commonDataPath = $plugin->getPluginPath() . '/xml/commonMetadata.xml';
				$commonData = $xmlDao->parse($commonDataPath);
				$commonMetadata = $commonData->getChildByName('objectMetadata');
				$allMetadataChildren = $commonMetadata->getChildren();

				// Get the object metadata
				if (!$onlyCommonMetadata) {
					// Parse the review object XML file
					$itemPath = $plugin->getPluginPath() . '/xml/reviewObjects/'. $reviewObjectType->getKey() . '.xml';
					$data = $xmlDao->parse($itemPath);
					if (!$data) return false;

					// Set the review object name
					$itemTypeName = __($data->getChildValue('objectType'), array(), $locale);
					$reviewObjectType->setName($itemTypeName, $locale);
					// $reviewObjectType->setDescription($itemTypeNameDescription, $locale);

					// Get the review object role selection options
					$roleSelectionOptions = $data->getChildByName('roleSelectionOptions');

					// Handle Metadata
					// Get multiple options metadata types
					$multipleOptionsTypes = ReviewObjectMetadata::getMultipleOptionsTypes();
					// Get metadata types defined in DTD
					$dtdTypes = ReviewObjectMetadata::getMetadataDTDTypes();
					// Get the review object metadata
					$itemMetadata = $data->getChildByName('objectMetadata');
					// Merge all (common + review objec) metadata
					$allMetadataChildren = array_merge($commonMetadata->getChildren(), $itemMetadata->getChildren());
				}

				// Go through the metadata
				foreach ($allMetadataChildren as $metadataNode) {
					$key = $metadataNode->getAttribute('key');

					// If we have already went througt, collected/considered the metadata
					if (array_key_exists($key, $reviewObjectMetadataArray)) {
						$reviewObjectMetadata = $reviewObjectMetadataArray[$key];
					} else {
						if ($action == 'update') {
							// Get the metadata
							$reviewObjectMetadata = $reviewObjectMetadataDao->getByKey($key, $reviewObjectType->getId());
						}
						if ($action == 'install' || !isset($reviewObjectMetadata)) {
							// Create a new metadata
							$reviewObjectMetadata = $reviewObjectMetadataDao->newDataObject();
							$reviewObjectMetadata->setSequence(REALLY_BIG_NUMBER);
							$metadataType = $dtdTypes[$metadataNode->getAttribute('type')];
							$reviewObjectMetadata->setMetadataType($metadataType);
							$required = $metadataNode->getAttribute('required');
							$reviewObjectMetadata->setRequired($required == 'true' ? 1 : 0);
							$display = $metadataNode->getAttribute('display');
							$reviewObjectMetadata->setDisplay($display == 'true' ? 1 : 0);
						}
					}
					// Set metadata name
					$name = __($metadataNode->getChildValue('name'), array(), $locale);
					$reviewObjectMetadata->setName($name, $locale);
					// Set roles options
					if ($key == REVIEW_OBJECT_METADATA_KEY_ROLE) {
						if (!$onlyCommonMetadata) {
							$possibleOptions = array();
							$index = 1;
							foreach ($roleSelectionOptions->getChildren() as $selectionOptionNode) {
								$possibleOptions[] = array('order' => $index, 'content' => __($selectionOptionNode->getValue(), array(), $locale));
								$index++;
							}
							$reviewObjectMetadata->setPossibleOptions($possibleOptions, $locale);
						}
					} else {
						// Set possible options for multiple options metadata type
						if (in_array($reviewObjectMetadata->getMetadataType(), $multipleOptionsTypes)) {
							$selectionOptions = $metadataNode->getChildByName('selectionOptions');
							$possibleOptions = array();
							$index = 1;
							foreach ($selectionOptions->getChildren() as $selectionOptionNode) {
								$possibleOptions[] = array('order' => $index, 'content' => __($selectionOptionNode->getValue(), array(), $locale));
								$index++;
							}
							$reviewObjectMetadata->setPossibleOptions($possibleOptions, $locale);
						} else {
							$reviewObjectMetadata->setPossibleOptions(null, null);
						}
					}
					// Collect/consider the metadata
					$reviewObjectMetadataArray[$key] = $reviewObjectMetadata;
					unset($reviewObjectMetadata);
				} // End foreach metadata
			} // End foreach locales

			// Insert resp. update the review object type
			if ($action == 'install') {
				$reviewObjectTypeId = $reviewObjectTypeDao->insertObject($reviewObjectType);
			} elseif ($action == 'update') {
				$reviewObjectTypeDao->updateObject($reviewObjectType);
			}
			// Insert resp. update review object metadata
			foreach ($reviewObjectMetadataArray as $key => $reviewObjectMetadata) {
				// if this is a new metadata insert it
				if ($reviewObjectMetadata->getKey() == '') {
					$reviewObjectMetadata->setKey($key);
					$reviewObjectMetadata->setReviewObjectTypeId($reviewObjectType->getId());
					$reviewObjectMetadataDao->insertObject($reviewObjectMetadata);
					$reviewObjectMetadataDao->resequence($reviewObjectType->getId());
				} else {
					$reviewObjectMetadataDao->updateObject($reviewObjectMetadata);
				}
			}
			unset($reviewObjectType);
		} // End foreach review objects
	}

	/**
	 * Create trivial notification
	 * @param $notificationType int
	 * @param $request PKPRequest
	 */
	function _createTrivialNotification($notificationType, &$request) {
		$user =& $request->getUser();
		import('classes.notification.NotificationManager');
		$notificationManager = new NotificationManager();
		$notificationManager->createTrivialNotification($user->getId(), $notificationType);
	}

}

?>
