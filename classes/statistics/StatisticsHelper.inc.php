<?php

/**
* @file classes/statistics/StatisticsHelper.inc.php
*
* Copyright (c) 2013-2020 Simon Fraser University
* Copyright (c) 2003-2020 John Willinsky
* Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
*
* @class StatisticsHelper
* @ingroup statistics
*
* @brief Statistics helper class.
*
*/

import('lib.pkp.classes.statistics.PKPStatisticsHelper');

define('STATISTICS_DIMENSION_ISSUE_ID', STATISTICS_DIMENSION_ASSOC_OBJECT_ID);

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
	 * @see PKPStatisticsHelper::getReportColumnsArray()
	 */
	protected function getReportColumnsArray() {
		return array_merge(
			parent::getReportColumnsArray(),
			array(STATISTICS_DIMENSION_ISSUE_ID => __('issue.issue'))
		);
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
				ASSOC_TYPE_ISSUE => __('issue.issue'),
				ASSOC_TYPE_ISSUE_GALLEY => __('editor.issues.galley'),
				ASSOC_TYPE_SUBMISSION => __('common.publication'),
				ASSOC_TYPE_SUBMISSION_FILE => __('submission.galleyFiles')
		);

		return $objectTypes;
	}

}


