<?php

/**
 * @file plugins/metadata/nlm30/schema/Nlm30NameSchema.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Nlm30NameSchema
 * @ingroup plugins_metadata_nlm30_schema
 * @see MetadataSchema
 *
 * @brief Class that provides meta-data properties compliant with
 *  the NLM name tag from the NLM Journal Publishing Tag Set
 *  Version 3.0. Records of this type will be used as composite property
 *  within the person group properties.
 *
 * NB: The given-names tag has a cardinality "many" in our schema which
 * is a deviation from the original NLM standard. This deviation is necessary
 * to ensure full "roundtripability" to/from OpenURL as required by our
 * specification. We'll have to provide special handling for this when
 * exporting to NLM XML.
 */


import('lib.pkp.classes.metadata.MetadataSchema');

class Nlm30NameSchema extends MetadataSchema {
	/**
	 * Constructor
	 */
	function __construct() {
		// Configure the meta-data schema.
		parent::__construct(
			'nlm-3.0-name',
			'nlm30',
			'lib.pkp.plugins.metadata.nlm30.schema.Nlm30NameSchema',
			array(ASSOC_TYPE_AUTHOR, ASSOC_TYPE_EDITOR)
		);

		// This schema is used for persons (authors, editors, ...)
		$this->addProperty('surname');
		// The following is a deviation from original NLM schema.
		// See classdoc for further info.
		$this->addProperty('given-names', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('prefix');
		$this->addProperty('suffix');
	}
}
?>
