<?php

/**
 * @file classes/oai/OAIStruct.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIConfig
 * @ingroup oai
 * @see OAI
 *
 * @brief Data structures associated with the OAI request handler.
 */

define('OAIRECORD_STATUS_DELETED', 0);
define('OAIRECORD_STATUS_ALIVE', 1);

/**
 * OAI repository configuration.
 */
class OAIConfig {
	/** @var string URL to the OAI front-end */
	var $baseUrl = '';

	/** @var string identifier of the repository */
	var $repositoryId = 'oai';

	/** @var string record datestamp granularity;
	 * Must be either 'YYYY-MM-DD' or 'YYYY-MM-DDThh:mm:ssZ'
	 */
	var $granularity = 'YYYY-MM-DDThh:mm:ssZ';

	/** @var int TTL of resumption tokens */
	var $tokenLifetime = 86400;

	/** @var int maximum identifiers returned per request */
	var $maxIdentifiers = 500;

	/** @var int maximum records returned per request */
	var $maxRecords;

	/** @var int maximum sets returned per request (must be 0 if sets not supported) */
	var $maxSets = 50;


	/**
	 * Constructor.
	 */
	function __construct($baseUrl, $repositoryId) {
		$this->baseUrl = $baseUrl;
		$this->repositoryId = $repositoryId;

		$this->maxRecords = Config::getVar('oai', 'oai_max_records');
		if (!$this->maxRecords) $this->maxRecords = 100;
	}
}

/**
 * OAI repository information.
 */
class OAIRepository {

	/** @var string name of the repository */
	var $repositoryName;

	/** @var string administrative contact email */
	var $adminEmail;

	/** @var int earliest *nix timestamp in the repository */
	var $earliestDatestamp;

	/** @var string delimiter in identifier */
	var $delimiter = ':';

	/** @var string example identifier */
	var $sampleIdentifier;

	/** @var string toolkit/software title (e.g. Open Journal Systems) */
	var $toolkitTitle;

	/** @var string toolkit/software version */
	var $toolkitVersion;

	/** @var string toolkit/software URL */
	var $toolkitURL;
}


/**
 * OAI resumption token.
 * Used to resume a record retrieval at the last-retrieved offset.
 */
class OAIResumptionToken {

	/** @var string unique token ID */
	var $id;

	/** @var int record offset */
	var $offset;

	/** @var array request parameters */
	var $params;

	/** @var int expiration timestamp */
	var $expire;


	/**
	 * Constructor.
	 */
	function __construct($id, $offset, $params, $expire) {
		$this->id = $id;
		$this->offset = $offset;
		$this->params = $params;
		$this->expire = $expire;
	}
}


/**
 * OAI metadata format.
 * Used to generated metadata XML according to a specified schema.
 */
class OAIMetadataFormat {

	/** @var string metadata prefix */
	var $prefix;

	/** @var string XML schema */
	var $schema;

	/** @var string XML namespace */
	var $namespace;

	/**
	 * Constructor.
	 */
	function __construct($prefix, $schema, $namespace) {
		$this->prefix = $prefix;
		$this->schema = $schema;
		$this->namespace = $namespace;
	}

	function getLocalizedData($data, $locale) {
		foreach ($data as $element) {
			if (isset($data[$locale])) return $data[$locale];
		}
		return '';
	}

	/**
	 * Retrieve XML-formatted metadata for the specified record.
	 * @param $record OAIRecord
	 * @param $format string OAI metadata prefix
	 * @return string
	 */
	function toXml($record, $format = null) {
		return '';
	}
}


/**
 * OAI set.
 * Identifies a set of related records.
 */
class OAISet {

	/** @var string unique set specifier */
	var $spec;

	/** @var string set name */
	var $name;

	/** @var string set description */
	var $description;


	/**
	 * Constructor.
	 */
	function __construct($spec, $name, $description) {
		$this->spec = $spec;
		$this->name = $name;
		$this->description = $description;
	}
}


/**
 * OAI identifier.
 */
class OAIIdentifier {
	/** @var string unique OAI record identifier */
	var $identifier;

	/** @var int last-modified *nix timestamp */
	var $datestamp;

	/** @var array sets this record belongs to */
	var $sets;

	/** @var string if this record is deleted */
	var $status;
}


/**
 * OAI record.
 * Describes metadata for a single record in the repository.
 */
class OAIRecord extends OAIIdentifier {
	var $data;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->data = array();
	}

	function setData($name, &$value) {
		$this->data[$name] =& $value;
	}

	function &getData($name) {
		if (isset($this->data[$name])) $returner =& $this->data[$name];
		else $returner = null;

		return $returner;
	}
}

?>
