<?php

/**
 * @file plugins/importexport/users/filter/UserGroupNativeXmlFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserGroupNativeXmlFilter
 * @ingroup plugins_importexport_users
 *
 * @brief Base class that converts a set of user groups to a Native XML document
 */

import('lib.pkp.plugins.importexport.native.filter.NativeExportFilter');

class UserGroupNativeXmlFilter extends NativeExportFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		$this->setDisplayName('Native XML user group export');
		parent::__construct($filterGroup);
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'lib.pkp.plugins.importexport.users.filter.UserGroupNativeXmlFilter';
	}


	//
	// Implement template methods from Filter
	//
	/**
	 * @see Filter::process()
	 * @param $userGroups array Array of user groups
	 * @return DOMDocument
	 */
	function &process(&$userGroups) {
		// Create the XML document
		$doc = new DOMDocument('1.0');
		$deployment = $this->getDeployment();

		// Multiple authors; wrap in a <authors> element
		$rootNode = $doc->createElementNS($deployment->getNamespace(), 'user_groups');
		foreach ($userGroups as $userGroup) {
			$rootNode->appendChild($this->createUserGroupNode($doc, $userGroup));
		}
		$doc->appendChild($rootNode);
		$rootNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$rootNode->setAttribute('xsi:schemaLocation', $deployment->getNamespace() . ' ' . $deployment->getSchemaFilename());

		return $doc;
	}

	//
	// UserGroup conversion functions
	//
	/**
	 * Create and return a user group node.
	 * @param $doc DOMDocument
	 * @param $userGroup UserGroup
	 * @return DOMElement
	 */
	function createUserGroupNode($doc, $userGroup) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();

		// Create the user_group node
		$userGroupNode = $doc->createElementNS($deployment->getNamespace(), 'user_group');

		// Add metadata
		$userGroupNode->appendChild($doc->createElementNS($deployment->getNamespace(), 'role_id', $userGroup->getRoleId()));
		$userGroupNode->appendChild($doc->createElementNS($deployment->getNamespace(), 'context_id', $userGroup->getContextId()));
		$userGroupNode->appendChild($doc->createElementNS($deployment->getNamespace(), 'is_default', $userGroup->getDefault() ? 'true' : 'false'));
		$userGroupNode->appendChild($doc->createElementNS($deployment->getNamespace(), 'show_title', $userGroup->getShowTitle() ? 'true' : 'false'));
		$userGroupNode->appendChild($doc->createElementNS($deployment->getNamespace(), 'permit_self_registration', $userGroup->getPermitSelfRegistration() ? 'true' : 'false'));

		$this->createLocalizedNodes($doc, $userGroupNode, 'name', $userGroup->getName(null));
		$this->createLocalizedNodes($doc, $userGroupNode, 'abbrev', $userGroup->getAbbrev(null));

		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$assignedStages = $userGroupDao->getAssignedStagesByUserGroupId($context->getId(), $userGroup->getId());
		$userGroupNode->appendChild($doc->createElementNS($deployment->getNamespace(), 'stage_assignments', htmlspecialchars(join(':', array_keys($assignedStages)), ENT_COMPAT, 'UTF-8')));
		return $userGroupNode;
	}
}

?>
