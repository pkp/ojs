<?php
/**
 * @defgroup plugins_metadata_mods34_schema MODS 3.4 Schema
 */

/**
 * @file plugins/metadata/mods34/schema/PKPMods34Schema.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPMods34Schema
 * @ingroup plugins_metadata_mods34_schema
 * @see MetadataSchema
 *
 * @brief @verbatim Class that provides meta-data properties compliant with
 *  a subset of MODS Version 3.4. We only support those sub-elements
 *  we have use-cases (and data) for. We map elements and attributes
 *  from the original XML standard to 'element/subelement[@attribute="..."]'
 *  property names. @endverbatim
 *
 *  MODS allows most elements, especially top-level elements to be repeated.
 *  We do not implement that full flexibility as we only require repeated
 *  elements for translation. This allows us to avoid the considerable extra
 *  overhead of handling composite elements in most cases. We essentially
 *  translate MODS into a flat key-value list wherever possible. The most
 *  notable exception to this rule is the name element which is implemented
 *  as a composite. Additional composites can be introduced later if required.
 *
 *  See <http://www.loc.gov/standards/mods34/mods-outline.html>.
 *
 *  Wherever possible we follow the "Digital Library Federation / Aquifer
 *  Implementation Guidelines for Shareable MODS Records", see
 *  <https://wiki.dlib.indiana.edu/confluence/download/attachments/24288/DLFMODS_ImplementationGuidelines.pdf>
 *
 *  NB: This class is an application agnostic base class to be extended
 *  by application specific versions of the schema configuring this class
 *  via constructor arguments.
 */


import('lib.pkp.classes.metadata.MetadataSchema');

