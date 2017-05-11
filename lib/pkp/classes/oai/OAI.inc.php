<?php

/**
 * @defgroup oai OAI
 * Implements an OAI (Open Archives Initiative) OAI-PMH interface. See
 * http://www.openarchives.org for information on OAI-PMH.
 */

/**
 * @file classes/oai/OAI.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAI
 * @ingroup oai
 * @see OAIDAO
 *
 * @brief Class to process and respond to OAI requests.
 */


import('lib.pkp.classes.oai.OAIStruct');
import('lib.pkp.classes.oai.OAIUtils');

abstract class OAI {
	/** @var OAIConfig configuration parameters */
	var $config;

	/** @var array list of request parameters */
	var $params;

	/** @var string version of the OAI protocol supported by this class */
	var $protocolVersion = '2.0';


	/**
	 * Constructor.
	 * Initializes object and parses user input.
	 * @param $config OAIConfig repository configuration
	 */
	function __construct($config) {
		$this->config = $config;

		// Initialize parameters from GET or POST variables
		$this->params = array();

		if (isset($GLOBALS['HTTP_RAW_POST_DATA']) && !empty($GLOBALS['HTTP_RAW_POST_DATA'])) {
			OAIUtils::parseStr($GLOBALS['HTTP_RAW_POST_DATA'], $this->params);

		} else if (!empty($_SERVER['QUERY_STRING'])) {
			OAIUtils::parseStr($_SERVER['QUERY_STRING'], $this->params);

		} else {
			$this->params = array_merge($_GET, $_POST);
		}

		// Clean input variables
		OAIUtils::prepInput($this->params);

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
	abstract function repositoryInfo();

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
	abstract function record($identifier);

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
	function records($metadataPrefix, $from, $until, $set, $offset, $limit, &$total) {
		return array();
	}

	/**
	 * Return set of OAI identifiers.
	 * @see getRecords
	 * @return array OAIIdentifier
	 */
	function identifiers($metadataPrefix, $from, $until, $set, $offset, $limit, &$total) {
		return array();
	}

	/**
	 * Return set of OAI sets.
	 * @param $offset int current set offset
	 * @param $total int output parameter, set to total number of sets
	 */
	function sets($offset, &$total) {
		return array();
	}

	/**
	 * Retrieve a resumption token.
	 * @param $tokenId string
	 * @return OAIResumptionToken (or false, if token invalid)
	 */
	abstract function resumptionToken($tokenId);

	/**
	 * Save a resumption token.
	 * @param $offset int current offset
	 * @param $params array request parameters
	 * @return OAIResumptionToken the saved token
	 */
	abstract function saveResumptionToken($offset, $params);

	/**
	 * Return array of supported metadata formats.
	 * @param $namesOnly boolean return array of format prefix names only
	 * @param $identifier string return formats for specific identifier
	 * @return array
	 */
	function metadataFormats($namesOnly = false, $identifier = null) {
		$formats = array();
		HookRegistry::call('OAI::metadataFormats', array($namesOnly, $identifier, &$formats));

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
		if (($record = $this->record($identifier)) === false) {
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
			"\t\t\t<header" .(($record->status == OAIRECORD_STATUS_DELETED)?" status=\"deleted\">\n":">\n") .
			"\t\t\t\t<identifier>" . $record->identifier ."</identifier>\n" .
			"\t\t\t\t<datestamp>" . $record->datestamp . "</datestamp>\n";
		// Output set memberships
		foreach ($record->sets as $setSpec) {
			$response .= "\t\t\t\t<setSpec>$setSpec</setSpec>\n";
		}
		$response .= "\t\t\t</header>\n";
		if (!empty($record->data)) {
			$response .= "\t\t\t<metadata>\n";
			// Output metadata
			$response .= $this->formatMetadata($metadataPrefix, $record);
			$response .= "\t\t\t</metadata>\n";
		}
		$response .= "\t\t</record>\n" .
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

		$info = $this->repositoryInfo();

		// Format body of response
		$response = "\t<Identify>\n" .
			"\t\t<repositoryName>" . OAIUtils::prepOutput($info->repositoryName) . "</repositoryName>\n" .
			"\t\t<baseURL>" . $this->config->baseUrl . "</baseURL>\n" .
			"\t\t<protocolVersion>" . $this->protocolVersion . "</protocolVersion>\n" .
			"\t\t<adminEmail>" . $info->adminEmail . "</adminEmail>\n" .
			"\t\t<earliestDatestamp>" . OAIUtils::UTCDate($info->earliestDatestamp) . "</earliestDatestamp>\n" .
			"\t\t<deletedRecord>persistent</deletedRecord>\n" .
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
			"\t\t</description>\n";
		$response .= "\t\t<description>\n" .
			"\t\t\t<toolkit\n" .
			"\t\t\t\txmlns=\"http://oai.dlib.vt.edu/OAI/metadata/toolkit\"\n" .
			"\t\t\t\txsi:schemaLocation=\"http://oai.dlib.vt.edu/OAI/metadata/toolkit\n" .
			"\t\t\t\t\thttp://oai.dlib.vt.edu/OAI/metadata/toolkit.xsd\">\n" .
			"\t\t\t\t<title>" . $info->toolkitTitle . "</title>\n" .
			"\t\t\t\t<author>\n" .
			"\t\t\t\t\t<name>Public Knowledge Project</name>\n" .
			"\t\t\t\t\t<email>pkp.contact@gmail.com</email>\n" .
			"\t\t\t\t</author>\n" .
			"\t\t\t\t<version>" . $info->toolkitVersion . "</version>\n" .
			"\t\t\t\t<URL>" . $info->toolkitURL . "</URL>\n" .
			"\t\t\t</toolkit>\n" .
			"\t\t</description>\n";
		$response .= "\t</Identify>\n";

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
			if (($token = $this->resumptionToken($this->getParam('resumptionToken'))) === false) {
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
		$records = $this->identifiers($metadataPrefix, $from, $until, $set, $offset, $this->config->maxIdentifiers, $total);
		if (empty($records)) {
			$this->error('noRecordsMatch', 'No matching records in this repository');
			return;
		}

		// Format body of response
		$response = "\t<ListIdentifiers>\n";

		// Output identifiers
		for ($i = 0, $num = count($records); $i < $num; $i++) {
			$record = $records[$i];
			$response .= "\t\t<header" .(($record->status == OAIRECORD_STATUS_DELETED)?" status=\"deleted\">\n":">\n") .
				"\t\t\t<identifier>" . $record->identifier . "</identifier>\n" .
				"\t\t\t<datestamp>" . $record->datestamp . "</datestamp>\n";
			// Output set memberships
			foreach ($record->sets as $setSpec) {
				$response .= "\t\t\t<setSpec>" . OAIUtils::prepOutput($setSpec) . "</setSpec>\n";
			}
			$response .= "\t\t</header>\n";
		}
		$offset += $num;

		if ($offset != 0 && $offset < $total) {
			// Partial result, save resumption token
			$token = $this->saveResumptionToken($offset, $this->getParams());

			$response .= "\t\t<resumptionToken expirationDate=\"" . OAIUtils::UTCDate($token->expire) . "\"\n" .
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
				$formats = $this->metadataFormats(false, $this->getParam('identifier'));
			}

		} else {
			$formats = $this->metadataFormats();
		}

		if (empty($formats) || !is_array($formats)) {
			$this->error('noMetadataFormats', 'No metadata formats are available');
			return;
		}

		// Format body of response
		$response = "\t<ListMetadataFormats>\n";

		// output metadata formats
		foreach ($formats as $format) {
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
			if (($token = $this->resumptionToken($this->getParam('resumptionToken'))) === false) {
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
		$records = $this->records($metadataPrefix, $from, $until, $set, $offset, $this->config->maxRecords, $total);
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
				"\t\t\t<header" .(($record->status == OAIRECORD_STATUS_DELETED)?" status=\"deleted\">\n":">\n") .
				"\t\t\t\t<identifier>" . $record->identifier . "</identifier>\n" .
				"\t\t\t\t<datestamp>" . $record->datestamp . "</datestamp>\n";
			// Output set memberships
			foreach ($record->sets as $setSpec) {
				$response .= "\t\t\t\t<setSpec>" . OAIUtils::prepOutput($setSpec) . "</setSpec>\n";
			}
			$response .= "\t\t\t</header>\n";
			if (!empty($record->data)) {
				$response .= "\t\t\t<metadata>\n";
				// Output metadata
				$response .= $this->formatMetadata($this->getParam('metadataPrefix'), $record);
				$response .= "\t\t\t</metadata>\n";
			}
			$response .= "\t\t</record>\n";
		}
		$offset += $num;

		if ($offset != 0 && $offset < $total) {
			// Partial result, save resumption token
			$token = $this->saveResumptionToken($offset, $this->getParams());

			$response .=	"\t\t<resumptionToken expirationDate=\"" . OAIUtils::UTCDate($token->expire) . "\"\n" .
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
			if (($token = $this->resumptionToken($this->getParam('resumptionToken'))) === false) {
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
		$sets = $this->sets($offset, $this->config->maxRecords, $total);
		if (empty($sets)) {
			$this->error('noSetHierarchy', 'This repository does not support sets');
			return;
		}

		// Format body of response
		$response = "\t<ListSets>\n";

		// Output sets
		for ($i = 0, $num = count($sets); $i < $num; $i++) {
			$set = $sets[$i];
			$response .=	"\t\t<set>\n" .
					"\t\t\t<setSpec>" . OAIUtils::prepOutput($set->spec) . "</setSpec>\n" .
					"\t\t\t<setName>" . OAIUtils::prepOutput($set->name) . "</setName>\n";
			// output set description, if applicable
			if (isset($set->description)) {
				$response .=	"\t\t\t<setDescription>\n" .
						"\t\t\t\t<oai_dc:dc\n" .
						"\t\t\t\t\txmlns:oai_dc=\"http://www.openarchives.org/OAI/2.0/oai_dc/\"\n" .
						"\t\t\t\t\txmlns:dc=\"http://purl.org/dc/elements/1.1/\"\n" .
						"\t\t\t\t\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
						"\t\t\t\t\txsi:schemaLocation=\"http://www.openarchives.org/OAI/2.0/oai_dc/\n" .
						"\t\t\t\t\t\thttp://www.openarchives.org/OAI/2.0/oai_dc.xsd\">\n" .
						"\t\t\t\t\t<dc:description>" . OAIUtils::prepOutput($set->description) . "</dc:description>\n" .
						"\t\t\t\t</oai_dc:dc>\n" .
						"\t\t\t</setDescription>\n";

			}
			$response .= "\t\t</set>\n";
		}
		$offset += $num;

		if ($offset != 0 && $offset < $total) {
			// Partial result, set resumption token
			$token = $this->saveResumptionToken($offset, $this->getParams());

			$response .=	"\t\t<resumptionToken expirationDate=\"" . OAIUtils::UTCDate($token->expire) . "\"\n" .
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
		header('Content-Type: text/xml');

		echo	"<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
			"<?xml-stylesheet type=\"text/xsl\" href=\"" . PKPRequest::getBaseUrl() . "/lib/pkp/xml/oai2.xsl\" ?>\n" .
			"<OAI-PMH xmlns=\"http://www.openarchives.org/OAI/2.0/\"\n" .
			"\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
			"\txsi:schemaLocation=\"http://www.openarchives.org/OAI/2.0/\n" .
			"\t\thttp://www.openarchives.org/OAI/2.0/OAI-PMH.xsd\">\n" .
			"\t<responseDate>" . OAIUtils::UTCDate() . "</responseDate>\n" .
			"\t<request";

		// print request params, if applicable
		if($printParams) {
			foreach($this->params as $k => $v) {
				echo " $k=\"" . OAIUtils::prepOutput($v) . "\"";
			}
		}

		echo	">" . OAIUtils::prepOutput($this->config->baseUrl) . "</request>\n" .
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
	function setParams($params) {
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
	 * Return a list of ignorable GET parameters.
	 * @return array
	 */
	function getNonPathInfoParams() {
		return array();
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
				// Ignore the "path" and "context" parameters if path_info is disabled.
				if (Request::isPathInfoEnabled() || !in_array($k, $this->getNonPathInfoParams())) {
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
	function formatMetadata($format, $record) {
		$formats = $this->metadataFormats();
		$metadata = $formats[$format]->toXml($record);
		return $metadata;
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
			$from = OAIUtils::UTCtoTimestamp($params['from']);

			if ($from == 'invalid') {
				$this->error('badArgument', 'Illegal from parameter');
				return false;

			} else if($from == 'invalid_granularity') {
				$this->error('badArgument', 'Illegal granularity for from parameter');
				return false;
			}
		}

		if(isset($params['until'])) {
			$until = OAIUtils::UTCtoTimestamp($params['until']);

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
}

?>
