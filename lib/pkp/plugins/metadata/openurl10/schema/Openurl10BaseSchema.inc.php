<?php

/**
 * @file plugins/metadata/openurl10/schema/Openurl10BaseSchema.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Openurl10BaseSchema
 * @ingroup plugins_metadata_openurl10_schema
 * @see MetadataSchema
 *
 * @brief Class that provides meta-data properties common to all
 *  variants of the OpenURL 1.0 standard.
 */


import('lib.pkp.classes.metadata.MetadataSchema');

class Openurl10BaseSchema extends MetadataSchema {
	/**
	 * Constructor
	 * @param $name string the meta-data schema name
	 */
	function __construct($name, $classname) {
		// Configure the meta-data schema.
		parent::__construct(
			$name,
			'openurl10',
			$classname,
			ASSOC_TYPE_CITATION
		);

		// Add meta-data properties common to all OpenURL standards
		$this->addProperty('aulast');
		$this->addProperty('aufirst');
		$this->addProperty('auinit');   // First author's first and middle initials
		$this->addProperty('auinit1');  // First author's first initial
		$this->addProperty('auinitm');  // First author's middle initial
		$this->addProperty('ausuffix'); // e.g.: "Jr", "III", etc.
		$this->addProperty('au', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('title');    // Deprecated in book/journal 1.0, prefer jtitle/btitle, ok for dissertation
		$this->addProperty('date', METADATA_PROPERTY_TYPE_DATE); // Publication date
		$this->addProperty('isbn');
	}
}
?>
