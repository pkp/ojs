<?php

/**
 * @file plugins/importexport/users/filter/UserXmlPKPUserFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserXmlPKPUserFilter
 * @ingroup plugins_importexport_users
 *
 * @brief Base class that converts a User XML document to a set of users
 */

import('lib.pkp.plugins.importexport.native.filter.NativeImportFilter');

class UserXmlPKPUserFilter extends NativeImportFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		$this->setDisplayName('User XML user import');
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
		return 'PKPUsers';
	}

	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'lib.pkp.plugins.importexport.users.filter.UserXmlPKPUserFilter';
	}

	/**
	 * Handle a user_groups element
	 * @param $node DOMElement
	 * @return array Array of UserGroup objects
	 */
	function parseUserGroup($node) {

		$filterDao = DAORegistry::getDAO('FilterDAO');
		$importFilters = $filterDao->getObjectsByGroup('user-xml=>usergroup');
		assert(count($importFilters)==1); // Assert only a single unserialization filter
		$importFilter = array_shift($importFilters);
		$importFilter->setDeployment($this->getDeployment());
		$userGroupDoc = new DOMDocument();
		$userGroupDoc->appendChild($userGroupDoc->importNode($node, true));
		return $importFilter->execute($userGroupDoc);
	}

	/**
	 * Handle a users element
	 * @param $node DOMElement
	 * @return array Array of User objects
	 */
	function parseUser($node) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();

		// Create the data object
		$userDao = DAORegistry::getDAO('UserDAO');
		$user = $userDao->newDataObject();

		// Handle metadata in subelements
		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) if (is_a($n, 'DOMElement')) switch($n->tagName) {
			case 'username': $user->setUsername($n->textContent); break;
			case 'firstname': $user->setFirstName($n->textContent); break;
			case 'middlename': $user->setMiddleName($n->textContent); break;
			case 'lastname': $user->setLastName($n->textContent); break;
			case 'initials': $user->setInitials($n->textContent); break;
			case 'salutation': $user->setSalutation($n->textContent); break;
			case 'suffix': $user->setSuffix($n->textContent); break;
			case 'gender': $user->setGender($n->textContent); break;
			case 'affiliation': $user->setAffiliation($n->textContent, $n->getAttribute('locale')); break;
			case 'country': $user->setCountry($n->textContent); break;
			case 'email': $user->setEmail($n->textContent); break;
			case 'url': $user->setUrl($n->textContent); break;
			case 'phone': $user->setPhone($n->textContent); break;
			case 'billing_address': $user->setBillingAddress($n->textContent); break;
			case 'mailing_address': $user->setMailingAddress($n->textContent); break;
			case 'biography': $user->setBiography($n->textContent, $n->getAttribute('locale')); break;
			case 'gossip': $user->setGossip($n->textContent, $n->getAttribute('locale')); break;
			case 'signature': $user->setSignature($n->textContent, $n->getAttribute('locale')); break;
			case 'date_registered': $user->setDateRegistered($n->textContent); break;
			case 'date_last_login': $user->setDateLastLogin($n->textContent); break;
			case 'date_last_email': $user->setDateLastEmail($n->textContent); break;
			case 'date_validated': $user->setDateValidated($n->textContent); break;
			case 'inline_help':$n->textContent == 'true' ? $user->setInlineHelp(true) : $user->setInlineHelp(false) ; break;
			case 'auth_id': $user->setAuthId($n->textContent); break;
			case 'auth_string': $user->setAuthString($n->textContent); break;
			case 'disabled_reason': $user->setDisabledReason($n->textContent); break;
			case 'locales': $user->setLocales(preg_split('/:/', $n->textContent)); break;

			case 'password':
				if ($n->getAttribute('must_change') == 'true') {
					$user->setMustChangePassword(true);
				}
				if ($n->getAttribute('is_disabled') == 'true') {
					$user->setIsDisabled(true);
				}
				$passwordValueNodeList = $n->getElementsByTagNameNS($deployment->getNamespace(), 'value');
				if ($passwordValueNodeList->length == 1) {
					$password = $passwordValueNodeList->item(0);
					$user->setPassword($password->textContent);
				} else {
					fatalError("User has no password.  Check your import XML format.");
				}
				break;
		}

		$userByUsername = $userDao->getByUsername($user->getUsername(), false);
		$userByEmail = $userDao->getUserByEmail($user->getEmail(), false);
		if ($userByUsername && $userByEmail && $userByUsername->getId() == $userByEmail->getId()) {
			$user = $userByUsername;
			$userId = $user->getId();
		} elseif (!$userByUsername && !$userByEmail) {
			$userId = $userDao->insertObject($user);

			// Insert reviewing interests, now that there is a userId.
			$interestNodeList = $node->getElementsByTagNameNS($deployment->getNamespace(), 'review_interests');
			if ($interestNodeList->length == 0) {
				$n = $interestNodeList->item(0);
				if ($n) {
					$interests = preg_split('/,\s*/', $n->textContent);
					import('lib.pkp.classes.user.InterestManager');
					$interestManager = new InterestManager();
					$interestManager->setInterestsForUser($user, $interests);
				}
			}
		}

		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$userGroupsFactory = $userGroupDao->getByContextId($context->getId());
		$userGroups = $userGroupsFactory->toArray();

		// Extract user groups from the User XML and assign the user to those (existing) groups.
		// Note:  It is possible for a user to exist with no user group assignments so there is
		// no fatalError() as is the case with PKPAuthor import.
		$userGroupNodeList = $node->getElementsByTagNameNS($deployment->getNamespace(), 'user_group_ref');
		if ($userGroupNodeList->length > 0) {
			for ($i = 0 ; $i < $userGroupNodeList->length ; $i++) {
				$n = $userGroupNodeList->item($i);
				foreach ($userGroups as $userGroup) {
					if (in_array($n->textContent, $userGroup->getName(null))) {
						// Found a candidate; assign user to it.
						$userGroupDao->assignUserToGroup($userId, $userGroup->getId());
					}
				}
			}
		}
		return $user;
	}

	/**
	 * Handle a singular element import.
	 * @param $node DOMElement
	 */
	function handleElement($node) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();

		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) {
			if (is_a($n, 'DOMElement')) {
				$this->handleChildElement($n);
			}
		}

	}

	/**
	 * Handle an element whose parent is the submission element.
	 * @param $n DOMElement
	 */
	function handleChildElement($n) {
		switch ($n->tagName) {
			case 'user_group':
				$this->parseUserGroup($n);
				break;
			case 'user':
				$this->parseUser($n);
				break;
			default:
				fatalError('Unknown element ' . $n->tagName);
		}
	}
}

?>
