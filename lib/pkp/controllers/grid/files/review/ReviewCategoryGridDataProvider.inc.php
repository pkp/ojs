<?php
/**
 * @file controllers/grid/files/review/ReviewCategoryGridDataProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewGridCategoryDataProvider
 * @ingroup controllers_grid_files_review
 *
 * @brief Provide access to review file data for category grids.
 */


import('lib.pkp.controllers.grid.files.SubmissionFilesCategoryGridDataProvider');

class ReviewCategoryGridDataProvider extends SubmissionFilesCategoryGridDataProvider {

	/**
	 * Constructor
	 * @param $fileStage int
	 * @param $viewableOnly int Will be passed to the review grid data provider.
	 * See parameter description there.
	 */
	function __construct($fileStage, $viewableOnly = false) {
		parent::__construct($fileStage, array('viewableOnly' => $viewableOnly));
	}


	//
	// Getters and setters.
	//
	/**
	 * @return ReviewRound
	 */
	function getReviewRound() {
		$gridDataProvider = $this->getDataProvider();
		return $gridDataProvider->getReviewRound();
	}


	//
	// Overriden public methods from SubmissionFilesCategoryGridDataProvider
	//
	/**
	 * @copydoc SubmissionFilesCategoryGridDataProvider::loadCategoryData()
	 */
	function loadCategoryData($request, $categoryDataElement, $filter = null) {
		$reviewRound = $this->getReviewRound();
		return parent::loadCategoryData($request, $categoryDataElement, $filter, $reviewRound);
	}

	/**
	 * @copydoc SubmissionFilesCategoryGridDataProvider::initGridDataProvider()
	 */
	function initGridDataProvider($fileStage, $initParams = null) {
		// This category grid data provider will use almost all the
		// same implementation of the ReviewGridDataProvider.
		import('lib.pkp.controllers.grid.files.review.ReviewGridDataProvider');
		$reviewFilesGridDataProvider = new ReviewGridDataProvider($fileStage);
		$reviewFilesGridDataProvider->setViewableOnly($initParams['viewableOnly']);

		return $reviewFilesGridDataProvider;
	}


	//
	// Public methods
	//
	/**
	 * @copydoc ReviewGridDataProvider::getSelectAction()
	 */
	function getSelectAction($request) {
		$gridDataProvider = $this->getDataProvider();
		return $gridDataProvider->getSelectAction($request);
	}
}

?>
