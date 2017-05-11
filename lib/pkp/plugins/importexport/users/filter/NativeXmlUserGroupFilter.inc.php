<?php

/**
 * @file plugins/importexport/users/filter/NativeXmlUserGroupFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NativeXmlUserGroupFilter
 * @ingroup plugins_importexport_users
 *
 * @brief Base class that converts a Native XML document to a set of user groups
 */

import('lib.pkp.plugins.importexport.native.filter.NativeImportFilter');

class NativeXmlUserGroupFilter extends NativeImportFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		$this->setDisplayName('Native XML user group import');
		parent::__construct($filterGroup);
	}

	//
	// Implement template methods from NativeImportFilter
	//
	/**
	 * Return the plural element name
	 * @return string
	 */
	function getPluralElementName() {
		return 'user_groups';
	}

	/**
	 * Get the singular element name
	 * @return string
	 */
	function getSingularElementName() {
		return 'user_group';
	}

	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'lib.pkp.plugins.importexport.users.filter.NativeXmlUserGroupFilter';
	}


	/**
	 * Handle a user_group element
	 * @param $node DOMElement
	 * @return array Array of UserGroup objects
	 */
	function handleElement($node) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();

		// Create the UserGroup object.
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$userGroup = $userGroupDao->newDataObject();
		$userGroup->setContextId($context->getId());

		// Extract the name node element to see if this user group exists already.
		$nodeList = $node->getElementsByTagNameNS($deployment->getNamespace(), 'name');
		if ($nodeList->length == 1) {
			$content = $this->parseLocalizedContent($nodeList->item(0)); // $content[1] contains the localized name.
			$userGroups = $userGroupDao->getByContextId($context->getId());
			while ($testGroup = $userGroups->next()) {
				if (in_array($content[1], $testGroup->getName(null))) {
					return $testGroup;  // we found one with the same name.
				}
			}

			for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) if (is_a($n, 'DOMElement')) switch($n->tagName) {
				case 'role_id': $userGroup->setRoleId($n->textContent); break;
				case 'is_default': $userGroup->setDefault($n->textContent); break;
				case 'show_title': $userGroup->setShowTitle($n->textContent); break;
				case 'name': $userGroup->setName($n->textContent, $n->getAttribute('locale')); break;
				case 'abbrev': $userGroup->setAbbrev($n->textContent, $n->getAttribute('locale')); break;
				case 'permit_self_registration': $userGroup->setPermitSelfRegistration($n->textContent); break;
			}

			$userGroupId = $userGroupDao->insertObject($userGroup);

			$stageNodeList = $node->getElementsByTagNameNS($deployment->getNamespace(), 'stage_assignments');
			if ($stageNodeList->length == 1) {
				$n = $stageNodeList->item(0);
				$assignedStages = preg_split('/:/', $n->textContent);
				foreach ($assignedStages as $stage) {
					if($stage >= WORKFLOW_STAGE_ID_SUBMISSION && $stage <= WORKFLOW_STAGE_ID_PRODUCTION) {
						$userGroupDao->assignGroupToStage($context->getId(), $userGroupId, $stage);
					}
				}
			}

			return $userGroup;
		} else {
			fatalError("unable to find \"name\" userGroup node element.  Check import XML document structure for validity.");
		}
	}
}

?>
