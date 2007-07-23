<?php

/**
 * @file OAI.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package oai
 * @class OAI
 *
 * Class to process and respond to OAI requests.
 *
 * $Id$
 */

import('oai.OAIStruct');

// Default supported OAI metadata formats
import('oai.format.OAIMetadataFormat_DC');
import('oai.format.OAIMetadataFormat_MARC');
import('oai.format.OAIMetadataFormat_MARC21');
import('oai.format.OAIMetadataFormat_RFC1807');

class OAI {

	/** @var $config OAIConfig configuration parameters */
	var $config;
	
	/** @var $params array list of request parameters */
	var $params;
	
	/** @var $protocolVersion string version of the OAI protocol supported by this class */
	var $protocolVersion = '2.0';
	

	/**
	 * Constructor.
	 * Initializes object and parses user input.
	 * @param $config OAIConfig repository configuration
	 */
	function OAI(&$config) {
		$this->config = $config;
		
		// Initialize parameters from GET or POST variables
		$this->params = array();
		
		if (isset($GLOBALS['HTTP_RAW_POST_DATA']) && !empty($GLOBALS['HTTP_RAW_POST_DATA'])) {
			$this->parseStr($GLOBALS['HTTP_RAW_POST_DATA'], $this->params);
			
		} else if (!empty($_SERVER['QUERY_STRING'])) {
			$this->parseStr($_SERVER['QUERY_STRING'], $this->params);		
			
		} else {
			$this->params = array_merge($_GET, $_POST);
		}
		
		// Clean input variables
		$this->prepInput($this->params);
		
		// Encode data with gzip, deflate, or none, depending on browser support
		ob_start('ob_gzhandler');
	}
	
	/**
	 * Execute the requested OAI protocol request
	 * and output the response.
	 */
	function execute() {
		switch ($this->getParam('verb')) {
			case 'GetRecord':
				$this->GetRecord();
				break;
			case 'Identify':
				$this->Identify();
				break;
			case 'ListIdentifiers':
				$this->ListIdentifiers();
				break;
			case 'ListMetadataFormats':
				$this->ListMetadataFormats();
				break;
			case 'ListRecords':
				$this->ListRecords();
				break;
			case 'ListSets':
				$this->ListSets();
				break;
			default:
				$this->error('badVerb', 'Illegal OAI verb');
				break;
		}
	}
	
	
	//
	// Abstract implementation-specific functions
	// (to be overridden in subclass)
	//
	
	/**
	 * Return information about the repository.
	 * @return OAIRepository
	 */
	function &repositoryInfo() {
		$info = false;
		return $info;
	}
	
	/**
	 * Check if identifier is in the valid format.
	 * @param $identifier string
	 * @return boolean
	 */
	function validIdentifier($identifier) {
		return false;
	}
	
	/**
	 * Check if identifier exists.
	 * @param $identifier string
	 * @return boolean
	 */
	function identifierExists($identifier) {
		return false;
	}
	
	/**
	 * Return OAI record for specified identifier.
	 * @param $identifier string
	 * @return OAIRecord (or false, if identifier is invalid)
	 */
	function &record($identifier) {
		$record = false;
		return $record;
	}
	
	/**
	 * Return set of OAI records.
	 * @param $metadataPrefix string specified metadata prefix
	 * @param $from int minimum timestamp
	 * @param $until int maximum timestamp
	 * @param $set string specified set
	 * @param $offset int current record offset
	 * @param $limit int maximum number of records to return
	 * @param $total int output parameter, set to total number of records
	 * @return array OAIRecord
	 */
	function &records($metadataPrefix, $from, $until, $set, $offset, $limit, &$total) {
		$records = array();
		return $records;
	}
	
	/**
	 * Return set of OAI identifiers.
	 * @see getRecords
	 * @return array OAIIdentifier
	 */
	function &identifiers($metadataPrefix, $from, $until, $set, $offset, $limit, &$total) {
		$identifiers = array();
		return $identifiers;
	}
	
