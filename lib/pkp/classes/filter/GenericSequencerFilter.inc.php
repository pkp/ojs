<?php
/**
 * @file classes/filter/GenericSequencerFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GenericSequencerFilter
 * @ingroup filter
 *
 * @brief A generic filter that is configured with a number of
 *  ordered filters. It takes the input argument of the first filter,
 *  passes its output to the next filter and so on and finally returns
 *  the result of the last filter in the chain to the caller.
 */

import('lib.pkp.classes.filter.CompositeFilter');

class GenericSequencerFilter extends CompositeFilter {
	/**
	 * Constructor
	 */
	function __construct(&$filterGroup, $displayName = null) {
		parent::__construct($filterGroup, $displayName);
	}


	//
	// Implementing abstract template methods from PersistableFilter
	//
	/**
	 * @see PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'lib.pkp.classes.filter.GenericSequencerFilter';
	}


	//
	// Implementing abstract template methods from Filter
	//
	/**
	 * @see Filter::process()
	 * @param $input mixed
	 * @return mixed
	 */
	function &process(&$input) {
		// Iterate over all filters and always feed the
		// output of one filter as input to the next
		// filter.
		$previousOutput = null;
		foreach($this->getFilters() as $filter) {
			if(is_null($previousOutput)) {
				// First filter
				$previousOutput =& $input;
			}
			$output = $filter->execute($previousOutput);

			// Propagate errors of sub-filters (if any)
			foreach($filter->getErrors() as $errorMessage) $this->addError($errorMessage);

			// If one filter returns null then we'll abort
			// execution of the filter chain.
			if (is_null($output)) break;

			unset($previousOutput);
			$previousOutput = $output;
		}
		return $output;
	}
}
?>