class PKPMods34Schema extends MetadataSchema {
	/**
	 * Constructor
	 * @param $appSpecificAssocType integer
	 * @param $useAuthoritiesForSubject boolean whether the subject is
	 *  free text or controlled by vocabularies.
	 */
	function __construct($appSpecificAssocType = null, $useAuthoritiesForSubject = false) {
		// Configure the meta-data schema.
		$assocTypes = array(ASSOC_TYPE_CITATION);
		if (!is_null($appSpecificAssocType)) array_push($assocTypes, $appSpecificAssocType);
		parent::__construct(
			'mods-3.4',
			'mods34',
			'plugins.metadata.mods34.schema.Mods34Schema',
			$assocTypes
		);


		//
		// titleInfo
		//

		// A word, phrase, character, or group of characters, normally appearing in a resource, that
		// names it or the work contained in it.
		//
		// Titles are an extremely important access point for digital library
		// resources, and are frequently used in brief record displays to assist
		// end users in deciding whether to investigate a resource further. As
		// such, at least one titleInfo description with at least one title
		// statement is required. Additional titleInfo descriptions should be used to
		// indicate other titles for the resource. Do not include punctuation
		// intended to delineate parts of titles separated into various statements
		// within the titleInfo description.

		// We do not implement titleInfo[@type] as we do not allow repetition of the
		// titleInfo element. We assume that our titles are always primary titles of
		// the resource.

		// The nonSort element contains characters, including initial articles, punctuation, and
		// spaces that appear at the beginning of a title that should be ignored for indexing of titles.
		// This element must be used when non-sorting characters are present, rather than including
		// them in the text of the title element.
		$this->addProperty('titleInfo/nonSort', METADATA_PROPERTY_TYPE_STRING, true, METADATA_PROPERTY_CARDINALITY_ONE, 'metadata.property.displayName.nonSort', 'metadata.property.validationMessage.nonSort');

		// The title element contains the core title of the resource. At least one
		// title is required. This element includes all parts of a title not covered
		// by other sub-elements.
		$this->addProperty('titleInfo/title', METADATA_PROPERTY_TYPE_STRING, true, METADATA_PROPERTY_CARDINALITY_ONE, 'metadata.property.displayName.title', 'metadata.property.validationMessage.title', true);

		// The subTitle element is used to record a part of a title deemed secondary to the core
		// portion. Use this element when a subtitle is present, rather than including the subtitle
		// in the text of the title element. When using the subTitle element, do not include
		// punctuation at the end of the title element intended to delineate the title from the
		// subtitle.
		$this->addProperty('titleInfo/subTitle', METADATA_PROPERTY_TYPE_STRING, true, METADATA_PROPERTY_CARDINALITY_ONE, 'metadata.property.displayName.subTitle', 'metadata.property.validationMessage.subTitle');

		// The partNumber element is used for a part or section number of a title. Use this
		// subelement when a part number is present, rather than including the part number
		// in the text of the title element. When using the partNumber element, do not include
		// punctuation at the end of the preceding element intended to delineate the part
		// number from previous parts of the title. Multiple parts of an item should appear
		// in separate MODS records or relatedItem elements.
		$this->addProperty('titleInfo/partNumber', METADATA_PROPERTY_TYPE_STRING, true, METADATA_PROPERTY_CARDINALITY_ONE, 'metadata.property.displayName.partNumber', 'metadata.property.validationMessage.partNumber');

		// The partName element is used for a part or section name of a title. Multiple partName
		// elements may be nested in a single titleInfo to describe a single part with multiple
		// hierarchical levels; multiple parts, however, should be separated into multiple
		// titleInfo elements. Use this subelement when a part name is present, rather than
		// including the part name in the text of the title element. When using the partName
		// element, do not include punctuation at the end of the preceding element intended
		// to delineate the part name from previous parts of the title. Multiple parts of an
		// item should appear in separate MODS records or relatedItem elements.
		$this->addProperty('titleInfo/partName', METADATA_PROPERTY_TYPE_STRING, true, METADATA_PROPERTY_CARDINALITY_ONE, 'metadata.property.displayName.partName', 'metadata.property.validationMessage.partName');


		//
		// name
		//

		// The DLF/Aquifer Implementation Guidelines for Shareable MODS Records
		// requires the use of at least one name description for the creator of the
		// intellectual content of the resource.
		$personResources = array(
			array(METADATA_PROPERTY_TYPE_COMPOSITE => ASSOC_TYPE_AUTHOR),
			array(METADATA_PROPERTY_TYPE_COMPOSITE => ASSOC_TYPE_EDITOR)
		);
		$this->addProperty('name', $personResources, false, METADATA_PROPERTY_CARDINALITY_MANY, null, null, true);


		//
		// typeOfResource
		//

		// A term that specifies the characteristics and general type of content of the resource.
		// The DLF/Aquifer Implementation Guidelines for Shareable MODS Records require the
		// use in all records of at least one typeOfResource statement using the required
		// enumerated values (see the controlled vocabulary).
		$this->addProperty('typeOfResource', array(METADATA_PROPERTY_TYPE_VOCABULARY => 'mods34-typeOfResource'), false, METADATA_PROPERTY_CARDINALITY_ONE, null, null, true);


		//
		// genre
		//

		// A term that designates a category characterizing a particular style, form, or content,
		// such as artistic, musical, literary composition, etc. genre contains terms that give more
		// specificity than the broad terms used in typeOfResource (see the controlled vocabulary).
		$this->addProperty('genre[@authority="marcgt"]', array(METADATA_PROPERTY_TYPE_VOCABULARY => 'mods34-genre-marcgt'), false, METADATA_PROPERTY_CARDINALITY_ONE, null, null, true);


		//
		// originInfo
		//

		// The originInfo contains information about the origin of the resource,
		// including place of origin or publication, publisher/originator, and
		// dates associated with the resource.
		//
		// Encode information in originInfo relevant to any version of a resource
		// that is considered useful for a given metadata use case. It is usually not
		// necessary to include full originInfo for every version of a resource known
		// to exist; choose carefully which versions and elements it is important
		// to share in the context of your metadata use case.
		//
		// The DLF/Aquifer Implementation Guidelines for Shareable MODS Records require the
		// use of at least one originInfo statement with at least one date subelement in every
		// record, one of which must be marked as a key date. Place, publisher, and
		// edition are recommended if applicable. This element is repeatable.

		// Record in place/placeTerm place names associated with the
		// creation or issuance of a resource. Descriptive standards such as AACR2
		// may be used to determine which places to record for published
		// resources. For unpublished resources, if a place of origin is known,
		// record it in place/placeTerm.
		// The place/placeTerm element should be omitted if no information about
		// the originating place of the resource is known. Repeat place for recording
		// multiple places.

		// This is a text-encoded place name (usually the publisher's place description)
		$this->addProperty('originInfo/place/placeTerm[@type="text"]');

		// This is an ISO 3166 country name (usually the publisher's country).
		// NB: We could use a vocabulary for country names here. We avoid this for now as
		// the list would be huge.
		// See <http://www.iso.org/iso/country_codes/iso_3166_code_lists/english_country_names_and_code_elements.htm>
		// for a full list.
		$this->addProperty('originInfo/place/placeTerm[@type="code" @authority="iso3166"]');

		// Record in publisher a named entity determined to be the publisher or originator for a
		// resource. Descriptive standards such as AACR2 may be used to format the name of the
		// publisher. Information about an institution responsible for digitizing and delivering
		// online a previously published resource should be included as a note, rather than
		// originInfo/publisher.
		$this->addProperty('originInfo/publisher');

		// The MODS schema includes several date elements intended to record different events
		// that may be important in the life of a resource. Record dates for as many of these
		// MODS elements as is appropriate. To indicate to users which is the best date to use
		// for sorting and similar features, we mark the publication date (dateIssued) as a
		// key date using the keyDate="yes" attribute. You may choose to use only one date
		// element when several apply but would contain identical data.
		// The guidelines recommend recording each date in a structured form rather than a textual
		// form. We use W3CDTF encoding (YYYY[-MM[-DD]]) throughout.

		// publication or issued date
		$this->addProperty('originInfo/dateIssued[@keyDate="yes" @encoding="w3cdtf"]', METADATA_PROPERTY_TYPE_DATE);

		// date of creation of the resource
		$this->addProperty('originInfo/dateCreated[@encoding="w3cdtf"]', METADATA_PROPERTY_TYPE_DATE);

		// date on which a resource is copyrighted
		$this->addProperty('originInfo/copyrightDate[@encoding="w3cdtf"]', METADATA_PROPERTY_TYPE_DATE);

		// The edition element is used to provide an edition statement for a published work.
		// Descriptive standards such as AACR2 and DACS may be used to determine if an edition
		// statement should be recorded and in what format. If no edition statement applies to the
		// resource, do not include the edition element.
		$this->addProperty('originInfo/edition', METADATA_PROPERTY_TYPE_STRING, true);


		//
		// language
		//

		// At least one language/languageTerm element is required for resources in which
		// language is primary to understanding the resource. The language element is optional
		// for resources in which language is important to understanding the resource, but not
		// primary. For example, the caption of a photograph may in some instances be important
		// to understanding the photograph, but not primary. Whether to include a language
		// element based on the language's importance or primacy is left to the user's discretion.
		// Repeat the language element as necessary.
		// NB: We could use a vocabulary for language names here. We avoid this for now as
		// the list would be huge. See http://www.loc.gov/standards/iso639-2/php/code_list.php
		// for a complete list. Use the (B)-type codes when two codes exist.
		$this->addProperty('language/languageTerm[@type="code" @authority="iso639-2b"]', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_ONE, null, null, true);


		//
		// physicalDescription
		//

		// physicalDescription is a wrapper element that contains all subelements relating to
		// physical description information of the resource described.
		//
		// Encode information in physicalDescription relevant to any version of a resource
		// that is considered useful for a metadata use case. It is usually not necessary to
		// include a full physicalDescription for every version of a resource known to exist;
		// choose carefully which versions and elements are important for your metadata use
		// case.

		// This subelement specifies the physical form or medium of the material for a resource.
		$this->addProperty('physicalDescription/form[@authority="marcform"]', array(METADATA_PROPERTY_TYPE_VOCABULARY => 'mods34-physicalDescription-form-marcform'));

		// This subelement records the electronic format type of the digital resource. Whenever
		// a records describe resources existing in digital versions, at least one internetMediaType
		// is required. This element has no attributes.
		// Inclusion of an internetMediaType is a key feature of a shared metadata record to
		// enable external users to provide added value on resources themselves rather than only on
		// metadata.
		// The content value for this subelement should be taken from the MIME Media Types list
		// and expressed in the format type/subtype. If a digital resource comprises multiple file
		// types (for example PDF and HTML full text), use a separate internetMediaType subelement
		// for each.
		// NB: We could use a vocabulary for MIME types here. We avoid this for now as
		// the list would be huge. See <http://www.iana.org/assignments/media-types/index.html>
		// for a full list.
		$this->addProperty('physicalDescription/internetMediaType');

		// We use the extent field for the number of pages of a book only.
		$this->addProperty('physicalDescription/extent', METADATA_PROPERTY_TYPE_INTEGER);


		//
		// abstract
		//

		// A summary of the content of the resource.
		// The DLF/Aquifer Implementation Guidelines for Shareable MODS Records recommend
		// the use of one abstract element in every MODS record, except when a title, formal or
		// supplied, serves as an adequate summary of the content of the digital resource. This
		// element is repeatable.
		$this->addProperty('abstract', METADATA_PROPERTY_TYPE_STRING, true);


		//
		// note
		//

		// General textual information relating to a resource.
		$this->addProperty('note', METADATA_PROPERTY_TYPE_STRING, true, METADATA_PROPERTY_CARDINALITY_MANY);


		//
		// subject
		//

		// A subject is a term or phrase representing the primary topic(s) on which
		// a work is focused.
		//
		// Information in subject describes subject content represented in or by the work, and
		// typically answers such questions as who, what, where, and when.
		// Whether or not the use of subject is applicable depends upon who might search for an
		// item outside its local context and how they are likely to search for it. For instance, topical
		// subject content may not apply to some items, such as abstract art. If researchers are likely
		// to be interested in the form or genre of an item, and not its subject content, using the
		// genre element (not the subelement under subject) may be most appropriate.
		//
		// However, in many instances, using appropriate subject values can greatly enhance
		// users’ ability to locate relevant works. Enter as many specific terms as necessary to
		// capture subject content, and be consistent in the formatting of subject terms.
		//
		// It is highly recommended that subject terms come from a controlled vocabulary or formal
		// classification scheme and that this source is identified in the authority attribute. Select
		// controlled vocabularies that are most relevant to and frequently used by the communities
		// likely to benefit from the described materials, and explicitly identify this source.
		//
		// Express multiple subjects in repeated subject fields.

		// Will we use controlled vocabularies to validate subjects?
		if ($useAuthoritiesForSubject) {
			// The name of the authoritative list that controls all statements in this
			// description is recorded here. An authority attribute may also be used to indicate
			// that a subject is controlled by a record in an authority file. Authority should be
			// specified for all terms, whether they come from a controlled vocabulary, formal
			// scheme, or are locally developed. The authority attribute for a locally-developed
			// scheme should be defined as "local". If no list or scheme controls the terms used,
			// omit the authority attribute.
			$this->addProperty('subject/[@authority]', METADATA_PROPERTY_TYPE_STRING, true, METADATA_PROPERTY_CARDINALITY_ONE, null, null, true);

			$topicType = array(METADATA_PROPERTY_TYPE_VOCABULARY => 'mods34-subject-topic');
			$geographicType = array(METADATA_PROPERTY_TYPE_VOCABULARY => 'mods34-subject-geographic');
			$temporalType = array(METADATA_PROPERTY_TYPE_VOCABULARY => 'mods34-subject-temporal');
		} else {
			$topicType = $geographicType = $temporalType = METADATA_PROPERTY_TYPE_STRING;
		}

		// Use this subelement to indicate any primary topical subjects that are not appropriate in
		// the geographic, temporal, or name subelements. While it is highly recommended that subject
		// values be parsed into subelements, they may also be listed as a string under topic.
		$this->addProperty('subject/topic', $topicType, true, METADATA_PROPERTY_CARDINALITY_MANY);

		// The second version is expressed as a structured date using the same data
		// definition as MODS dates.
		$this->addProperty('subject/temporal[@encoding="w3cdtf"]', METADATA_PROPERTY_TYPE_DATE);
		$this->addProperty('subject/temporal[@encoding="w3cdtf" @point="start"]', METADATA_PROPERTY_TYPE_DATE);
		$this->addProperty('subject/temporal[@encoding="w3cdtf" @point="end"]', METADATA_PROPERTY_TYPE_DATE);


		//
		// identifier
		//

		// Unique standard numbers or codes that distinctively identify a resource.
		$this->addProperty('identifier[@type="isbn"]');
		$this->addProperty('identifier[@type="doi"]');
		$this->addProperty('identifier[@type="uri"]', METADATA_PROPERTY_TYPE_URI);


		//
		// location
		//

		// "location" identifies the institution or repository holding the resource, or a remote
		// location in the form of a URL where it is available.
		$this->addProperty('location/url[@usage="primary display"]', METADATA_PROPERTY_TYPE_URI);


		//
		// recordInfo
		//

		// This subelement is used to record the date the original MODS record was created.
		// Within the OAI context, service providers are more likely to rely on the
		// datestamp within the OAI header for information about when the record was created
		// than this date.
		$this->addProperty('recordInfo/recordCreationDate[@encoding="w3cdtf"]', METADATA_PROPERTY_TYPE_DATE);

		// Use this subelement to record the system control number assigned by the organization
		// creating, using, or distributing the record. Within the OAI context, service providers
		// are likely to rely on the identifier in the OAI header, rather than the recordIdentifier.
		// If recordIdentifier is used, the guidelines recommend the use of the source attribute
		// if possible.
		$this->addProperty('recordInfo/recordIdentifier[@source="pkp"]');

		// Use languageOfCataloging to record the language of the text of the cataloging in the
		// MODS record. If additional language(s) are used this will be indicated with the $locale
		// parameter within the specific element(s) in which the additional language(s)
		// appear(s).
		// NB: We could use a vocabulary for language names here. We avoid this for now as
		// the list would be huge. See http://www.loc.gov/standards/iso639-2/php/code_list.php
		// for a complete list. Use the (B)-type codes when two codes exist.
		$this->addProperty('recordInfo/languageOfCataloging/languageTerm[@authority="iso639-2b"]', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_ONE, null, null, true);
	}
}
?>
