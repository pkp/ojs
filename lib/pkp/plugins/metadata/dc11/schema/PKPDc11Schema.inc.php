<?php
/**
 * @defgroup plugins_metadata_dc11_schema Dublin Core 1.1 Metadata Format Schema
 */

/**
 * @file plugins/metadata/dc11/schema/PKPDc11Schema.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPDc11Schema
 * @ingroup plugins_metadata_dc11_schema
 * @see MetadataSchema
 *
 * @brief Class that provides meta-data properties compliant with
 *  the Dublin Core specification, version 1.1.
 *
 *  For details see <http://dublincore.org/documents/dces/>,
 */

import('lib.pkp.classes.metadata.MetadataSchema');

class PKPDc11Schema extends MetadataSchema {
	/**
	 * Constructor
	 * @param $appSpecificAssocType integer
	 */
	function __construct($appSpecificAssocType, $classname = 'plugins.metadata.dc11.schema.Dc11Schema') {
		// Configure the meta-data schema.
		parent::__construct(
			'dc-1.1',
			'dc',
			$classname,
			$appSpecificAssocType
		);

		$this->addProperty('dc:title', METADATA_PROPERTY_TYPE_STRING, true, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('dc:creator', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('dc:subject', METADATA_PROPERTY_TYPE_STRING, true, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('dc:description', METADATA_PROPERTY_TYPE_STRING, true, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('dc:publisher', METADATA_PROPERTY_TYPE_STRING, true, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('dc:contributor', METADATA_PROPERTY_TYPE_STRING, true, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('dc:date', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('dc:type', METADATA_PROPERTY_TYPE_STRING, true, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('dc:format', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('dc:identifier', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('dc:source', METADATA_PROPERTY_TYPE_STRING, true, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('dc:language', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('dc:relation', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('dc:coverage', METADATA_PROPERTY_TYPE_STRING, true, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('dc:rights', METADATA_PROPERTY_TYPE_STRING, true, METADATA_PROPERTY_CARDINALITY_MANY);
	}
}
?>
