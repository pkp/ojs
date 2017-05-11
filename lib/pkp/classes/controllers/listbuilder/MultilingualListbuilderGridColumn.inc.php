<?php

/**
 * @file classes/controllers/listbuilder/MultilingualListbuilderGridColumn.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MultilingualListbuilderGridColumn
 * @ingroup controllers_listbuilder
 *
 * @brief Represents a multilingual text column within a listbuilder.
 */


import('lib.pkp.classes.controllers.listbuilder.ListbuilderGridColumn');

class MultilingualListbuilderGridColumn extends ListbuilderGridColumn {
	/**
	 * Constructor
	 */
	function __construct($listbuilder, $id = '', $title = null,
			$titleTranslated = null, $template = null, $cellProvider = null,
			$availableLocales = null, $flags = array()) {

		// Make sure this is a text input
		assert($listbuilder->getSourceType() == LISTBUILDER_SOURCE_TYPE_TEXT);

		// Provide a default set of available locales if not specified
		if (!$availableLocales) $availableLocales = AppLocale::getSupportedFormLocales();

		// Set some flags for multilingual support
		$flags['multilingual'] = true; // This is a multilingual column.
		$flags['availableLocales'] = $availableLocales; // Provide available locales

		parent::__construct($listbuilder, $id, $title, $titleTranslated, $template, $cellProvider, $flags);
	}
}

?>
