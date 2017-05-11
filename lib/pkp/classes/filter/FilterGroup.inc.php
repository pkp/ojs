<?php
/**
 * @file classes/filter/FilterGroup.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FilterGroup
 * @ingroup filter
 *
 * @see PersistableFilter
 *
 * @brief Class that represents filter groups.
 *
 * A filter group is a category of filters that all accept the exact same input
 * and output type and execute semantically very similar tasks (e.g. all citation
 * parsers or all citation output filters).
 *
 * Distinct filter groups can have define the same input and output types if they
 * do semantically different things (e.g. two XSL operations that both take
 * XML as input and output but do different things).
 *
 * A transformation can only be part of exactly one filter group. If you find that
 * you want to add the same transformation (same input/output type and same
 * parameterization) to two different filter groups then this indicates that the
 * semantics of the two groups has been defined ambivalently.
 *
 * The rules for defining filter groups are like this:
 * 1) Describe what the transformation does and not in which context the transformation
 *    is being used (e.g. "NLM-3.0 citation-element to plaintext citation output conversion"
 *    rather than "Reading tool citation filter").
 * 2) Make sure that the name is really unique with respect to input type, output type
 *    and potential parameterizations of filters in the group. Otherwise you can expect
 *    to get name clashes later (e.g. use "NLM-3.0 ... conversion" and not "NLM ... conversion"
 *    otherwise you'll get a name clash when NLM 4.0 or 3.1 comes out.
 *
 * It can be difficult to change filter group names later as we expect community
 * contributions to certain filter groups (e.g. citation parsers).
 */


import('lib.pkp.classes.core.DataObject');

class FilterGroup extends DataObject {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Setters and Getters
	//
	/**
	 * Set the symbolic name
	 * @param $symbolic string
	 */
	function setSymbolic($symbolic) {
		$this->setData('symbolic', $symbolic);
	}

	/**
	 * Get the symbolic name
	 * @return string
	 */
	function getSymbolic() {
		return $this->getData('symbolic');
	}

	/**
	 * Set the display name
	 * @param $displayName string
	 */
	function setDisplayName($displayName) {
		$this->setData('displayName', $displayName);
	}

	/**
	 * Get the display name
	 * @return string
	 */
	function getDisplayName() {
		return $this->getData('displayName');
	}

	/**
	 * Set the description
	 * @param $description string
	 */
	function setDescription($description) {
		$this->setData('description', $description);
	}

	/**
	 * Get the description
	 * @return string
	 */
	function getDescription() {
		return $this->getData('description');
	}

	/**
	 * Set the input type
	 * @param $inputType string a string representation of a TypeDescription
	 */
	function setInputType($inputType) {
		$this->setData('inputType', $inputType);
	}

	/**
	 * Get the input type
	 * @return string a string representation of a TypeDescription
	 */
	function getInputType() {
		return $this->getData('inputType');
	}

	/**
	 * Set the output type
	 * @param $outputType string a string representation of a TypeDescription
	 */
	function setOutputType($outputType) {
		$this->setData('outputType', $outputType);
	}

	/**
	 * Get the output type
	 * @return string a string representation of a TypeDescription
	 */
	function getOutputType() {
		return $this->getData('outputType');
	}

}
?>
