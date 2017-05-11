<?php

/**
 * @file classes/metadata/CrosswalkFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CrosswalkFilter
 * @ingroup metadata
 * @see MetadataDescription
 *
 * @brief Class that provides methods to convert one type of
 *  meta-data description into another. This is an abstract
 *  class that must be sub-classed by specific cross-walk
 *  implementations.
 */

import('lib.pkp.classes.filter.Filter');

class CrosswalkFilter extends Filter {
	/**
	 * Constructor
	 * @param $fromSchema string fully qualified class name of supported input meta-data schema
	 * @param $toSchema string fully qualified class name of supported output meta-data schema
	 */
	function __construct($fromSchema, $toSchema) {
		parent::__construct('metadata::'.$fromSchema.'(*)', 'metadata::'.$toSchema.'(*)');
	}
}
?>
