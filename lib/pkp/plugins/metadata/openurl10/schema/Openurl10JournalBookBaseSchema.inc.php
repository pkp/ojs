<?php

/**
 * @file plugins/metadata/openurl10/schema/Openurl10JournalBookBaseSchema.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Openurl10JournalBookBaseSchema
 * @ingroup plugins_metadata_openurl10_schema
 * @see Openurl10BaseSchema
 *
 * @brief Class that provides meta-data properties common to the
 *  journal and book variants of the OpenURL 1.0 standard.
 */


import('lib.pkp.plugins.metadata.openurl10.schema.Openurl10BaseSchema');

define('OPENURL10_GENRE_CONFERENCE', 'conference');
define('OPENURL10_GENRE_PROCEEDING', 'proceeding');
define('OPENURL10_GENRE_UNKNOWN', 'unknown');

class Openurl10JournalBookBaseSchema extends Openurl10BaseSchema {
	/**
	 * Constructor
	 * @param $name string the meta-data schema name
	 */
	function __construct($name, $classname) {
		parent::__construct($name, $classname);

		// Add meta-data properties common to the OpenURL book/journal standard
		$this->addProperty('aucorp');   // Organization or corporation that is the author or creator
		$this->addProperty('atitle');
		$this->addProperty('spage', METADATA_PROPERTY_TYPE_INTEGER);
		$this->addProperty('epage', METADATA_PROPERTY_TYPE_INTEGER);
		$this->addProperty('pages');
		$this->addProperty('issn');
	}
}
?>
