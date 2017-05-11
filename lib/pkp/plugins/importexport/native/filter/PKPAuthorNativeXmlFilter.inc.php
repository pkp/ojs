<?php

/**
 * @file plugins/importexport/native/filter/PKPAuthorNativeXmlFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPAuthorNativeXmlFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Base class that converts a set of authors to a Native XML document
 */

import('lib.pkp.plugins.importexport.native.filter.NativeExportFilter');

class PKPAuthorNativeXmlFilter extends NativeExportFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		$this->setDisplayName('Native XML author export');
		parent::__construct($filterGroup);
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'lib.pkp.plugins.importexport.native.filter.PKPAuthorNativeXmlFilter';
	}


	//
	// Implement template methods from Filter
	//
	/**
	 * @see Filter::process()
	 * @param $authors array Array of authors
	 * @return DOMDocument
	 */
	function &process(&$authors) {
		// Create the XML document
		$doc = new DOMDocument('1.0');
		$doc->preserveWhiteSpace = false;
		$doc->formatOutput = true;
		$deployment = $this->getDeployment();

		// Multiple authors; wrap in a <authors> element
		$rootNode = $doc->createElementNS($deployment->getNamespace(), 'authors');
		foreach ($authors as $author) {
			$rootNode->appendChild($this->createPKPAuthorNode($doc, $author));
		}
		$doc->appendChild($rootNode);
		$rootNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$rootNode->setAttribute('xsi:schemaLocation', $deployment->getNamespace() . ' ' . $deployment->getSchemaFilename());

		return $doc;
	}

	//
	// PKPAuthor conversion functions
	//
	/**
	 * Create and return an author node.
	 * @param $doc DOMDocument
	 * @param $author PKPAuthor
	 * @return DOMElement
	 */
	function createPKPAuthorNode($doc, $author) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();

		// Create the author node
		$authorNode = $doc->createElementNS($deployment->getNamespace(), 'author');
		if ($author->getPrimaryContact()) $authorNode->setAttribute('primary_contact', 'true');
		if ($author->getIncludeInBrowse()) $authorNode->setAttribute('include_in_browse', 'true');

		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$userGroup = $userGroupDao->getById($author->getUserGroupId());
		assert(isset($userGroup));
		$authorNode->setAttribute('user_group_ref', $userGroup->getName($context->getPrimaryLocale()));

		// Add metadata
		$authorNode->appendChild($doc->createElementNS($deployment->getNamespace(), 'firstname', htmlspecialchars($author->getFirstName(), ENT_COMPAT, 'UTF-8')));
		$this->createOptionalNode($doc, $authorNode, 'middlename', $author->getMiddleName());
		$authorNode->appendChild($doc->createElementNS($deployment->getNamespace(), 'lastname', htmlspecialchars($author->getLastName(), ENT_COMPAT, 'UTF-8')));

		$this->createLocalizedNodes($doc, $authorNode, 'affiliation', $author->getAffiliation(null));

		$this->createOptionalNode($doc, $authorNode, 'country', $author->getCountry());
		$authorNode->appendChild($doc->createElementNS($deployment->getNamespace(), 'email', htmlspecialchars($author->getEmail(), ENT_COMPAT, 'UTF-8')));
		$this->createOptionalNode($doc, $authorNode, 'url', $author->getUrl());

		$this->createLocalizedNodes($doc, $authorNode, 'biography', $author->getBiography(null));

		return $authorNode;
	}
}

?>
