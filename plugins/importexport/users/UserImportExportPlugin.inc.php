<?php

/**
 * @file UserImportExportPlugin.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserImportExportPlugin
 * @ingroup plugins_importexport_users
 *
 * @brief Users import/export plugin
 */

// $Id$


import('classes.plugins.ImportExportPlugin');

import('xml.XMLCustomWriter');

class UserImportExportPlugin extends ImportExportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'UserImportExportPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.importexport.users.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.importexport.users.description');
	}

	function display(&$args) {
		$templateMgr = &TemplateManager::getManager();
		parent::display($args);

		$templateMgr->assign('roleOptions', array(
			'' => 'manager.people.doNotEnroll',
			'manager' => 'user.role.manager',
			'editor' => 'user.role.editor',
			'sectionEditor' => 'user.role.sectionEditor',
			'layoutEditor' => 'user.role.layoutEditor',
			'reviewer' => 'user.role.reviewer',
			'copyeditor' => 'user.role.copyeditor',
			'proofreader' => 'user.role.proofreader',
			'author' => 'user.role.author',
			'reader' => 'user.role.reader',
			'subscriptionManager' => 'user.role.subscriptionManager'
		));

		$roleDao = &DAORegistry::getDAO('RoleDAO');

		$journal = &Request::getJournal();
		switch (array_shift($args)) {
			case 'confirm':
				$this->import('UserXMLParser');
				$templateMgr->assign('helpTopicId', 'journal.users.importUsers');

				$sendNotify = (bool) Request::getUserVar('sendNotify');
				$continueOnError = (bool) Request::getUserVar('continueOnError');

				import('file.FileManager');
				if (($userFile = FileManager::getUploadedFilePath('userFile')) !== false) {
					// Import the uploaded file
					$parser = &new UserXMLParser($journal->getJournalId());
					$users = &$parser->parseData($userFile);

					$i = 0;
					$usersRoles = array();
					foreach ($users as $user) {
						$usersRoles[$i] = array();
						foreach ($user->getRoles() as $role) {
							array_push($usersRoles[$i], $role->getRoleName());
						}
						$i++;
					}

					$templateMgr->assign_by_ref('users', $users);
					$templateMgr->assign_by_ref('usersRoles', $usersRoles);
					$templateMgr->assign('sendNotify', $sendNotify);
					$templateMgr->assign('continueOnError', $continueOnError);
					$templateMgr->assign('errors', $parser->errors);

					// Show confirmation form
					$templateMgr->display($this->getTemplatePath() . 'importUsersConfirm.tpl');
				}
				break;
			case 'import':
				$this->import('UserXMLParser');
				$userKeys = Request::getUserVar('userKeys');
				if (!is_array($userKeys)) $userKeys = array();
				$sendNotify = (bool) Request::getUserVar('sendNotify');
				$continueOnError = (bool) Request::getUserVar('continueOnError');

				$users = array();
				foreach ($userKeys as $i) {
					$newUser = &new ImportedUser();
					$newUser->setFirstName(Request::getUserVar($i.'_firstName'));
					$newUser->setMiddleName(Request::getUserVar($i.'_middleName'));
					$newUser->setLastName(Request::getUserVar($i.'_lastName'));
					$newUser->setUsername(Request::getUserVar($i.'_username'));
					$newUser->setEmail(Request::getUserVar($i.'_email'));

					$locales = array();
					if (Request::getUserVar($i.'_locales') != null || is_array(Request::getUserVar($i.'_locales'))) {
						foreach (Request::getUserVar($i.'_locales') as $locale) {
							array_push($locales, $locale);
						}
					}
					$newUser->setLocales($locales);
					$newUser->setSignature(Request::getUserVar($i.'_signature'), null);
					$newUser->setBiography(Request::getUserVar($i.'_biography'), null);
					$newUser->setInterests(Request::getUserVar($i.'_interests'), null);
					$newUser->setCountry(Request::getUserVar($i.'_country'));
					$newUser->setMailingAddress(Request::getUserVar($i.'_mailingAddress'));
					$newUser->setFax(Request::getUserVar($i.'_fax'));
					$newUser->setPhone(Request::getUserVar($i.'_phone'));
					$newUser->setUrl(Request::getUserVar($i.'_url'));
					$newUser->setAffiliation(Request::getUserVar($i.'_affiliation'));
					$newUser->setGender(Request::getUserVar($i.'_gender'));
					$newUser->setInitials(Request::getUserVar($i.'_initials'));
					$newUser->setSalutation(Request::getUserVar($i.'_salutation'));
					$newUser->setPassword(Request::getUserVar($i.'_password'));
					$newUser->setMustChangePassword(Request::getUserVar($i.'_mustChangePassword'));
					$newUser->setUnencryptedPassword(Request::getUserVar($i.'_unencryptedPassword'));

					$newUserRoles = Request::getUserVar($i.'_roles');
					if (is_array($newUserRoles) && count($newUserRoles) > 0) {
						foreach ($newUserRoles as $newUserRole) {
							if ($newUserRole != '') {
								$role = &new Role();
								$role->setRoleId(RoleDAO::getRoleIdFromPath($newUserRole));
								$newUser->AddRole($role);
							}
						}
					}
					array_push($users, $newUser);
				}

				$parser = &new UserXMLParser($journal->getJournalId());
				$parser->setUsersToImport($users);
				if (!$parser->importUsers($sendNotify, $continueOnError)) {
					// Failures occurred
					$templateMgr->assign('isError', true);
					$templateMgr->assign('errors', $parser->getErrors());
				}
				$templateMgr->assign('importedUsers', $parser->getImportedUsers());
				$templateMgr->display($this->getTemplatePath() . 'importUsersResults.tpl');
				break;
			case 'exportAll':
				$this->import('UserExportDom');
				$users = &$roleDao->getUsersByJournalId($journal->getJournalId());
				$users = &$users->toArray();
				$doc = &UserExportDom::exportUsers($journal, $users);
				header("Content-Type: application/xml");
				header("Cache-Control: private");
				header("Content-Disposition: attachment; filename=\"users.xml\"");
				echo XMLCustomWriter::getXML($doc);
				break;
			case 'exportByRole':
				$this->import('UserExportDom');
				$users = array();
				$rolePaths = array();
				foreach (Request::getUserVar('roles') as $rolePath) {
					$roleId = $roleDao->getRoleIdFromPath($rolePath);
					$thisRoleUsers = &$roleDao->getUsersByRoleId($roleId, $journal->getJournalId());
					foreach ($thisRoleUsers->toArray() as $user) {
						$users[$user->getUserId()] = $user;
					}
					$rolePaths[] = $rolePath;
				}
				$users = array_values($users);
				$doc = &UserExportDom::exportUsers($journal, $users, $rolePaths);
				header("Content-Type: application/xml");
				header("Cache-Control: private");
				header("Content-Disposition: attachment; filename=\"users.xml\"");
				echo XMLCustomWriter::getXML($doc);
				break;
			default:
				$this->setBreadcrumbs();
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
		}
	}

	/**
	 * Execute import/export tasks using the command-line interface.
	 * @param $args Parameters to the plugin
	 */ 
	function executeCLI($scriptName, &$args) {
		$command = array_shift($args);
		$xmlFile = array_shift($args);
		$journalPath = array_shift($args);
		$flags = &$args;

		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');

		$journal = &$journalDao->getJournalByPath($journalPath);

		if (!$journal) {
			if ($journalPath != '') {
				echo Locale::translate('plugins.importexport.users.import.errorsOccurred') . ":\n";
				echo Locale::translate('plugins.importexport.users.unknownJournal', array('journalPath' => $journalPath)) . "\n\n";
			}
			$this->usage($scriptName);
			return;
		}
		switch ($command) {
			case 'import':
				$this->import('UserXMLParser');

				$sendNotify = in_array('send_notify', $flags);
				$continueOnError = in_array('continue_on_error', $flags);

				import('file.FileManager');

				// Import the uploaded file
				$parser = &new UserXMLParser($journal->getJournalId());
				$users = &$parser->parseData($xmlFile);

				if (!$parser->importUsers($sendNotify, $continueOnError)) {
					// Failure.
					echo Locale::translate('plugins.importexport.users.import.errorsOccurred') . ":\n";
					foreach ($parser->getErrors() as $error) {
						echo "\t$error\n";
					}
					return false;
				}

				// Success.
				echo Locale::translate('plugins.importexport.users.import.usersWereImported') . ":\n";
				foreach ($parser->getImportedUsers() as $user) {
					echo "\t" . $user->getUserName() . "\n";
				}

				return true;
				break;
			case 'export':
				$this->import('UserExportDom');
				$roleDao = &DAORegistry::getDAO('RoleDAO');
				$rolePaths = null;
				if (empty($args)) {
					$users = &$roleDao->getUsersByJournalId($journal->getJournalId());
					$users = &$users->toArray();
				} else {
					$users = array();
					$rolePaths = array();
					foreach ($args as $rolePath) {
						$roleId = $roleDao->getRoleIdFromPath($rolePath);
						$thisRoleUsers = &$roleDao->getUsersByRoleId($roleId, $journal->getJournalId());
						foreach ($thisRoleUsers->toArray() as $user) {
							$users[$user->getUserId()] = $user;
						}
						$rolePaths[] = $rolePath;
					}
					$users = array_values($users);
				}
				$doc = &UserExportDom::exportUsers($journal, $users, $rolePaths);
				if (($h = fopen($xmlFile, 'wb'))===false) {
					echo Locale::translate('plugins.importexport.users.export.errorsOccurred') . ":\n";
					echo Locale::translate('plugins.importexport.users.export.couldNotWriteFile', array('fileName' => $xmlFile)) . "\n";
					return false;
				}
				fwrite($h, XMLCustomWriter::getXML($doc));
				fclose($h);
				return true;
		}
		$this->usage($scriptName);
	}

	/**
	 * Display the command-line usage information
	 */
	function usage($scriptName) {
		echo Locale::translate('plugins.importexport.users.cliUsage', array(
			'scriptName' => $scriptName,
			'pluginName' => $this->getName()
		)) . "\n";
	}
}

?>
