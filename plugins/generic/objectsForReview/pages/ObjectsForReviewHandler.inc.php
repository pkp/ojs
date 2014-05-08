<?php

/**
 * @file plugins/generic/objectsForReview/pages/ObjectsForReviewHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ObjectsForReviewHandler
 * @ingroup plugins_generic_objectsForReview
 *
 * @brief Handle requests for public object for review functions.
 */

import('classes.handler.Handler');

class ObjectsForReviewHandler extends Handler {

	/**
	 * Display objects for review public index page.
	 */
	function index($args, &$request) {
		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		// Search
		$searchParameters = array(
			'searchField', 'searchMatch', 'search'
		);
		$searchFieldOptions = Array(
			OFR_FIELD_TITLE => 'plugins.generic.objectsForReview.search.field.title',
			OFR_FIELD_ABSTRACT => 'plugins.generic.objectsForReview.search.field.abstract'
		);
		$searchField = null;
		$searchMatch = null;
		$search = $request->getUserVar('search');
		if (!empty($search)) {
			$searchField = $request->getUserVar('searchField');
			$searchMatch = $request->getUserVar('searchMatch');
		}

		// Filter by review object type
		$reviewObjectTypeDao =& DAORegistry::getDAO('ReviewObjectTypeDAO');
		$allTypes =& $reviewObjectTypeDao->getTypeIdsAlphabetizedByContext($journalId);
		$typeOptions = array(0 => __('common.all'));
		$reviewObjectMetadataDao =& DAORegistry::getDAO('ReviewObjectMetadataDAO');
		$allReviewObjectsMetadata = array();
		foreach ($allTypes as $type) {
			$typeId = $type['typeId'];
			$typeOptions[$typeId] = $type['typeName'];
			$typeMetadata = $reviewObjectMetadataDao->getArrayByReviewObjectTypeId($typeId);
			$allReviewObjectsMetadata[$typeId] = $typeMetadata;
		}
		$filterType = $request->getUserVar('filterType');

		// Sort
		$sortingOptions = array(
			'title' => __('plugins.generic.objectsForReview.objectsForReview.title'),
			'created' => __('plugins.generic.objectsForReview.objectsForReview.dateCreated')
		);
		$sort = $request->getUserVar('sort');
		$sort = isset($sort) ? $sort : 'title';
		$sortDirections = array(
			SORT_DIRECTION_ASC => __('plugins.generic.objectsForReview.sort.sortDirectionAsc'),
			SORT_DIRECTION_DESC => __('plugins.generic.objectsForReview.sort.sortDirectionDesc')
		);
		$sortDirection = $request->getUserVar('sortDirection');
		$sortDirection = isset($sortDirection) ? $sortDirection : SORT_DIRECTION_ASC;

		// Get objects for review
		$rangeInfo =& Handler::getRangeInfo('objectsForReview');
		$ofrDao =& DAORegistry::getDAO('ObjectForReviewDAO');
		$objectsForReview =& $ofrDao->getAllByContextId($journalId, $searchField, $search, $searchMatch, 1, null, $filterType, $rangeInfo, $sort, $sortDirection);

		// If the user is an author get her/his assignments
		$isAuthor = Validation::isAuthor();
		if ($isAuthor) {
			$user =& $request->getUser();
			$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
			$authorAssignments = $ofrAssignmentDao->getObjectIds($user->getId());
		}

		$this->setupTemplate($request);
		$templateMgr =& TemplateManager::getManager($request);
		foreach ($searchParameters as $param)
			$templateMgr->assign($param, $request->getUserVar($param));
		$templateMgr->assign('searchFieldOptions', $searchFieldOptions);

		$templateMgr->assign('typeOptions', $typeOptions);
		$templateMgr->assign('filterType', $filterType);

		$templateMgr->assign('sortingOptions', $sortingOptions);
		$templateMgr->assign('sort', $sort);
		$templateMgr->assign('sortDirections', $sortDirections);
		$templateMgr->assign('sortDirection', $sortDirection);

		$templateMgr->assign('objectsForReview', $objectsForReview);
		$templateMgr->assign('allReviewObjectsMetadata', $allReviewObjectsMetadata);

		$templateMgr->assign('isAuthor', $isAuthor);
		$templateMgr->assign('authorAssignments', $authorAssignments);

		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		$coverPagePath = $request->getBaseUrl() . '/';
		$coverPagePath .= $publicFileManager->getJournalFilesPath($journalId) . '/';
		$templateMgr->assign('coverPagePath', $coverPagePath);

		$ofrPlugin =& $this->_getObjectsForReviewPlugin();
		$ofrPlugin->import('classes.ReviewObjectMetadata');
		$templateMgr->assign('multipleOptionsTypes', ReviewObjectMetadata::getMultipleOptionsTypes());
		$templateMgr->assign('additionalInformation', $ofrPlugin->getSetting($journalId, 'additionalInformation'));
		$templateMgr->assign('ofrListing', true);
		$templateMgr->display($ofrPlugin->getTemplatePath() . 'objectsForReview.tpl');
	}

