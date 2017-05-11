<?php

/**
 * @defgroup plugins_citationLookup_crossref_filter CrossRef Citation Filter Plugin
 */

/**
 * @file plugins/citationLookup/crossref/filter/CrossrefNlm30CitationSchemaFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CrossrefNlm30CitationSchemaFilter
 * @ingroup plugins_citationLookup_crossref_filter
 *
 * @brief Filter that uses the Crossref web
 *  service to identify a DOI and corresponding
 *  meta-data for a given NLM citation.
 */


import('lib.pkp.plugins.metadata.nlm30.filter.Nlm30CitationSchemaFilter');
import('lib.pkp.classes.filter.EmailFilterSetting');

define('CROSSREF_WEBSERVICE_URL', 'http://www.crossref.org/openurl/');

class CrossrefNlm30CitationSchemaFilter extends Nlm30CitationSchemaFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		$this->setDisplayName('CrossRef');

		// Instantiate the settings of this filter
		$emailSetting = new EmailFilterSetting('email',
				'metadata.filters.crossref.settings.email.displayName',
				'metadata.filters.crossref.settings.email.validationMessage');
		$this->addSetting($emailSetting);

		parent::__construct(
			$filterGroup,
			array(
				NLM30_PUBLICATION_TYPE_JOURNAL,
				NLM30_PUBLICATION_TYPE_CONFPROC,
				NLM30_PUBLICATION_TYPE_BOOK,
				NLM30_PUBLICATION_TYPE_THESIS
			)
		);
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the CrossRef registered access email
	 * @param $email string
	 */
	function setEmail($email) {
		$this->setData('email', $email);
	}

	/**
	 * Get the CrossRef registered access email
	 * @return string
	 */
	function getEmail() {
		return $this->getData('email');
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'lib.pkp.plugins.citationLookup.crossref.filter.CrossrefNlm30CitationSchemaFilter';
	}


	//
	// Implement template methods from Filter
	//
	/**
	 * @copydoc Filter::process()
	 * @param $citationDescription MetadataDescription
	 * @return MetadataDescription
	 */
	function &process(&$citationDescription) {
		$nullVar = null;

		$email = $this->getEmail();
		assert(!empty($email));
		$searchParams = array(
			'pid' => $email,
			'noredirect' => 'true',
			'format' => 'unixref'
		);

		$doi = $citationDescription->getStatement('pub-id[@pub-id-type="doi"]');
		if (!empty($doi)) {
			// Directly look up the DOI with OpenURL 0.1.
			$searchParams['id'] = 'doi:'.$doi;
		} else {
			// Use OpenURL meta-data to search for the entry.
			if (is_null($openurl10Metadata = $this->_prepareOpenurl10Search($citationDescription))) return $nullVar;
			$searchParams += $openurl10Metadata;
		}

		// Call the CrossRef web service
		if (is_null($resultXml =& $this->callWebService(CROSSREF_WEBSERVICE_URL, $searchParams, XSL_TRANSFORMER_DOCTYPE_STRING)) || PKPString::substr(trim($resultXml), 0, 6) == '<html>') return $nullVar;

		// Remove default name spaces from XML as CrossRef doesn't
		// set them reliably and element names are unique anyway.
		$resultXml = PKPString::regexp_replace('/ xmlns="[^"]+"/', '', $resultXml);

		// Transform and process the web service result
		if (is_null($metadata =& $this->transformWebServiceResults($resultXml, dirname(__FILE__).DIRECTORY_SEPARATOR.'crossref.xsl'))) return $nullVar;

		return $this->getNlm30CitationDescriptionFromMetadataArray($metadata);
	}


	//
	// Private methods
	//
	/**
	 * Prepare a search with the CrossRef OpenURL resolver
	 * @param $citationDescription MetadataDescription
	 * @return array an array of search parameters
	 */
	function &_prepareOpenurl10Search(&$citationDescription) {
		$nullVar = null;

		// Crosswalk to OpenURL.
		import('lib.pkp.plugins.metadata.nlm30.filter.Nlm30CitationSchemaOpenurl10CrosswalkFilter');
		$nlm30Openurl10Filter = new Nlm30CitationSchemaOpenurl10CrosswalkFilter();
		if (is_null($openurl10Citation =& $nlm30Openurl10Filter->execute($citationDescription))) return $nullVar;

		// Prepare the search.
		$searchParams = array(
			'url_ver' => 'Z39.88-2004'
		);

		// Configure the meta-data schema.
		$openurl10CitationSchema =& $openurl10Citation->getMetadataSchema();
		switch(true) {
			case is_a($openurl10CitationSchema, 'Openurl10JournalSchema'):
				$searchParams['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:journal';
				break;

			case is_a($openurl10CitationSchema, 'Openurl10BookSchema'):
				$searchParams['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:book';
				break;

			case is_a($openurl10CitationSchema, 'Openurl10DissertationSchema'):
				$searchParams['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:dissertation';
				break;

			default:
				assert(false);
		}

		// Add all OpenURL meta-data to the search parameters.
		// FIXME: Implement a looping search like for other lookup services.
		$searchProperties = array(
			'aufirst', 'aulast', 'btitle', 'jtitle', 'atitle', 'issn',
			'artnum', 'date', 'volume', 'issue', 'spage', 'epage'
		);
		foreach ($searchProperties as $property) {
			if ($openurl10Citation->hasStatement($property)) {
				$searchParams['rft.'.$property] = $openurl10Citation->getStatement($property);
			}
		}

		return $searchParams;
	}
}
?>
