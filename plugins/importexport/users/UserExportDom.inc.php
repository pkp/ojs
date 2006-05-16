<?php

/**
 * UserExportDom.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * User plugin DOM functions for export
 *
 * $Id$
 */

import('xml.XMLCustomWriter');

define('USERS_DTD_URL', 'http://pkp.sfu.ca/ojs/dtds/users.dtd');
define('USERS_DTD_ID', '-//PKP/OJS Users XML//EN');

class UserExportDom {
	function &exportUsers(&$journal, &$users, $allowedRoles = null) {
		$roleDao = &DAORegistry::getDAO('RoleDAO');

		$doc = &XMLCustomWriter::createDocument('issue', USERS_DTD_ID, USERS_DTD_URL);
		$root = &XMLCustomWriter::createElement($doc, 'users');

		foreach ($users as $user) {
			$userNode = &XMLCustomWriter::createElement($doc, 'user');

			XMLCustomWriter::createChildWithText($doc, $userNode, 'username', $user->getUserName(), false);
			$passwordNode = XMLCustomWriter::createChildWithText($doc, $userNode, 'password', $user->getPassword());
			XMLCustomWriter::setAttribute($passwordNode, 'encrypted', 'md5');
			XMLCustomWriter::createChildWithText($doc, $userNode, 'first_name', $user->getFirstName());
			XMLCustomWriter::createChildWithText($doc, $userNode, 'middle_name', $user->getMiddleName(), false);
			XMLCustomWriter::createChildWithText($doc, $userNode, 'last_name', $user->getLastName());
			XMLCustomWriter::createChildWithText($doc, $userNode, 'affiliation', $user->getAffiliation(), false);
			XMLCustomWriter::createChildWithText($doc, $userNode, 'email', $user->getEmail());
			XMLCustomWriter::createChildWithText($doc, $userNode, 'url', $user->getUrl(), false);
			XMLCustomWriter::createChildWithText($doc, $userNode, 'phone', $user->getPhone(), false);
			XMLCustomWriter::createChildWithText($doc, $userNode, 'fax', $user->getFax(), false);
			XMLCustomWriter::createChildWithText($doc, $userNode, 'mailing_address', $user->getMailingAddress(), false);
			XMLCustomWriter::createChildWithText($doc, $userNode, 'biography', strip_tags($user->getBiography()), false);

			$roles = &$roleDao->getRolesByUserId($user->getUserId(), $journal->getJournalId());
			foreach ($roles as $role) {
				$rolePath = $role->getRolePath();
				if ($allowedRoles !== null && !in_array($rolePath, $allowedRoles)) {
					continue;
				}
				$roleNode = &XMLCustomWriter::createElement($doc, 'role');
				XMLCustomWriter::setAttribute($roleNode, 'type', $rolePath);
				XMLCustomWriter::appendChild($userNode, $roleNode);
			}

			XMLCustomWriter::appendChild($root, $userNode);
		}

		XMLCustomWriter::appendChild($doc, $root);

		return $doc;
	}
}

?>