	/**
	 * Return set of OAI sets.
	 * @param $offset int current set offset
	 * @param $total int output parameter, set to total number of sets
	 */
	function &sets($offset, &$total) {
		$sets = array();
		return $sets;
	}
	
	/**
	 * Retrieve a resumption token.
	 * @param $tokenId string
	 * @return OAIResumptionToken (or false, if token invalid)
	 */
	function &resumptionToken($tokenId) {
		$token = false;
		return $token;
	}
	
	/**
	 * Save a resumption token.
	 * @param $offset int current offset
	 * @param $params array request parameters
	 * @return OAIResumptionToken the saved token
	 */
	function &saveResumptionToken($offset, $params) {
		$token = null;
		return $token;
	}
	
	/**
	 * Return array of supported metadata formats.
	 * @param $namesOnly boolean return array of format prefix names only
	 * @param $identifier string return formats for specific identifier
	 * @return array
	 */
	function &metadataFormats($namesOnly = false, $identifier = null) {
		if ($namesOnly) {
			$formats = array('oai_dc', 'oai_marc', 'marcxml', 'rfc1807');
			
		} else {
			$formats = array(
				// Dublin Core
				'oai_dc' => new OAIMetadataFormat_DC($this, 'oai_dc', 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd', 'http://www.openarchives.org/OAI/2.0/oai_dc/'),

				// MARC
				'oai_marc' => new OAIMetadataFormat_MARC($this, 'oai_marc', "http://www.openarchives.org/OAI/1.1/oai_marc.xsd", "http://www.openarchives.org/OAI/1.1/oai_marc"),
				
				// MARC21
				'marcxml' => new OAIMetadataFormat_MARC21($this, 'marcxml', "http://www.loc.gov/standards/marcxml/schema/MARC21slim.xsd", "http://www.loc.gov/MARC21/slim"),

				// RFC 1807
				'rfc1807' => new OAIMetadataFormat_RFC1807($this, 'rfc1807', "http://www.openarchives.org/OAI/1.1/rfc1807.xsd", "http://info.internet.isi.edu:80/in-notes/rfc/files/rfc1807.txt")
			);
		}
		return $formats;
	}
	
	
	//
	// Protocol request handlers
	//
	
	/**
	 * Handle OAI GetRecord request.
	 * Retrieves an individual record from the repository.
	 */
	function GetRecord() {
		// Validate parameters
		if (!$this->checkParams(array('identifier', 'metadataPrefix'))) {
			return;
		}
		
		$identifier = $this->getParam('identifier');
		$metadataPrefix = $this->getParam('metadataPrefix');
		
		// Check that identifier is in valid format
		if ($this->validIdentifier($identifier) === false) {
			$this->error('badArgument', 'Identifier is not in a valid format');
			return;
		}
		
		// Get metadata for requested identifier
		if (($record = &$this->record($identifier)) === false) {
			$this->error('idDoesNotExist', 'No matching identifier in this repository');
			return;
		}
		
		// Check that the requested metadata format is supported for this identifier
		if (!in_array($metadataPrefix, $this->metadataFormats(true, $identifier))) {
			$this->error('cannotDisseminateFormat', 'The requested metadataPrefix is not supported by this repository');
			return;
		}
		
		// Display response
		$response = "\t<GetRecord>\n" .
			"\t\t<record>\n" .
			"\t\t\t<header>\n" .
			"\t\t\t\t<identifier>" . $record->identifier ."</identifier>\n" .
			"\t\t\t\t<datestamp>" . $record->datestamp . "</datestamp>\n";
		// Output set memberships
		foreach ($record->sets as $setSpec) {
			$response .= "\t\t\t\t<setSpec>$setSpec</setSpec>\n";
		}
		$response .= "\t\t\t</header>\n" .
			"\t\t\t<metadata>\n";
		// Output metadata
		$response .= $this->formatMetadata($metadataPrefix, $record);
		$response .= "\t\t\t</metadata>\n" .
			"\t\t</record>\n" .
			"\t</GetRecord>\n";
		
		$this->response($response);
	}
	
	
	/**
	 * Handle OAI Identify request.
	 * Retrieves information about a repository.
	 */
	function Identify() {
		// Validate parameters
		if (!$this->checkParams()) {
			return;
		}
		
		$info = &$this->repositoryInfo();
		
		// Format body of response
		$response = "\t<Identify>\n" .
			"\t\t<repositoryName>" . $this->prepOutput($info->repositoryName) . "</repositoryName>\n" .
			"\t\t<baseURL>" . $this->config->baseUrl . "</baseURL>\n" .
			"\t\t<protocolVersion>" . $this->protocolVersion . "</protocolVersion>\n" .
			"\t\t<adminEmail>" . $info->adminEmail . "</adminEmail>\n" .
			"\t\t<earliestDatestamp>" . $this->UTCDate($info->earliestDatestamp) . "</earliestDatestamp>\n" .
			"\t\t<deletedRecord>no</deletedRecord>\n" . // FIXME Support deleted records?
			"\t\t<granularity>" . $this->config->granularity . "</granularity>\n";
		if (extension_loaded('zlib')) {
			// Show compression options if server supports Zlib
			$response .= "\t\t<compression>gzip</compression>\n" .
				"\t\t<compression>deflate</compression>\n";
		}
		$response .= "\t\t<description>\n" .
			"\t\t\t<oai-identifier\n" .
			"\t\t\t\txmlns=\"http://www.openarchives.org/OAI/2.0/oai-identifier\"\n" .
			"\t\t\t\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
			"\t\t\t\txsi:schemaLocation=\"http://www.openarchives.org/OAI/2.0/oai-identifier\n" .
			"\t\t\t\t\thttp://www.openarchives.org/OAI/2.0/oai-identifier.xsd\">\n" .
			"\t\t\t\t<scheme>oai</scheme>\n" .
			"\t\t\t\t<repositoryIdentifier>" . $this->config->repositoryId . "</repositoryIdentifier>\n" .
			"\t\t\t\t<delimiter>" . $info->delimiter . "</delimiter>\n" .
			"\t\t\t\t<sampleIdentifier>" . $info->sampleIdentifier . "</sampleIdentifier>\n" .
			"\t\t\t</oai-identifier>\n" .
			"\t\t</description>\n" .
			"\t</Identify>\n";
		
		$this->response($response);
	}
	
	/**
	 * Handle OAI ListIdentifiers request.
	 * Retrieves headers of records from the repository.
	 */
	function ListIdentifiers() {
		$offset = 0;
		
		// Check for resumption token
		if ($this->paramExists('resumptionToken')) {		
			// Validate parameters
			if (!$this->checkParams(array('resumptionToken'))) {
				return;
			}
			
			// Get parameters from resumption token
			if (($token = &$this->resumptionToken($this->getParam('resumptionToken'))) === false) {
				$this->error('badResumptionToken', 'The requested resumptionToken is invalid or has expired');
				return;
			}
			
			$this->setParams($token->params);
			$offset = $token->offset;
		}
			
		// Validate parameters
		if (!$this->checkParams(array('metadataPrefix'), array('from', 'until', 'set'))) {
			return;
		}
		
		$metadataPrefix = $this->getParam('metadataPrefix');
		$set = $this->getParam('set');
		
		// Check that the requested metadata format is supported
		if (!in_array($metadataPrefix, $this->metadataFormats(true))) {
			$this->error('cannotDisseminateFormat', 'The requested metadataPrefix is not supported by this repository');
			return;
		}
	
		// If a set was passed in check if repository supports sets
		if (isset($set) && $this->config->maxSets == 0) {
			$this->error('noSetHierarchy', 'This repository does not support sets');
			return;
		}

		// Get UNIX timestamps for from and until dates, if applicable
		if (!$this->extractDateParams($this->getParams(), $from, $until)) {
			return;
		}
		
		// Store current offset and total records for resumption token, if needed
		$cursor = $offset;
		$total = 0;
				
		// Get list of matching identifiers
		$records = &$this->identifiers($metadataPrefix, $from, $until, $set, $offset, $this->config->maxIdentifiers, $total);
		if (empty($records)) {
			$this->error('noRecordsMatch', 'No matching records in this repository');
			return;
		}

		// Format body of response
		$response = "\t<ListIdentifiers>\n";
		
		// Output identifiers
		for ($i = 0, $num = count($records); $i < $num; $i++) {
			$record = $records[$i];
			$response .= "\t\t<header>\n" .
				"\t\t\t<identifier>" . $record->identifier . "</identifier>\n" .
				"\t\t\t<datestamp>" . $record->datestamp . "</datestamp>\n";
			// Output set memberships
			foreach ($record->sets as $setSpec) {
				$response .= "\t\t\t<setSpec>" . $this->prepOutput($setSpec) . "</setSpec>\n";
			}
			$response .= "\t\t</header>\n";
		}
		$offset += $num;
		
		if ($offset != 0 && $offset < $total) {
			// Partial result, save resumption token
			$token = &$this->saveResumptionToken($offset, $this->getParams());

			$response .= "\t\t<resumptionToken expirationDate=\"" . $this->UTCDate($token->expire) . "\"\n" .
				"\t\t\tcompleteListSize=\"$total\"\n" .
				"\t\t\tcursor=\"$cursor\">" . $token->id . "</resumptionToken>\n";
			             
		} else if (isset($token)) {
			// Current request completes a previous incomplete list, add empty resumption token
			$response .= "\t\t<resumptionToken completeListSize=\"$total\" cursor=\"$cursor\" />\n";
		}
		
		$response .= "\t</ListIdentifiers>\n";

		$this->response($response);
	}
	
	/**
	 * Handle OAI ListMetadataFormats request.
	 * Retrieves metadata formats supported by the repository.
	 */
	function ListMetadataFormats() {
		// Validate parameters
		if (!$this->checkParams(array(), array('identifier'))) {
			return;
		}
		
		// Get list of metadata formats for selected identifier, or all formats if no identifier was passed
		if ($this->paramExists('identifier')) {
			if (!$this->identifierExists($this->getParam('identifier'))) {
				$this->error('idDoesNotExist', 'No matching identifier in this repository');
				return;
				
			} else {
				$formats = &$this->metadataFormats(false, $this->getParam('identifier'));
			}
			
		} else {
			$formats = &$this->metadataFormats();
		}
		
		if (empty($formats) || !is_array($formats)) {
			$this->error('noMetadataFormats', 'No metadata formats are available');
			return;
		}
		
		// Format body of response
		$response = "\t<ListMetadataFormats>\n";
		
		// output metadata formats
		foreach ($formats as $prefix => $format) {
			$response .= "\t\t<metadataFormat>\n" .
				"\t\t\t<metadataPrefix>" . $format->prefix . "</metadataPrefix>\n" .
				"\t\t\t<schema>" . $format->schema . "</schema>\n" .
				"\t\t\t<metadataNamespace>" . $format->namespace . "</metadataNamespace>\n" .
				"\t\t</metadataFormat>\n";
		}
		
		$response .= "\t</ListMetadataFormats>\n";
		
		$this->response($response);
	}
	
	/**
	 * Handle OAI ListRecords request.
	 * Retrieves records from the repository.
	 */
	function ListRecords() {
		$offset = 0;
		
		// Check for resumption token
		if ($this->paramExists('resumptionToken')) {		
			// Validate parameters
			if (!$this->checkParams(array('resumptionToken'))) {
				return;
			}
			
			// get parameters from resumption token
			if (($token = &$this->resumptionToken($this->getParam('resumptionToken'))) === false) {
				$this->error('badResumptionToken', 'The requested resumptionToken is invalid or has expired');
				return;
			}
			
			$this->setParams($token->params);
			$offset = $token->offset;
			
		}
		
		// Validate parameters
		if (!$this->checkParams(array('metadataPrefix'), array('from', 'until', 'set'))) {
			return;
		}
		
		$metadataPrefix = $this->getParam('metadataPrefix');
		$set = $this->getParam('set');
		
		// Check that the requested metadata format is supported
		if (!in_array($metadataPrefix, $this->metadataFormats(true))) {
			$this->error('cannotDisseminateFormat', 'The requested metadataPrefix is not supported by this repository');
			return;
		}
	
		// If a set was passed check if repository supports sets
		if (isset($set) && $this->config->maxSets == 0) {
			$this->error('noSetHierarchy', 'This repository does not support sets');
			return;
		}
		
		// Get UNIX timestamps for from and until dates, if applicable
		if (!$this->extractDateParams($this->getParams(), $from, $until)) {
			return;
		}
		
		// Store current offset and total records for resumption token, if needed
		$cursor = $offset;
		$total = 0;
		
		// Get list of matching records
		$records = &$this->records($metadataPrefix, $from, $until, $set, $offset, $this->config->maxRecords, $total);
		if (empty($records)) {
			$this->error('noRecordsMatch', 'No matching records in this repository');
			return;
		}

		// Format body of response
		$response = "\t<ListRecords>\n";
		
		// Output records
		for ($i = 0, $num = count($records); $i < $num; $i++) {
			$record = $records[$i];
			$response .= "\t\t<record>\n" .
				"\t\t\t<header>\n" .
				"\t\t\t\t<identifier>" . $record->identifier . "</identifier>\n" .
				"\t\t\t\t<datestamp>" . $record->datestamp . "</datestamp>\n";
			// Output set memberships
			foreach ($record->sets as $setSpec) {
				$response .= "\t\t\t\t<setSpec>" . $this->prepOutput($setSpec) . "</setSpec>\n";
			}
			$response .= "\t\t\t</header>\n" .
			             "\t\t\t<metadata>\n";
			// Output metadata
			$response .= $this->formatMetadata($this->getParam('metadataPrefix'), $record);
			$response .= "\t\t\t</metadata>\n" .
			             "\t\t</record>\n";
		}
		$offset += $num;
		
		if ($offset != 0 && $offset < $total) {
			// Partial result, save resumption token
			$token = &$this->saveResumptionToken($offset, $this->getParams());

			$response .= "\t\t<resumptionToken expirationDate=\"" . $this->UTCDate($token->expire) . "\"\n" .
			             "\t\t\tcompleteListSize=\"$total\"\n" .
			             "\t\t\tcursor=\"$cursor\">" . $token->id . "</resumptionToken>\n";

		} else if(isset($token)) {
			// Current request completes a previous incomplete list, add empty resumption token
			$response .= "\t\t<resumptionToken completeListSize=\"$total\" cursor=\"$cursor\" />\n";
		}
		
		$response .= "\t</ListRecords>\n";

		$this->response($response);
	}
	
	/**
	 * Handle OAI ListSets request.
	 * Retrieves sets from a repository.
	 */
	function ListSets() {
		$offset = 0;
		
		// Check for resumption token
		if ($this->paramExists('resumptionToken')) {		
			// Validate parameters
			if (!$this->checkParams(array('resumptionToken'))) {
				return;
			}
			
			// Get parameters from resumption token
			if (($token = &$this->resumptionToken($this->getParam('resumptionToken'))) === false) {
				$this->error('badResumptionToken', 'The requested resumptionToken is invalid or has expired');
				return;
			}

 			$this->setParams($token->params);
			$offset = $token->offset;
			
		}
			
		// Validate parameters
		if (!$this->checkParams()) {
			return;
		}
		
		// Store current offset and total sets for resumption token, if needed
		$cursor = $offset;
		$total = 0;
		
		// Get list of matching sets
		$sets = &$this->sets($offset, $total);
		if (empty($sets)) {
			$this->error('noSetHierarchy', 'This repository does not support sets');
			return;
		}

		// Format body of response
		$response = "\t<ListSets>\n";
		
		// Output sets
		for ($i = 0, $num = count($sets); $i < $num; $i++) {
			$set = $sets[$i];
			$response .= "\t\t<set>\n" .
			             "\t\t\t<setSpec>" . $this->prepOutput($set->spec) . "</setSpec>\n" .
			             "\t\t\t<setName>" . $this->prepOutput($set->name) . "</setName>\n";
			// output set description, if applicable
			if (isset($set->description)) {
				$response .= "\t\t\t<setDescription>\n" .
				             "\t\t\t\t<oai_dc:dc\n" .
				             "\t\t\t\t\txmlns:oai_dc=\"http://www.openarchives.org/OAI/2.0/oai_dc/\"\n" .
				             "\t\t\t\t\txmlns:dc=\"http://purl.org/dc/elements/1.1/\"\n" .
				             "\t\t\t\t\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
				             "\t\t\t\t\txsi:schemaLocation=\"http://www.openarchives.org/OAI/2.0/oai_dc/\n" .
				             "\t\t\t\t\t\thttp://www.openarchives.org/OAI/2.0/oai_dc.xsd\">\n" .
				             "\t\t\t\t\t<dc:description>" . $this->prepOutput($set->description) . "</dc:description>\n" .
				             "\t\t\t\t</oai_dc:dc>\n" .
				             "\t\t\t</setDescription>\n";
				             
			}
			$response .= "\t\t</set>\n";
		}
		
		if ($offset != 0 && $offset < $total) {
			// Partial result, set resumption token
			$token = &$this->saveResumptionToken($offset, $this->getParams());

			$response .= "\t\t<resumptionToken expirationDate=\"" . $this->UTCDate($token->expire) . "\"\n" .
			             "\t\t\tcompleteListSize=\"$total\"\n" .
			             "\t\t\tcursor=\"$cursor\">" . $token->id . "</resumptionToken>\n";
			             
		} else if (isset($token)) {
			// current request completes a previous incomplete list, add empty resumption token
			$response .= "\t\t<resumptionToken completeListSize=\"$total\" cursor=\"$cursor\" />\n";
		}
		
		$response .= "\t</ListSets>\n";

		$this->response($response);
	}
	
	
	//
	// Private helper functions
	//

	/**
	 * Display OAI error response.
	 */
	function error($code, $message) {
		if (in_array($code, array('badVerb', 'badArgument'))) {
			$printParams = false;
			
		} else {
			$printParams = true;
		}
		
		$this->response("\t<error code=\"$code\">$message</error>", $printParams);
	}
	
	/**
	 * Output OAI response.
	 * @param $response string text of response message.
	 * @param $printParams boolean display request parameters
	 */
	function response($response, $printParams = true) {
		header("Content-Type: text/xml");
		
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
		     "<OAI-PMH xmlns=\"http://www.openarchives.org/OAI/2.0/\"\n" .
		     "\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
		     "\txsi:schemaLocation=\"http://www.openarchives.org/OAI/2.0/\n" .
		     "\t\thttp://www.openarchives.org/OAI/2.0/OAI-PMH.xsd\">\n" .
		     "\t<responseDate>" . $this->UTCDate() . "</responseDate>\n" .
		     "\t<request";
		
		// print request params, if applicable
		if($printParams) {
			foreach($this->params as $k => $v) {
				echo " $k=\"" . $this->prepOutput($v) . "\"";
			}
		}
		
		echo ">" . $this->config->baseUrl . "</request>\n" .
		     $response .
		     "</OAI-PMH>\n";
	}
	
	/**
	 * Returns the value of the specified parameter.
	 * @param $name string
	 * @return string
	 */
	function getParam($name) {
		return isset($this->params[$name]) ? $this->params[$name] : null;
	}
	
	/**
	 * Returns an associative array of all request parameters.
	 * @return array
	 */
	function getParams() {
		return $this->params;
	}
	
	/**
	 * Set the request parameters.
	 * @param $params array
	 */
	function setParams(&$params) {
		$this->params = $params;
	}
	
	/**
	 * Returns true if the requested parameter is set, false if it is not set.
	 * @param $name string
	 * @return boolean
	 */
	function paramExists($name) {
		return isset($this->params[$name]);
	}
	
	/**
	 * Check request parameters.
	 * Outputs error response if an invalid parameter is found.
	 * @param $required array required parameters for the current request
	 * @param $optional array optional parameters for the current request
	 * @return boolean
	 */
	function checkParams($required = array(), $optional = array()) {
		// Get allowed parameters for current request
		$requiredParams = array_merge(array('verb'), $required);
		$validParams = array_merge($requiredParams, $optional);

		// Check for missing or duplicate required parameters
		foreach ($requiredParams as $k) {
			if(!$this->paramExists($k)) {
				$this->error('badArgument', "Missing $k parameter");
				return false;
				
			} else if (is_array($this->getParam($k))) {
				$this->error('badArgument', "Multiple values are not allowed for the $k parameter");
				return false;
			}
		}
		
		// Check for duplicate optional parameters
		foreach ($optional as $k) {
			if ($this->paramExists($k) && is_array($this->getParam($k))) {
				$this->error('badArgument', "Multiple values are not allowed for the $k parameter");
				return false;
			}
		}
		
		// Check for illegal parameters
		foreach ($this->params as $k => $v) {
			if (!in_array($k, $validParams)) {
				// Ignore the "path" and "journal" parameters if path_info is disabled.
				if (Request::isPathInfoEnabled() || ($k != 'journal' && $k != 'page')) {
					$this->error('badArgument', "$k is an illegal parameter");
					return false;
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Returns formatted metadata response in specified format.
	 * @param $format string
	 * @param $metadata OAIMetadata
	 * @return string
	 */
	function &formatMetadata($format, $record) {
		$formats = &$this->metadataFormats();		
		$metadata = $formats[$format]->toXML($record);
		return $metadata;
	}
	
	/**
	 * Return a UTC-formatted datestamp from the specified UNIX timestamp.
	 * @param $timestamp int *nix timestamp (if not used, the current time is used)
	 * @param $includeTime boolean include both the time and date
	 * @return string UTC datestamp
	 */
	function UTCDate($timestamp = 0, $includeTime = true) {
		$format = "Y-m-d";
		if($includeTime) {
			$format .= "\TH:i:s\Z";
		}
		
		if($timestamp == 0) {
			return gmdate($format);
			
		} else {
			return gmdate($format, $timestamp);
		}
	}
	
	/**
	 * Returns a UNIX timestamp from a UTC-formatted datestamp.
	 * Returns the string "invalid" if datestamp is invalid,
	 * or "invalid_granularity" if unsupported granularity.
	 * @param $date string UTC datestamp
	 * @param $checkGranularity boolean verify that granularity is correct
	 * @return int timestamp
	 */
	function UTCtoTimestamp($date, $checkGranularity = true) {
		// FIXME Has limited range (see http://php.net/strtotime)
		if (preg_match("/^\d\d\d\d\-\d\d\-\d\d$/", $date)) {
			// Match date
			$time = strtotime("$date UTC");
			return ($time != -1) ? $time : 'invalid';
			
		} else if (preg_match("/^(\d\d\d\d\-\d\d\-\d\d)T(\d\d:\d\d:\d\d)Z$/", $date, $matches)) {
			// Match datetime
			// FIXME
			$date = "$matches[1] $matches[2]";
			if ($checkGranularity && $this->config->granularity != 'YYYY-MM-DDThh:mm:ssZ') {
				return 'invalid_granularity';
				
			} else {
				$time = strtotime("$date UTC");
				return ($time != -1) ? $time : 'invalid';
			}
			
		} else {
			return 'invalid';
		}
	}
	
	/**
	 * Checks if from and until parameters have been passed.
	 * If passed, validate and convert to UNIX timestamps.
	 * @param $params array request parameters
	 * @param $from int from timestamp (output parameter)
	 * @param $until int until timestamp (output parameter)
	 * @return boolean
	 */
	function extractDateParams($params, &$from, &$until) {
		if (isset($params['from'])) {
			$from = $this->UTCtoTimestamp($params['from']);
			
			if ($from == 'invalid') {
				$this->error('badArgument', 'Illegal from parameter');
				return false;
			
			} else if($from == 'invalid_granularity') {
				$this->error('badArgument', 'Illegal granularity for from parameter');
				return false;
			}
		}
		
		if(isset($params['until'])) {
			$until = $this->UTCtoTimestamp($params['until']);
			
			if($until == 'invalid') {
				$this->error('badArgument', 'Illegal until parameter');
				return false;
			
			} else if($until == 'invalid_granularity') {
				$this->error('badArgument', 'Illegal granularity for until parameter');
				return false;
			}
			
			// Check that until value is greater than or equal to from value
			if (isset($from) && $from > $until) {
				$this->error('badArgument', 'until parameter must be greater than or equal to from parameter');
				return false;
			}
			
			// Check that granularities are equal
			if (isset($from) && strlen($params['from']) != strlen($params['until'])) {
				$this->error('badArgument', 'until and from parameters must be of the same granularity');
				return false;
			}
			
			if (strlen($params['until']) == 10) {
				// Until date is inclusive 
				$until += 86399;
			}
		}
		
		return true;
	}
	
	/**
	 * Clean input variables.
	 * @param $data mixed request parameter(s)
	 * @return mixed cleaned request parameter(s)
	 */
	function prepInput(&$data) {
		if (!is_array($data)) {
			$data = urldecode($data);
			
		} else {
			foreach ($data as $k => $v) {
				if (is_array($data[$k])) {
					$this->prepInput($data[$k]);
				} else {
					$data[$k] = urldecode($v);
				}
			}
		}
		return $data;
	}
	
	/**
	 * Prepare variables for output.
	 * Data is assumed to be UTF-8 encoded (FIXME?)
	 * @param $data mixed output parameter(s)
	 * @return mixed cleaned output parameter(s)
	 */
	function prepOutput(&$data) {
		if (!is_array($data)) {
			$data = htmlspecialchars($data);
			
		} else {
			foreach ($data as $k => $v) {
				if (is_array($data[$k])) {
					$this->prepOutput($data[$k]);
				} else {
					// FIXME FIXME FIXME
					$data[$k] = htmlspecialchars($v);
				}
			}
		}
		return $data;
	}
	
	/**
	 * Parses string $string into an associate array $array.
	 * Acts like parse_str($string, $array) except duplicate
	 * variable names in $string are converted to an array.
	 * @param $duplicate string input data string
	 * @param $array array of parsed parameters
	 */
	function parseStr($string, &$array) {
		$pairs = explode('&', $string);
		foreach ($pairs as $p) {
			$vars = explode('=', $p);
			if (!empty($vars[0]) && isset($vars[1])) {
				$key = $vars[0];
				$value = join('=', array_splice($vars, 1));
				
				if (!isset($array[$key])) {
					$array[$key] = $value;
				} else if (is_array($array[$key])) {
					array_push($array[$key], $value);
				} else {
					$array[$key] = array($array[$key], $value);
				}
			}
		}
	}
	
}

?>
