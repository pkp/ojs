<?php

/**
 * @file plugins/metadata/mods34/schema/Mods34NameSchema.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Mods34NameSchema
 * @ingroup plugins_metadata_mods34_schema
 * @see MetadataSchema
 *
 * @brief @verbatim Class that provides meta-data properties compliant with
 *  the MODS name tag from MODS Version 3.4. We only support
 *  those sub-elements we have use-cases for. We map elements and attributes
 *  from the original XML standard to 'element[@attribute="..."]' property
 *  names. @endverbatim
 *
 *  See <http://www.loc.gov/standards/mods34/mods-outline.html#name>.
 *
 *  Wherever possible we follow the "Digital Library Federation / Aquifer
 *  Implementation Guidelines for Shareable MODS Records", see
 *  <https://wiki.dlib.indiana.edu/confluence/download/attachments/24288/DLFMODS_ImplementationGuidelines.pdf>
 *
 *  The DLF/Aquifer Implementation Guidelines for Shareable MODS Records
 *  requires the use of at least one name description for the creator of the
 *  intellectual content of the resource, if available. Please use a type
 *  statement with all name descriptions for greater control and interoperability.
 *  In addition at least one namePart statement is a required of each name
 *  description.
 */


import('lib.pkp.classes.metadata.MetadataSchema');

class Mods34NameSchema extends MetadataSchema {
	/**
	 * Constructor
	 */
	function __construct() {
		// Configure the meta-data schema.
		parent::__construct(
			'mods-3.4-name',
			'mods34',
			'lib.pkp.plugins.metadata.mods34.schema.Mods34NameSchema',
			array(ASSOC_TYPE_AUTHOR, ASSOC_TYPE_EDITOR)
		);


		// The type attribute can take the following values: personal,
		// corporate and conference. This is a required attribute.
		$this->addProperty('[@type]', array(METADATA_PROPERTY_TYPE_VOCABULARY => 'mods34-name-types'), false, METADATA_PROPERTY_CARDINALITY_ONE, 'metadata.property.displayName.name-type', 'metadata.property.validationMessage.name-type', true);

		// The name itself is always wrapped in namePart elements. MODS allows for either
		// breaking up parts of the name (given and family, for example) in different namePart
		// elements or enclosing the entire name in one element. Use of the former method affords
		// more control in sorting and display which is why it is the only encoding supported
		// in our case.

		// Use the following typeless namePart version for corporate and conference names.
		$this->addProperty('namePart');

		// Use the following namePart types for personal names.
		$this->addProperty('namePart[@type="family"]');
		$this->addProperty('namePart[@type="given"]');

		// The attribute "termsOfAddress" is used to record titles and enumeration associated
		// with a name, such as Jr., II, etc.
		$this->addProperty('namePart[@type="termsOfAddress"]');

		// The attribute "date" is used to parse dates that are not integral parts of a name,
		// i.e. the lifetime of an author ("1901-1983") used to disambiguate an author name.
		// Dates that are part of a name, e.g. dates within a conference name, do not use this
		// attribute to separate the date, since it is an integral part of the name string.
		// This attribute is not used when parsing the components of a corporate name.
		$this->addProperty('namePart[@type="date"]');

		// The affiliation subelement contains the name, address, etc. of an organization with which the
		// name entity was associated when the resource was created. If the information is readily
		// available, it may be included.
		$this->addProperty('affiliation');

		// Use the role element as a wrapper element to contain coded and/or textual description
		// of the role of the named entity. Use this element primarily with personal names. Repeat role
		// for each new role. We only support coded roles which can be resolved to textual representations
		// via the controlled vocabulary "mods34-name-role-roleTerms-marcrelator". See the controlled
		// vocabulary for details of the allowed entries.
		// NB: If we want to support various roleTerm types within one role then we'll have to create
		// a separate meta-data schema for roles. We avoid this complexity for now as we don't have a
		// use case for this.
		$this->addProperty('role/roleTerm[@type="code" @authority="marcrelator"]', array(METADATA_PROPERTY_TYPE_VOCABULARY => 'mods34-name-role-roleTerms-marcrelator'), false, METADATA_PROPERTY_CARDINALITY_MANY);
	}
}
?>
