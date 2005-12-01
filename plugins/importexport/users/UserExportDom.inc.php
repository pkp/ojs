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

import('xml.XMLWriter');

class UserExportDom {
	function &exportUsers(&$journal, &$users, $allowedRoles = null) {
		$roleDao = &DAORegistry::getDAO('RoleDAO');

		$doc = &XMLWriter::createDocument('issue', 'users.dtd');
		$root = &XMLWriter::createElement($doc, 'users');

		foreach ($users as $user) {
			$userNode = &XMLWriter::createElement($doc, 'user');

			XMLWriter::createChildWithText($doc, $userNode, 'username', $user->getUserName(), false);
			$passwordNode = XMLWriter::createChildWithText($doc, $userNode, 'password', $user->getPassword());
			XMLWriter::setAttribute($passwordNode, 'encrypted', 'md5');
			XMLWriter::createChildWithText($doc, $userNode, 'first_name', $user->getFirstName());
			XMLWriter::createChildWithText($doc, $userNode, 'middle_name', $user->getMiddleName(), false);
			XMLWriter::createChildWithText($doc, $userNode, 'last_name', $user->getLastName());
			XMLWriter::createChildWithText($doc, $userNode, 'affiliation', $user->getAffiliation(), false);
			XMLWriter::createChildWithText($doc, $userNode, 'email', $user->getEmail());
			XMLWriter::createChildWithText($doc, $userNode, 'phone', $user->getPhone(), false);
			XMLWriter::createChildWithText($doc, $userNode, 'fax', $user->getFax(), false);
			XMLWriter::createChildWithText($doc, $userNode, 'mailing_address', $user->getMailingAddress(), false);
			XMLWriter::createChildWithText($doc, $userNode, 'biography', strip_tags($user->getBiography()), false);

			$roles = &$roleDao->getRolesByUserId($user->getUserId(), $journal->getJournalId());
			foreach ($roles as $role) {
				$rolePath = $role->getRolePath();
				if ($allowedRoles !== null && !in_array($rolePath, $allowedRoles)) {
					continue;
				}
				$roleNode = &XMLWriter::createElement($doc, 'role');
				XMLWriter::setAttribute($roleNode, 'type', $rolePath);
				XMLWriter::appendChild($userNode, $roleNode);
			}

			XMLWriter::appendChild($root, $userNode);
		}

		XMLWriter::appendChild($doc, $root);

		return $doc;
	}
}

?>
