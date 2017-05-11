<?php

/**
 * @file classes/template/PKPTemplateResource.inc.php
 *
 * Copyright (c) 2016-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPTemplateResource
 * @ingroup template
 *
 * @brief Representation for a PKP template resource (template directory).
 */

class PKPTemplateResource {
	var $templateDir;

	/**
	 * Constructor
	 * @param $templateDir Template directory
	 */
	function __construct($templateDir) {
		$this->templateDir = $templateDir;
	}

	/**
	 * Resource function to get a template.
	 * @param $template string
	 * @param $templateSource string reference
	 * @param $smarty Smarty
	 * @return boolean
	 */
	function fetch($template, &$templateSource, $smarty) {
		$templateSource = file_get_contents($this->_getFilename($template));
		return ($templateSource !== false);
	}

	/**
	 * Get the timestamp for the specified template.
	 * @param $template string Filename
	 * @param $templateTimestamp int reference
	 * @return boolean
	 */
	function fetchTimestamp($template, &$templateTimestamp, $smarty) {
		$filename = $this->_getFilename($name);
		if (!file_exists($filename)) return false;
		$templateTimestamp = filemtime($filename);
		return true;
	}

	/**
	 * Get the complete template filename including path.
	 * @param $template Template filename.
	 * @return string
	 */
	protected function _getFilename($template) {
		return $this->templateDir . DIRECTORY_SEPARATOR . $template;
	}

	/**
	 * Get secure status
	 * @return boolean
	 */
	function getSecure() {
		return true;
	}

	/**
	 * Get trusted status
	 */
	function getTrusted() {
		// From <http://www.smarty.net/docsv2/en/plugins.resources.tpl>:
		// "This function is used for only for PHP script components
		// requested by {include_php} tag or {insert} tag with the src
		// attribute. However, it should still be defined even for
		// template resources."
		// a.k.a. OK not to implement.
	}
}

?>
