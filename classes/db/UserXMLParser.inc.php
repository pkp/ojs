<?php

/**
 * UserXMLParser.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package db
 *
 * Class to import and export user data from an XML format.
 * See dbscripts/xml/dtd/users.dtd for the XML schema used.
 *
 * $Id$
 */

/** Sample user datafile:
 ****************************************
	<users>
		<user>
			<username>username</username>
			<password>password</password>
			<first_name>FirstName</first_name>
			<last_name>LastName</last_name>
			<affiliation>Affiliation</affiliation>
			<email>user@pkp.ubc.ca</email>
			<role type="editor"/>
			<role type="sectionEditor"/>
		</user>
	</users>
 ****************************************
 */

import('xml.XMLParser');

class UserXMLParser {

	/** @var XMLParser the parser to use */
	var $parser;
	
	/** @var array ImportedUsers users to import */
	var $usersToImport;
	
	/** @var array ImportedUsers imported users */
	var $importedUsers;
	
	/** @var array error messages that occurred during import */
	var $errors;
	
	/** @var int the ID of the journal to import users into */
	var $journalId;

	/**
	 * Constructor.
	 * @param $journalId int assumed to be a valid journal ID
	 */
	function UserXMLParser($journalId) {
		$this->parser = &new XMLParser();
		$this->journalId = $journalId;
	}
	
	/**
	 * Parse an XML users file into a set of users to import.
	 * @param $file string path to the XML file to parse
	 * @return array ImportedUsers the collection of users read from the file
	 */
	function &parseData($file) {	
		$this->usersToImport = array();
		$tree = $this->parser->parse($file);
		
		if ($tree !== false) {
			foreach ($tree->getChildren() as $user) {
				if ($user->getName() == 'user') {
					// Match user element
					$newUser = &new ImportedUser();
					
					foreach ($user->getChildren() as $attrib) {
						switch ($attrib->getName()) {
							case 'username':
								// Usernames must be lowercase
								$newUser->setUsername(strtolower($attrib->getValue()));
								break;
							case 'password':
								$encrypted = $attrib->getAttribute('encrypted');
								if (isset($encrypted) && $encrypted == 'md5') {
									$newUser->setPassword($attrib->getValue());
								} else {
									$newUser->setUnencryptedPassword($attrib->getValue());
								}
								break;
							case 'first_name':
								$newUser->setFirstName($attrib->getValue());
								break;
							case 'middle_name':
								$newUser->setMiddleName($attrib->getValue());
								break;
							case 'last_name':
								$newUser->setLastName($attrib->getValue());
								break;
							case 'affiliation':
								$newUser->setAffiliation($attrib->getValue());
								break;
							case 'email':
								$newUser->setEmail($attrib->getValue());
								break;
							case 'phone':
								$newUser->setPhone($attrib->getValue());
								break;
							case 'fax':
								$newUser->setFax($attrib->getValue());
								break;
							case 'mailing_address':
								$newUser->setMailingAddress($attrib->getValue());
								break;
							case 'biography':
								$newUser->setBiography($attrib->getValue());
								break;
							case 'role':
								$roleType = $attrib->getAttribute('type');
								if ($this->validRole($roleType)) {
									$role = &new Role();
									$role->setRoleId(RoleDAO::getRoleIdFromPath($roleType));
									$newUser->addRole($role);
								}
								break;
						}
					}
					
					array_push($this->usersToImport, $newUser);
				}
			}
		}
		
		return $this->usersToImport;
	}
	