	/**
	 * Public view object for review details.
	 */
	function viewObjectForReview($args, &$request) {
		// Ensure the args (object ID) exists
		$objectId = array_shift($args);
		if (!$objectId) {
			$request->redirect(null, 'objectsForReview');
		}

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		// Ensure the object exists
		$ofrDao =& DAORegistry::getDAO('ObjectForReviewDAO');
		$objectForReview =& $ofrDao->getById($objectId, $journalId);
		if (!isset($objectForReview)) {
			$request->redirect(null, 'objectsForReview');
		}
		// If object is available
		if ($objectForReview->getAvailable()) {
			// Get all metadata for the objects for review
			$reviewObjectTypeDao =& DAORegistry::getDAO('ReviewObjectTypeDAO');
			$allTypes =& $reviewObjectTypeDao->getTypeIdsAlphabetizedByContext($journalId);
			$reviewObjectMetadataDao =& DAORegistry::getDAO('ReviewObjectMetadataDAO');
			$allReviewObjectsMetadata = array();
			foreach ($allTypes as $type) {
				$typeId = $type['typeId'];
				$typeMetadata = $reviewObjectMetadataDao->getArrayByReviewObjectTypeId($typeId);
				$allReviewObjectsMetadata[$typeId] = $typeMetadata;
			}

			// If the user is an author get her/his assignments
			$isAuthor = Validation::isAuthor();
			if ($isAuthor) {
				$user =& $request->getUser();
				$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
				$authorAssignments = $ofrAssignmentDao->getObjectIds($user->getId());
			}

			$this->setupTemplate($request, true);
			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->assign('objectForReview', $objectForReview);
			$templateMgr->assign('allReviewObjectsMetadata', $allReviewObjectsMetadata);

			$templateMgr->assign('isAuthor', $isAuthor);
			$templateMgr->assign('authorAssignments', $authorAssignments);

			// Cover page path
			import('classes.file.PublicFileManager');
			$publicFileManager = new PublicFileManager();
			$coverPagePath = $request->getBaseUrl() . '/';
			$coverPagePath .= $publicFileManager->getJournalFilesPath($journalId) . '/';
			$templateMgr->assign('coverPagePath', $coverPagePath);

			$ofrPlugin =& $this->_getObjectsForReviewPlugin();
			$ofrPlugin->import('classes.ReviewObjectMetadata');
			$templateMgr->assign('multipleOptionsTypes', ReviewObjectMetadata::getMultipleOptionsTypes());
			$templateMgr->assign('locale', AppLocale::getLocale());
			$templateMgr->assign('ofrListing', false);
			$templateMgr->display($ofrPlugin->getTemplatePath() . 'objectForReview.tpl');

		} else {
			$request->redirect(null, 'objectsForReview');
		}
	}

	/**
	 * Ensure that we have a selected journal, the plugin is enabled and in full mode
	 */
	function authorize(&$request, &$args, $roleAssignments) {
		$journal =& $request->getJournal();
		if (!isset($journal)) return false;

		$ofrPlugin =& $this->_getObjectsForReviewPlugin();

		if (!isset($ofrPlugin)) return false;

		if (!$ofrPlugin->getEnabled()) return false;

		$mode = $ofrPlugin->getSetting($journal->getId(), 'mode');
		if ($mode != OFR_MODE_FULL) return false;

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Setup common template variables.
	 * @param $request PKPRequest
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate(&$request, $subclass = false) {
		$templateMgr =& TemplateManager::getManager($request);
		if ($subclass) {
			$templateMgr->append(
				'pageHierarchy',
				array(
					$request->url(null, 'objectsForReview'),
					AppLocale::Translate('plugins.generic.objectsForReview.displayName'),
					true
				)
			);
		}
		$ofrPlugin =& $this->_getObjectsForReviewPlugin();
		$templateMgr->addStyleSheet($request->getBaseUrl() . '/' . $ofrPlugin->getStyleSheet());
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
}

?>
