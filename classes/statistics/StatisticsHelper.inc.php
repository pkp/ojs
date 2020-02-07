<?php

/**
* @file classes/statistics/StatisticsHelper.inc.php
*
* Copyright (c) 2013-2019 Simon Fraser University
* Copyright (c) 2003-2019 John Willinsky
* Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
*
* @class StatisticsHelper
* @ingroup statistics
*
* @brief Statistics helper class.
*
*/

import('lib.pkp.classes.statistics.PKPStatisticsHelper');

class StatisticsHelper extends PKPStatisticsHelper {

	/**
	 * @see PKPStatisticsHelper::getAppColumnTitle()
	 */
	protected function getAppColumnTitle($column) {
		switch ($column) {
			case STATISTICS_DIMENSION_SUBMISSION_ID:
				return __('common.publication');
			case STATISTICS_DIMENSION_PKP_SECTION_ID:
				return __('section.section');
			case STATISTICS_DIMENSION_CONTEXT_ID:
				return __('context.context');
			default:
				assert(false);
		}
	}

	/**
	 * @see PKPStatisticsHelper::getReportObjectTypesArray()
	 */
	protected function getReportObjectTypesArray() {
		$objectTypes = parent::getReportObjectTypesArray();
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR);
		$objectTypes = $objectTypes + array(
				ASSOC_TYPE_JOURNAL => __('context.context'),
				ASSOC_TYPE_SECTION => __('section.section'),
				ASSOC_TYPE_SUBMISSION => __('common.publication'),
				ASSOC_TYPE_SUBMISSION_FILE => __('submission.galleyFiles')
		);

		return $objectTypes;
	}

}


