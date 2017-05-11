<?php

/**
 * @file classes/controllers/grid/feature/OrderCategoryGridItemsFeature.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OrderCategoryGridItemsFeature
 * @ingroup controllers_grid_feature
 *
 * @brief Implements category grid ordering functionality.
 *
 */

import('lib.pkp.classes.controllers.grid.feature.OrderItemsFeature');

define_exposed('ORDER_CATEGORY_GRID_CATEGORIES_ONLY', 0x01);
define_exposed('ORDER_CATEGORY_GRID_CATEGORIES_ROWS_ONLY', 0x02);
define_exposed('ORDER_CATEGORY_GRID_CATEGORIES_AND_ROWS', 0x03);

class OrderCategoryGridItemsFeature extends OrderItemsFeature {

	/**
	 * Constructor.
	 * @param $typeOption int Defines which grid elements will
	 * be orderable (categories and/or rows).
	 * @param $overrideRowTemplate boolean This feature uses row
	 * actions and it will force the usage of the gridRow.tpl.
	 * If you want to use a different grid row template file, set this flag to
	 * false and make sure to use a template file that adds row actions.
	 */
	function __construct($typeOption = ORDER_CATEGORY_GRID_CATEGORIES_AND_ROWS, $overrideRowTemplate = true) {
		parent::__construct($overrideRowTemplate);

		$this->addOptions(array('type' => $typeOption));
	}


	//
	// Getters and setters.
	//
	/**
	 * Return this feature type.
	 * @return int One of the ORDER_CATEGORY_GRID_... constants
	 */
	function getType() {
		$options = $this->getOptions();
		return $options['type'];
	}


	//
	// Extended methods from GridFeature.
	//
	/**
	 * @see GridFeature::getJSClass()
	 */
	function getJSClass() {
		return '$.pkp.classes.features.OrderCategoryGridItemsFeature';
	}


	//
	// Hooks implementation.
	//
	/**
	 * @see OrderItemsFeature::getInitializedRowInstance()
	 */
	function getInitializedRowInstance($args) {
		if ($this->getType() != ORDER_CATEGORY_GRID_CATEGORIES_ONLY) {
			parent::getInitializedRowInstance($args);
		}
	}

	/**
	 * @see GridFeature::getInitializedCategoryRowInstance()
	 */
	function getInitializedCategoryRowInstance($args) {
		if ($this->getType() != ORDER_CATEGORY_GRID_CATEGORIES_ROWS_ONLY) {
			$row =& $args['row'];
			$this->addRowOrderAction($row);
		}
	}

	/**
	 * @see GridFeature::saveSequence()
	 */
	function saveSequence($args) {
		$request =& $args['request'];
		$grid =& $args['grid'];

		$data = json_decode($request->getUserVar('data'));
		$gridCategoryElements = $grid->getGridDataElements($request);

		if ($this->getType() != ORDER_CATEGORY_GRID_CATEGORIES_ROWS_ONLY) {
			$categoriesData = array();
			foreach($data as $categoryData) {
				$categoriesData[] = $categoryData->categoryId;
			}

			// Save categories sequence.
			$firstSeqValue = $grid->getDataElementSequence(reset($gridCategoryElements));
			foreach ($gridCategoryElements as $rowId => $element) {
				$rowPosition = array_search($rowId, $categoriesData);
				$newSequence = $firstSeqValue + $rowPosition;
				$currentSequence = $grid->getDataElementSequence($element);
				if ($newSequence != $currentSequence) {
					$grid->setDataElementSequence($request, $rowId, $element, $newSequence);
				}
			}
		}

		// Save rows sequence, if this grid has also orderable rows inside each category.
		$this->_saveRowsInCategoriesSequence($request, $grid, $gridCategoryElements, $data);
	}


	//
	// Private helper methods.
	//
	/**
	 * Save row elements sequence inside categories.
	 * @param $request PKPRequest
	 * @param $grid GridHandler
	 * @param $gridCategoryElements array
	 * @param $data
	 */
	function _saveRowsInCategoriesSequence($request, &$grid, $gridCategoryElements, $data) {
		if ($this->getType() != ORDER_CATEGORY_GRID_CATEGORIES_ONLY) {
			foreach($gridCategoryElements as $categoryId => $element) {
				$gridRowElements = $grid->getGridCategoryDataElements($request, $element);
				if (!$gridRowElements) continue;

				// Get the correct rows sequence data.
				$rowsData = null;
				foreach ($data as $categoryData) {
					if ($categoryData->categoryId == $categoryId) {
						$rowsData = $categoryData->rowsId;
						break;
					}
				}

				unset($rowsData[0]); // remove the first element, it is always the parent category ID
				$firstSeqValue = $grid->getDataElementInCategorySequence($categoryId, reset($gridRowElements));
				foreach ($gridRowElements as $rowId => $element) {
					$newSequence = array_search($rowId, $rowsData);
					$currentSequence = $grid->getDataElementInCategorySequence($categoryId, $element);
					if ($newSequence != $currentSequence) {
						$grid->setDataElementInCategorySequence($categoryId, $element, $newSequence);
					}
				}
			}
		}
	}
}

?>
