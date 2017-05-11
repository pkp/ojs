<?php

/**
 * @file plugins/metadata/openurl10/schema/Openurl10BookSchema.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Openurl10BookSchema
 * @ingroup plugins_metadata_openurl10_schema
 * @see Openurl10JournalBookBaseSchema
 *
 * @brief Class that provides meta-data properties of the
 *  OpenURL 1.0 book standard.
 */


import('lib.pkp.plugins.metadata.openurl10.schema.Openurl10JournalBookBaseSchema');

define('OPENURL10_GENRE_BOOK', 'book');
define('OPENURL10_GENRE_BOOKITEM', 'bookitem');
define('OPENURL10_GENRE_REPORT', 'report');
define('OPENURL10_GENRE_DOCUMENT', 'document');

class Openurl10BookSchema extends Openurl10JournalBookBaseSchema {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct(
			'openurl-1.0-book',
			'lib.pkp.plugins.metadata.openurl10.schema.Openurl10BookSchema'
		);

		// Add meta-data properties that only appear in the OpenURL book standard
		$this->addProperty('btitle');
		$this->addProperty('place'); // Place of publication
		$this->addProperty('pub');   // Publisher
		$this->addProperty('edition');
		$this->addProperty('tpages');
		$this->addProperty('series'); // The title of a series in which the book or document was issued.
		$this->addProperty('bici');
		$this->addProperty('genre', array(METADATA_PROPERTY_TYPE_VOCABULARY => 'openurl10-book-genres'));
	}
}
?>
