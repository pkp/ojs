<?php

/**
 * @file plugins/generic/booksForReview/pages/BooksForReviewHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BooksForReviewHandler
 * @ingroup plugins_generic_booksForReview
 *
 * @brief Handle requests for public book for review functions. 
 */

import('classes.handler.Handler');

class BooksForReviewHandler extends Handler {

	/**
	 * Display books for review public index page.
	 */
	function index($args = array(), &$request) {
		$this->setupTemplate();

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$bfrPlugin =& PluginRegistry::getPlugin('generic', BOOKS_FOR_REVIEW_PLUGIN_NAME);

		$bfrPlugin->import('classes.BookForReview');
		$searchField = null;
		$searchMatch = null;
		$search = $request->getUserVar('search');

		if (!empty($search)) {
			$searchField = $request->getUserVar('searchField');
			$searchMatch = $request->getUserVar('searchMatch');
		}			

		$rangeInfo =& Handler::getRangeInfo('booksForReview');
		$bfrDao =& DAORegistry::getDAO('BookForReviewDAO');
		$booksForReview =& $bfrDao->getBooksForReviewByJournalId($journalId, $searchField, $search, $searchMatch, BFR_STATUS_AVAILABLE, null, null, $rangeInfo);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('booksForReview', $booksForReview);

		$isAuthor = Validation::isAuthor();
		$templateMgr->assign('isAuthor', $isAuthor);

		// Set search parameters
		$duplicateParameters = array(
			'searchField', 'searchMatch', 'search'
		);
		foreach ($duplicateParameters as $param)
			$templateMgr->assign($param, $request->getUserVar($param));

		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		$coverPagePath = $request->getBaseUrl() . '/';
		$coverPagePath .= $publicFileManager->getJournalFilesPath($journalId) . '/';
		$templateMgr->assign('coverPagePath', $coverPagePath);
		$templateMgr->assign('locale', AppLocale::getLocale());

		$fieldOptions = Array(
			BFR_FIELD_TITLE => 'plugins.generic.booksForReview.field.title',
			BFR_FIELD_PUBLISHER => 'plugins.generic.booksForReview.field.publisher',
			BFR_FIELD_YEAR => 'plugins.generic.booksForReview.field.year',
			BFR_FIELD_ISBN => 'plugins.generic.booksForReview.field.isbn',
			BFR_FIELD_DESCRIPTION => 'plugins.generic.booksForReview.field.description'
		);
		$templateMgr->assign('fieldOptions', $fieldOptions);
		
		$templateMgr->assign('additionalInformation', $bfrPlugin->getSetting($journalId, 'additionalInformation'));
		$templateMgr->display($bfrPlugin->getTemplatePath() . 'booksForReview.tpl');
	}

	/**
	 * Public view book for review details.
	 */
	function viewBookForReview($args = array(), &$request) {
		$this->setupTemplate(true);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$bfrPlugin =& PluginRegistry::getPlugin('generic', BOOKS_FOR_REVIEW_PLUGIN_NAME);

		$bookId = !isset($args) || empty($args) ? null : (int) $args[0];

		$bfrDao =& DAORegistry::getDAO('BookForReviewDAO');

		// Ensure book for review is valid and for this journal
		if ($bfrDao->getBookForReviewJournalId($bookId) == $journalId) {
			$book =& $bfrDao->getBookForReview($bookId);
			$bfrPlugin->import('classes.BookForReview');

			// Ensure book is still available
			if ($book->getStatus() == BFR_STATUS_AVAILABLE) {
				$isAuthor = Validation::isAuthor();

				import('classes.file.PublicFileManager');
				$publicFileManager = new PublicFileManager();
				$coverPagePath = $request->getBaseUrl() . '/';
				$coverPagePath .= $publicFileManager->getJournalFilesPath($journalId) . '/';

				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign('coverPagePath', $coverPagePath);
				$templateMgr->assign('locale', AppLocale::getLocale());
				$templateMgr->assign_by_ref('bookForReview', $book);
				$templateMgr->assign('isAuthor', $isAuthor);
				$templateMgr->display($bfrPlugin->getTemplatePath() . 'bookForReview.tpl');
			}
		}
		$request->redirect(null, 'booksForReview');
	}

	/**
	 * Ensure that we have a selected journal and the plugin is enabled
	 */
	function authorize(&$request, &$args, $roleAssignments) {
		$journal =& $request->getJournal();
		if (!isset($journal)) return false;

		$bfrPlugin =& PluginRegistry::getPlugin('generic', BOOKS_FOR_REVIEW_PLUGIN_NAME);

		if (!isset($bfrPlugin)) return false;
 
		if (!$bfrPlugin->getEnabled()) return false;

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		$templateMgr =& TemplateManager::getManager();

		if ($subclass) {
			$templateMgr->append(
				'pageHierarchy',
				array(
					Request::url(null, 'booksForReview'), 
					AppLocale::Translate('plugins.generic.booksForReview.displayName'),
					true
				)
			);
		}

		$bfrPlugin =& PluginRegistry::getPlugin('generic', BOOKS_FOR_REVIEW_PLUGIN_NAME);
		$templateMgr->addStyleSheet(Request::getBaseUrl() . '/' . $bfrPlugin->getStyleSheet());
	}
}

?>
