<?php

/**
 * @defgroup plugins_citationLookup_pubmed_filter PubMed Citation Lookup Filter Plugin
 */

/**
 * @file plugins/citationLookup/pubmed/filter/PubmedNlm30CitationSchemaFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PubmedNlm30CitationSchemaFilter
 * @ingroup plugins_citationLookup_pubmed_filter
 *
 * @brief Filter that uses the Pubmed web
 *  service to identify a PMID and corresponding
 *  meta-data for a given NLM citation.
 */


import('lib.pkp.plugins.metadata.nlm30.filter.Nlm30CitationSchemaFilter');
import('lib.pkp.classes.filter.EmailFilterSetting');

define('PUBMED_WEBSERVICE_ESEARCH', 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi');
define('PUBMED_WEBSERVICE_EFETCH', 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi');
define('PUBMED_WEBSERVICE_ELINK', 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/elink.fcgi');

class PubmedNlm30CitationSchemaFilter extends Nlm30CitationSchemaFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		$this->setDisplayName('PubMed');

		// Instantiate the settings of this filter
		$emailSetting = new EmailFilterSetting('email',
				'metadata.filters.pubmed.settings.email.displayName',
				'metadata.filters.pubmed.settings.email.validationMessage',
				FORM_VALIDATOR_OPTIONAL_VALUE);
		$this->addSetting($emailSetting);

		parent::__construct(
			$filterGroup,
			array(
				NLM30_PUBLICATION_TYPE_JOURNAL,
				NLM30_PUBLICATION_TYPE_CONFPROC
			)
		);
	}

	//
	// Getters and Setters
	//
	/**
	 * Get the email
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
		return 'lib.pkp.plugins.citationLookup.pubmed.filter.PubmedNlm30CitationSchemaFilter';
	}


	//
	// Implement template methods from Filter
	//
	/**
	 * @see Filter::process()
	 * @param $citationDescription MetadataDescription
	 * @return MetadataDescription
	 */
	function &process(&$citationDescription) {
		$pmid = $citationDescription->getStatement('pub-id[@pub-id-type="pmid"]');

		// If the citation does not have a PMID, try to get one from eSearch
		// otherwise skip directly to eFetch.
		if (empty($pmid)) {
			// Initialize search result arrays.
			$pmidArrayFromAuthorsSearch = $pmidArrayFromTitleSearch = $pmidArrayFromStrictSearch = array();

			// 1) Try a "loose" search based on the author list.
			//    (This works surprisingly well for pubmed.)
			$authors =& $citationDescription->getStatement('person-group[@person-group-type="author"]');
			if (is_array($authors)) {
				import('lib.pkp.plugins.metadata.nlm30.filter.Nlm30NameSchemaPersonStringFilter');
				$personNameFilter = new Nlm30NameSchemaPersonStringFilter(PERSON_STRING_FILTER_MULTIPLE, '%firstname%%initials%%prefix% %surname%%suffix%', ', ');
				$authorsString = (string)$personNameFilter->execute($authors);
				if (!empty($authorsString)) {
					$pmidArrayFromAuthorsSearch =& $this->_search($authorsString);
				}
			}

			// 2) Try a "loose" search based on the article title
			$articleTitle = (string)$citationDescription->getStatement('article-title');
			if (!empty($articleTitle)) {
				$pmidArrayFromTitleSearch =& $this->_search($articleTitle);
			}

			// 3) Try a "strict" search based on as much information as possible
			$searchProperties = array(
				'article-title' => '',
				'person-group[@person-group-type="author"]' => '[Auth]',
				'source' => '[Jour]',
				'date' => '[DP]',
				'volume' => '[VI]',
				'issue' => '[IP]',
				'fpage' => '[PG]'
			);
			$searchTerms = '';
			$statements = $citationDescription->getStatements();
			foreach($searchProperties as $nlm30Property => $pubmedProperty) {
				if (isset($statements[$nlm30Property])) {
					if (!empty($searchTerms)) $searchTerms .= ' AND ';

					// Special treatment for authors
					if ($nlm30Property == 'person-group[@person-group-type="author"]') {
						assert(isset($statements['person-group[@person-group-type="author"]'][0]));
						$firstAuthor =& $statements['person-group[@person-group-type="author"]'][0];

						// Add surname
						$searchTerms .= (string)$firstAuthor->getStatement('surname');

						// Add initial of the first given name
						$givenNames = $firstAuthor->getStatement('given-names');
						if (is_array($givenNames)) $searchTerms .= ' '.PKPString::substr($givenNames[0], 0, 1);
					} else {
						$searchTerms .= $citationDescription->getStatement($nlm30Property);
					}

					$searchTerms .= $pubmedProperty;
				}
			}

			$pmidArrayFromStrictSearch =& $this->_search($searchTerms);

			// TODO: add another search like strict, but without article title
			// e.g.  ...term=Baumgart+Dc[Auth]+AND+Lancet[Jour]+AND+2005[DP]+AND+366[VI]+AND+9492[IP]+AND+1210[PG]

			// Compare the arrays to try to narrow it down to one PMID

			switch (true) {
				// strict search has a single result
				case (count($pmidArrayFromStrictSearch) == 1):
					$pmid = $pmidArrayFromStrictSearch[0];
					break;

				// 3-way union
				case (count($intersect = array_intersect($pmidArrayFromTitleSearch, $pmidArrayFromAuthorsSearch, $pmidArrayFromStrictSearch)) == 1):
					$pmid = current($intersect);
					break;

				// 2-way union: title / strict
				case (count($pmid_2way1 = array_intersect($pmidArrayFromTitleSearch, $pmidArrayFromStrictSearch)) == 1):
					$pmid = current($pmid_2way1);
					break;

				// 2-way union: authors / strict
				case (count($pmid_2way2 = array_intersect($pmidArrayFromAuthorsSearch, $pmidArrayFromStrictSearch)) == 1):
					$pmid = current($pmid_2way2);
					break;

				// 2-way union: authors / title
				case (count($pmid_2way3 = array_intersect($pmidArrayFromAuthorsSearch, $pmidArrayFromTitleSearch)) == 1):
					$pmid = current($pmid_2way3);
					break;

				// we only have one result for title
				case (count($pmidArrayFromTitleSearch) == 1):
					$pmid = $pmidArrayFromTitleSearch[0];
					break;

				// we only have one result for authors
				case (count($pmidArrayFromAuthorsSearch) == 1):
					$pmid = $pmidArrayFromAuthorsSearch[0];
					break;

				// we were unable to find a PMID
				default:
					$pmid = '';
			}
		}

		// If we have a PMID, get a metadata array for it
		if (!empty($pmid)) {
			$citationDescription =& $this->_lookup($pmid);
			return $citationDescription;
		}

		// Nothing found
		$nullVar = null;
		return $nullVar;
	}

	//
	// Private methods
	//
	/**
	 * Searches the given search terms with the pubmed
	 * eSearch and returns the found PMIDs as an array.
	 * @param $searchTerms
	 * @return array an array with PMIDs
	 */
	function &_search($searchTerms) {
		$searchParams = array(
			'db' => 'pubmed',
			'tool' => 'pkp-wal',
			'term' => $searchTerms
		);
		if (!is_null($this->getEmail())) $searchParams['email'] = $this->getEmail();

		// Call the eSearch web service and get an XML result
		if (is_null($resultDOM = $this->callWebService(PUBMED_WEBSERVICE_ESEARCH, $searchParams))) {
			$emptyArray = array();
			return $emptyArray;
		}

		// Loop through any results we have and add them to a PMID array
		$pmidArray = array();
		foreach ($resultDOM->getElementsByTagName('Id') as $idNode) {
			$pmidArray[] = $idNode->textContent;
		}

		return $pmidArray;
	}

	/**
	 * Fills the given citation object with
	 * meta-data retrieved from PubMed.
	 * @param $pmid string
	 * @return MetadataDescription
	 */
	function &_lookup($pmid) {
		$nullVar = null;

		// Use eFetch to get XML metadata for the given PMID
		$lookupParams = array(
			'db' => 'pubmed',
			'mode' => 'xml',
			'tool' => 'pkp-wal',
			'id' => $pmid
		);
		if (!is_null($this->getEmail())) $lookupParams['email'] = $this->getEmail();

		// Call the eFetch URL and get an XML result
		if (is_null($resultDOM = $this->callWebService(PUBMED_WEBSERVICE_EFETCH, $lookupParams))) return $nullVar;

		$articleTitleNodes = $resultDOM->getElementsByTagName('ArticleTitle');
		$articleTitleFirstNode = $articleTitleNodes->item(0);
		$medlineTaNodes = $resultDOM->getElementsByTagName('MedlineTA');
		$medlineTaFirstNode = $medlineTaNodes->item(0);
		$metadata = array(
			'pub-id[@pub-id-type="pmid"]' => $pmid,
			'article-title' =>$articleTitleFirstNode->textContent,
			'source' => $medlineTaFirstNode->textContent,
		);

		$volumeNodes = $resultDOM->getElementsByTagName('Volume');
		$issueNodes = $resultDOM->getElementsByTagName('Issue');
		if ($volumeNodes->length > 0)
			$volumeFirstNode = $volumeNodes->item(0);
			$metadata['volume'] = $volumeFirstNode->textContent;
		if ($issueNodes->length > 0)
			$issueFirstNode = $issueNodes->item(0);
			$metadata['issue'] = $issueFirstNode->textContent;

		// Get list of author full names
		foreach ($resultDOM->getElementsByTagName("Author") as $authorNode) {
			if (!isset($metadata['person-group[@person-group-type="author"]']))
				$metadata['person-group[@person-group-type="author"]'] = array();

			// Instantiate an NLM name description
			$authorDescription = new MetadataDescription('lib.pkp.plugins.metadata.nlm30.schema.Nlm30NameSchema', ASSOC_TYPE_AUTHOR);

			// Surname
			$lastNameNodes = $authorNode->getElementsByTagName('LastName');
			$lastNameFirstNode = $lastNameNodes->item(0);
			$authorDescription->addStatement('surname', $lastNameFirstNode->textContent);

			// Given names
			$givenNamesString = '';
			$firstNameNodes = $authorNode->getElementsByTagName('FirstName');
			if ($firstNameNodes->length > 0) {
				$firstNameFirstNode = $firstNameNodes->item(0);
				$givenNamesString = $firstNameFirstNode->textContent;
			} else {
				$foreNameNodes = $authorNode->getElementsByTagName('ForeName');
				if ($foreNameNodes->length > 0) {
					$foreNameFirstNode = $foreNameNodes->item(0);
					$givenNamesString = $foreNameFirstNode->textContent;
				}
			}
			if (!empty($givenNamesString)) {
				foreach(explode(' ', $givenNamesString) as $givenName) $authorDescription->addStatement('given-names', PKPString::trimPunctuation($givenName));
			}

			// Suffix
			$suffixNodes = $authorNode->getElementsByTagName('Suffix');
			if ($suffixNodes->length > 0) {
				$suffixFirstNode = $suffixNodes->item(0);
				$authorDescription->addStatement('suffix', $suffixFirstNode->textContent);
			}

			// Include collective names
			// FIXME: This corresponds to an NLM-citation <collab> tag and should be part of the Metadata implementation
			/*if ($resultDOM->getElementsByTagName("CollectiveName")->length > 0 && $authorNode->getElementsByTagName("CollectiveName")->item(0)->textContent != '') {
			}*/

			$metadata['person-group[@person-group-type="author"]'][] =& $authorDescription;
			unset($authorDescription);
		}

		// Extract pagination
		$medlinePgnNodes = $resultDOM->getElementsByTagName('MedlinePgn');
		$medlinePgnFirstNode = $medlinePgnNodes->item(0);
		if (PKPString::regexp_match_get("/^[:p\.\s]*(?P<fpage>[Ee]?\d+)(-(?P<lpage>\d+))?/", $medlinePgnFirstNode->textContent, $pages)) {
			$fPage = (integer)$pages['fpage'];
			$metadata['fpage'] = $fPage;
			if (!empty($pages['lpage'])) {
				$lPage = (integer)$pages['lpage'];

				// Deal with shortcuts like '382-7'
				if ($lPage < $fPage) {
					$lPage = (integer)(PKPString::substr($pages['fpage'], 0, -PKPString::strlen($pages['lpage'])).$pages['lpage']);
				}

				$metadata['lpage'] = $lPage;
			}
		}

		// Get publication date (can be in several places in PubMed).
		$dateNode = null;
		$articleDateNodes = $resultDOM->getElementsByTagName('ArticleDate');
		if ($articleDateNodes->length > 0) {
			$dateNode = $articleDateNodes->item(0);
		} else {
			$pubDateNodes = $resultDOM->getElementsByTagName('PubDate');
			if ($pubDateNodes->length > 0) {
				$dateNode = $pubDateNodes->item(0);
			}
		}

		// Retrieve the data parts and assemble date.
		if (!is_null($dateNode)) {
			$publicationDate = '';
			$requiresNormalization = false;
			foreach(array('Year' => 4, 'Month' => 2, 'Day' => 2) as $dateElement => $padding) {
				$dateElementNodes = $dateNode->getElementsByTagName($dateElement);
				if ($dateElementNodes->length > 0) {
					if (!empty($publicationDate)) $publicationDate.='-';
					$dateElementFirstNode = $dateElementNodes->item(0);
					$datePart = str_pad($dateElementFirstNode->textContent, $padding, '0', STR_PAD_LEFT);
					if (!is_numeric($datePart)) $requiresNormalization = true;
					$publicationDate .= $datePart;
				} else {
					break;
				}
			}

			// Normalize the date to NLM standard if necessary.
			if ($requiresNormalization) {
				$dateFilter = new DateStringNormalizerFilter();
				$publicationDate = $dateFilter->execute($publicationDate);
			}

			if (!empty($publicationDate)) $metadata['date'] = $publicationDate;
		}

		// Get publication type
		$publicationTypeNodes = $resultDOM->getElementsByTagName('PublicationType');
		if ($publicationTypeNodes->length > 0) {
			foreach($publicationTypeNodes as $publicationType) {
				// The vast majority of items on PubMed are articles so catch these...
				if (PKPString::strpos(PKPString::strtolower($publicationType->textContent), 'article') !== false) {
					$metadata['[@publication-type]'] = NLM30_PUBLICATION_TYPE_JOURNAL;
					break;
				}
			}
		}

		// Get DOI if it exists
		$articleIdNodes = $resultDOM->getElementsByTagName('ArticleId');
		foreach ($articleIdNodes as $idNode) {
			if ($idNode->getAttribute('IdType') == 'doi') {
				$metadata['pub-id[@pub-id-type="doi"]'] = $idNode->textContent;
			}
		}

		// Use eLink utility to find fulltext links
		$lookupParams = array(
			'dbfrom' => 'pubmed',
			'cmd' => 'llinks',
			'tool' => 'pkp-wal',
			'id' => $pmid
		);
		if(!is_null($resultDOM = $this->callWebService(PUBMED_WEBSERVICE_ELINK, $lookupParams))) {
			// Get a list of possible links
			foreach ($resultDOM->getElementsByTagName("ObjUrl") as $linkOut) {
				$attributes = '';
				foreach ($linkOut->getElementsByTagName("Attribute") as $attribute) $attributes .= PKPString::strtolower($attribute->textContent).' / ';

				// Only add links to open access resources
				if (PKPString::strpos($attributes, "subscription") === false && PKPString::strpos($attributes, "membership") === false &&
						PKPString::strpos($attributes, "fee") === false && $attributes != "") {
					$urlNodes = $linkOut->getElementsByTagName('Url');
					$urlFirstNode = $urlNodes->item(0);
					$links[] = $urlFirstNode->textContent;
				}
			}

			// Take the first link if we have any left (presumably pubmed returns them in preferential order)
			if (isset($links[0])) $metadata['uri'] = $links[0];
		}

		return $this->getNlm30CitationDescriptionFromMetadataArray($metadata);
	}
}
?>