	/**
	 * Import the parsed users into the system.
	 * @param $sendNotify boolean send an email notification to each imported user containing their username and password
	 * @param $continueOnError boolean continue to import remaining users if a failure occurs
	 * @return boolean success
	 */
	function importUsers($sendNotify = false, $continueOnError = false) {
		$success = true;
		$this->importedUsers = array();
		$this->errors = array();
		
		$userDao = &DAORegistry::getDAO('UserDAO');
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		
		if ($sendNotify) {
			// Set up mail template to send to added users
			$mail = &new MailTemplate('USER_REGISTER');
		}
		
		for ($i=0, $count=count($this->usersToImport); $i < $count; $i++) {
			$user = &$this->usersToImport[$i];
			// If the email address already exists in the system,
			// then assign the user the username associated with that email address.
			if ($user->getEmail() != null) {
				$emailExists = $userDao->getUserByEmail($user->getEmail());
				if ($emailExists != null) {
					$user->setUsername($emailExists->getUsername());
				}
			}
			if ($user->getUsername() == null) {
				$newUsername = true;
				$this->generateUsername($user);
			} else {
				$newUsername = false;
			}
			if ($user->getUnencryptedPassword() != null) {
				$user->setPassword(Validation::encryptCredentials($user->getUsername(), $user->getUnencryptedPassword()));
			} else if ($user->getPassword() == null) {
				$this->generatePassword($user);
			}
			
			if (!$newUsername) {
				// Check if user already exists
				$userExists = $userDao->getUserByUsername($user->getUsername());
				if ($userExists != null) {
					$user->setUserId($userExists->getUserId());
				}
			} else {
				$userExists = false;
			}
			
			if ($newUsername || !$userExists) {
				// Create new user account
				// If the user's username was specified in the data file and
				// the username already exists, only the new roles are added for that user
				if (!$userDao->insertUser($user)) {
					// Failed to add user!
					$this->errors[] = sprintf('%s: %s (%s)',
						Locale::translate('manager.people.importUsers.failedToImportUser'),
						$user->getFullName(), $user->getUsername());
						
					if ($continueOnError) {
						// Skip to next user
						$success = false;
						continue;
					} else {
						return false;
					}
				}
			}
			
			// Enroll user in specified roles
			// If the user is already enrolled in a role, that role is skipped
			foreach ($user->getRoles() as $role) {
				$role->setUserId($user->getUserId());
				$role->setJournalId($this->journalId);
				if (!$roleDao->roleExists($role->getJournalId(), $role->getUserId(), $role->getRoleId())) {
					if (!$roleDao->insertRole($role)) {
						// Failed to add role!
						$this->errors[] = sprintf('%s: %s - %s (%s)',
							Locale::translate('manager.people.importUsers.failedToImportRole'),
							$role->getRoleName(),
							$user->getFullName(), $user->getUsername());
							
						if ($continueOnError) {
							// Continue to insert other roles for this user
							$success = false;
							continue;
						} else {
							return false;
						}
					}
				}
			}
			
			if ($sendNotify && !$userExists) {
				// Send email notification to user as if user just registered themselves			
				$mail->addRecipient($user->getEmail(), $user->getFullName());
				$mail->sendWithParams(array(
					'username' => $user->getUsername(),
					'password' => $user->getUnencryptedPassword() ==  null ? '-' : $user->getUnencryptedPassword()
				));
				$mail->clearRecipients();
			}
			
			array_push($this->importedUsers, $user);
		}
		
		return $success;
	}
	
	/**
	 * Return the set of parsed users.
	 * @return array ImportedUsers
	 */
	function &getUsersToImport() {
		return $this->usersToImport;
	}
	
	/**
	 * Specify the set of parsed users.
	 * @param $usersToImport ImportedUsers
	 */
	function &setUsersToImport($users) {
		$this->usersToImport = $users;
	}
	
	/**
	 * Return the set of users who were successfully imported.
	 * @return array ImportedUsers
	 */
	function &getImportedUsers() {
		return $this->importedUsers;
	}
	
	/**
	 * Return an array of error messages that occurred during the import.
	 * @return array string
	 */
	function &getErrors() {
		return $this->errors;
	}
	
	/**
	 * Check if a role type value identifies a valid role that can be imported.
	 * Note we do not allow users to be imported into the "admin" role.
	 * @param $roleType string
	 * @return boolean
	 */
	function validRole($roleType) {
		return isset($roleType) && in_array($roleType, array('manager', 'editor', 'sectionEditor', 'layoutEditor', 'reviewer', 'copyeditor', 'proofreader', 'author', 'reader'));
	}
	
	/**
	 * Generate a unique username for a user based on the user's name.
	 * @param $user ImportedUser the user to be modified by this function
	 */
	function generateUsername(&$user) {
		$userDao = &DAORegistry::getDAO('UserDAO');
		$baseUsername = String::regexp_replace('/[^A-Z0-9]/i', '', $user->getLastName());
		if (empty($baseUsername)) {
			$baseUsername = String::regexp_replace('/[^A-Z0-9]/i', '', $user->getFirstName());
		}
		if (empty($username)) {
			// Default username if we can't use the user's last or first name
			$baseUsername = 'user';
		}
		
		for ($username = $baseUsername, $i=1; $userDao->userExistsByUsername($username); $i++) {
			$username = $baseUsername . $i;
		}
		$user->setUsername($username);
	}
	
	/**
	 * Generate a random password for a user.
	 * @param $user ImportedUser the user to be modified by this function
	 */
	function generatePassword(&$user) {
		$password = Validation::generatePassword();
		$user->setUnencryptedPassword($password);
		$user->setPassword(Validation::encryptCredentials($user->getUsername(), $password));
	}
	
}


/**
 * Helper class representing a user imported from a user data file.
 */
class ImportedUser extends User {

	/** @var array Roles of this user */
	var $roles;
	
	/**
	 * Constructor.
	 */
	function ImportedUser() {
		$this->roles = array();
		parent::User();
	}
	
	/**
	 * Set the unencrypted form of the user's password.
	 * @param $unencryptedPassword string
	 */
	function setUnencryptedPassword($unencryptedPassword) {
		$this->setData('unencryptedPassword', $unencryptedPassword);	
	}
	
	/**
	 * Get the user's unencrypted password.
	 * @return string
	 */
	function getUnencryptedPassword() {
		return $this->getData('unencryptedPassword');
	}
	
	/**
	 * Add a new role to this user.
	 * @param $role Role
	 */
	function addRole(&$role) {
		array_push($this->roles, $role);
	}
	
	/**
	 * Get this user's roles.
	 * @return array Roles
	 */
	function &getRoles() {
		return $this->roles;
	}
	
}

?>
