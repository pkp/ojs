<?php

/**
 * @file classes/metadata/DateStringNormalizerFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DateStringNormalizerFilter
 * @ingroup metadata
 *
 * @brief Filter that normalizes a date string to
 *  YYYY[-MM[-DD]].
 */

import('lib.pkp.classes.filter.Filter');
import('lib.pkp.classes.validation.ValidatorDate');

class DateStringNormalizerFilter extends Filter {
	/**
	 * Constructor
	 */
	function __construct() {
		$this->setDisplayName('Date String Normalizer');

		parent::__construct('primitive::string', 'validator::date('.DATE_FORMAT_ISO.')');
	}


	//
	// Implement abstract methods from Filter
	//
	/**
	 * Normalize incoming date string.
	 * @see Filter::process()
	 * @param $input string
	 * @return string
	 */
	function &process(&$input) {
		// FIXME: We have to i18nize this when expanding citation parsing to other languages
		static $monthNames = array(
			'Jan' => '01', 'Feb' => '02', 'Mar' => '03', 'Apr' => '04', 'May' => '05', 'Jun' => '06',
			'Jul' => '07', 'Aug' => '08', 'Sep' => '09', 'Oct' => '10', 'Nov' => '11', 'Dec' => '12'
		);

		$dateExpressions = array(
			'/(?P<year>\d{4})-(?P<month>\d{2})-(?P<day>\d{2})/',
			'/(?P<year>\d{4})(\s|-)*(?P<monthName>[a-z]\w+)?(\s|-)*(?P<day>\d+)?/i'
		);
		$normalizedDate = null;
		foreach($dateExpressions as $dateExpression) {
			if (PKPString::regexp_match_get($dateExpression, $input, $parsedDate) ){
				if (isset($parsedDate['year'])) {
					$normalizedDate = $parsedDate['year'];

					$month = '';
					if (isset($parsedDate['monthName'])) {
						$monthName = substr($parsedDate['monthName'], 0, 3);
						if (isset($monthNames[$monthName])) {
							// Convert the month name to a two digit numeric month representation.
							$month = $monthNames[$monthName];
						}
					}

					if (isset($parsedDate['month'])) {
						// Convert month to a two digit representation.
						$month = str_pad($parsedDate['month'], 2, '0', STR_PAD_LEFT);
					}

					if (!empty($month)) {
						$normalizedDate .= '-'.$month;
						if (isset($parsedDate['day'])) $normalizedDate .= '-'.str_pad($parsedDate['day'], 2, '0', STR_PAD_LEFT);
					}
				}
				if (!empty($normalizedDate)) break;
			}
		}

		return $normalizedDate;
	}
}
?>
