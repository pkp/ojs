<?php

/**
 * OAIStruct.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package oai
 *
 * Data structures associated with the OAI request handler.
 *
 * $Id$
 */

/**
 * OAI repository configuration.
 */
class OAIConfig {

	/** @var $baseUrl string URL to the OAI front-end */
	var $baseUrl = '';
	
	/** @var $repositoryId string identifier of the repository */
	var $repositoryId = 'oai';
	
	/** @var $granularity string record datestamp granularity */
	// Must be either 'YYYY-MM-DD' or 'YYYY-MM-DDThh:mm:ssZ'
	var $granularity = 'YYYY-MM-DDThh:mm:ssZ';
	
	/** @var $tokenLifetime int TTL of resumption tokens */
	var $tokenLifetime = 3600;
	
	/** @var $maxIdentifiers int maximum identifiers returned per request */
	var $maxIdentifiers = 500;
	
	/** @var $maxRecords int maximum records returned per request */
	var $maxRecords = 200;
	
	/** @var $maxSets int maximum sets returned per request */
	// Must be set to zero if sets not supported by repository
	var $maxSets = 50;


	/**
	 * Constructor.
	 */
	function OAIConfig($baseUrl, $repositoryId) {
		$this->baseUrl = $baseUrl;
		$this->repositoryId = $repositoryId;
	}
}

/**
 * OAI repository information.
 */
class OAIRepository {
	
	/** @var $repositoryName string name of the repository */
	var $repositoryName;
	
	/** @var $adminEmail string administrative contact email */
	var $adminEmail;
	
	/** @var $earliestDatestamp int earliest *nix timestamp in the repository */
	var $earliestDatestamp;
	
	/** @var $delimiter string delimiter in identifier */
	var $delimiter = ':';
	
	/** @var $sampleIdentifier string example identifier */
	var $sampleIdentifier;
}


/**
 * OAI resumption token.
 * Used to resume a record retrieval at the last-retrieved offset.
 */
class OAIResumptionToken {

	/** @var $id string unique token ID */
	var $id;
	
	/** @var $offset int record offset */
	var $offset;
	
	/** @var $params array request parameters */
	var $params;
	
	/** @var $expire int expiration timestamp */
	var $expire;
	
	
	/**
	 * Constructor.
	 */
	function OAIResumptionToken($id, $offset, $params, $expire) {
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

	/** @var $prefix string metadata prefix */
	var $prefix;
	
	/** @var $schema string XML schema */
	var $schema;
	
	/** @var $namespace string XML namespace */
	var $namespace;
	
	/** @var $oai the parent OAI object */
	var $oai;
	
	
	/**
	 * Constructor.
	 */
	function OAIMetadataFormat(&$oai, $prefix, $schema, $namespace) {
		$this->oai = $oai;
		$this->prefix = $prefix;
		$this->schema = $schema;
		$this->namespace = $namespace;
	}
	
	/**
	 * Retrieve XML-formatted metadata for the specified record.
	 * @param $record OAIRecord
	 * @return string
	 */
	function toXML($record) {
		return '';
	}
}


/**
 * OAI set.
 * Identifies a set of related records.
 */
class OAISet {

	/** @var $spec string unique set specifier */
	var $spec;
	
	/** @var $name string set name */
	var $name;
	
	/** @var $description string set description */
	var $description;
	
	
	/**
	 * Constructor.
	 */
	function OAISet($spec, $name, $description) {
		$this->spec = $spec;
		$this->name = $name;
		$this->description = $description;
	}
}


/**
 * OAI identifier.
 */
class OAIIdentifier {

	/** @var $identifier string unique OAI record identifier */
	var $identifier;
	
	/** @var $datestamp int last-modified *nix timestamp */
	var $datestamp;
	
	/** @var $sets array sets this record belongs to */
	var $sets;
}


/**
 * OAI record.
 * Describes metadata for a single record in the repository.
 */
class OAIRecord extends OAIIdentifier {
	
	//
	// Metadata fields
	//
	
	var $url;
	var $title;
	var $creator;
	var $subject;
	var $description;
	var $publisher;
	var $contributor;
	var $date;
	var $type;
	var $format;
	var $source;
	var $language;
	var $relation;
	var $coverage;
	var $rights;
	var $pages;
}

?>
