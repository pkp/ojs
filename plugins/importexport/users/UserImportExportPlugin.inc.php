<?php

/**
 * @file plugins/importexport/users/UserImportExportPlugin.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserImportExportPlugin
 * @ingroup plugins_importexport_users
 *
 * @brief Users import/export plugin
 */

import('classes.plugins.ImportExportPlugin');

import('lib.pkp.classes.xml.XMLCustomWriter');

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
		return __('plugins.importexport.users.displayName');
	}

	function getDescription() {
		return __('plugins.importexport.users.description');
	}

	function display(&$args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		parent::display($args, $request);

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

		$roleDao = DAORegistry::getDAO('RoleDAO');

		$journal = $request->getJournal();
		set_time_limit(0);
		switch (array_shift($args)) {
			case 'confirm':
				$this->import('UserXMLParser');

				$sendNotify = (bool) $request->getUserVar('sendNotify');
				$continueOnError = (bool) $request->getUserVar('continueOnError');

				import('lib.pkp.classes.file.FileManager');
				$fileManager = new FileManager();
				if (($userFile = $fileManager->getUploadedFilePath('userFile')) !== false) {
					// Import the uploaded file
					$parser = new UserXMLParser($journal->getId());
					$users =& $parser->parseData($userFile);

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
				$roleDao = DAORegistry::getDAO('RoleDAO');
				$userKeys = $request->getUserVar('userKeys');
				if (!is_array($userKeys)) $userKeys = array();
				$sendNotify = (bool) $request->getUserVar('sendNotify');
				$continueOnError = (bool) $request->getUserVar('continueOnError');

				$users = array();
				foreach ($userKeys as $i) {
					$newUser = new ImportedUser();
					$newUser->setFirstName($request->getUserVar($i.'_firstName'));
					$newUser->setMiddleName($request->getUserVar($i.'_middleName'));
					$newUser->setLastName($request->getUserVar($i.'_lastName'));
					$newUser->setUsername($request->getUserVar($i.'_username'));
					$newUser->setEmail($request->getUserVar($i.'_email'));

					$locales = array();
					if ($request->getUserVar($i.'_locales') != null || is_array($request->getUserVar($i.'_locales'))) {
						foreach ($request->getUserVar($i.'_locales') as $locale) {
							array_push($locales, $locale);
						}
					}
					$newUser->setLocales($locales);
					$newUser->setSignature($request->getUserVar($i.'_signature'), null);
					$newUser->setBiography($request->getUserVar($i.'_biography'), null);
					$newUser->setTemporaryInterests($request->getUserVar($i.'_interests'));
					$newUser->setGossip($request->getUserVar($i.'_gossip'), null);
					$newUser->setCountry($request->getUserVar($i.'_country'));
					$newUser->setMailingAddress($request->getUserVar($i.'_mailingAddress'));
					$newUser->setFax($request->getUserVar($i.'_fax'));
					$newUser->setPhone($request->getUserVar($i.'_phone'));
					$newUser->setUrl($request->getUserVar($i.'_url'));
					$newUser->setAffiliation($request->getUserVar($i.'_affiliation'), null);
					$newUser->setGender($request->getUserVar($i.'_gender'));
					$newUser->setInitials($request->getUserVar($i.'_initials'));
					$newUser->setSalutation($request->getUserVar($i.'_salutation'));
					$newUser->setPassword($request->getUserVar($i.'_password'));
					$newUser->setMustChangePassword($request->getUserVar($i.'_mustChangePassword'));
					$newUser->setUnencryptedPassword($request->getUserVar($i.'_unencryptedPassword'));

					$newUserRoles = $request->getUserVar($i.'_roles');
					if (is_array($newUserRoles) && count($newUserRoles) > 0) {
						foreach ($newUserRoles as $newUserRole) {
							if ($newUserRole != '') {
								$role = new Role();
								$role->setRoleId($roleDao->getRoleIdFromPath($newUserRole));
								$newUser->addRole($role);
							}
						}
					}
					array_push($users, $newUser);
				}

				$parser = new UserXMLParser($journal->getId());
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
				$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
				$users = $userGroupDao->getUsersByContextId($journal->getId());
				$users = $users->toArray();
				$userExportDom = new UserExportDom();
				$doc = $userExportDom->exportUsers($journal, $users);
				header("Content-Type: application/xml");
				header("Cache-Control: private");
				header("Content-Disposition: attachment; filename=\"users.xml\"");
				echo XMLCustomWriter::getXML($doc);
				break;
			case 'exportByRole':
				$this->import('UserExportDom');
				$users = array();
				$rolePaths = array();
				foreach ($request->getUserVar('roles') as $rolePath) {
					$roleId = $roleDao->getRoleIdFromPath($rolePath);
					$thisRoleUsers = $roleDao->getUsersByRoleId($roleId, $journal->getId());
					foreach ($thisRoleUsers->toArray() as $user) {
						$users[$user->getId()] = $user;
					}
					$rolePaths[] = $rolePath;
				}
				$users = array_values($users);
				$userExportDom = new UserExportDom();
				$doc =& $userExportDom->exportUsers($journal, $users, $rolePaths);
				header("Content-Type: application/xml");
				header("Cache-Control: private");
				header("Content-Disposition: attachment; filename=\"users.xml\"");
				echo XMLCustomWriter::getXML($doc);
				break;
			default:
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
		$flags =& $args;

		$journalDao = DAORegistry::getDAO('JournalDAO');
		$userDao = DAORegistry::getDAO('UserDAO');

		$journal = $journalDao->getByPath($journalPath);

		if (!$journal) {
			if ($journalPath != '') {
				echo __('plugins.importexport.users.import.errorsOccurred') . ":\n";
				echo __('plugins.importexport.users.unknownJournal', array('journalPath' => $journalPath)) . "\n\n";
			}
			$this->usage($scriptName);
			return;
		}
		switch ($command) {
			case 'import':
				$this->import('UserXMLParser');

				$sendNotify = in_array('send_notify', $flags);
				$continueOnError = in_array('continue_on_error', $flags);

				import('lib.pkp.classes.file.FileManager');

				// Import the uploaded file
				$parser = new UserXMLParser($journal->getId());
				$users =& $parser->parseData($xmlFile);

				if (!$parser->importUsers($sendNotify, $continueOnError)) {
					// Failure.
					echo __('plugins.importexport.users.import.errorsOccurred') . ":\n";
					foreach ($parser->getErrors() as $error) {
						echo "\t$error\n";
					}
					return false;
				}

				// Success.
				echo __('plugins.importexport.users.import.usersWereImported') . ":\n";
				foreach ($parser->getImportedUsers() as $user) {
					echo "\t" . $user->getUserName() . "\n";
				}

				return true;
				break;
			case 'export':
				$this->import('UserExportDom');
				$roleDao = DAORegistry::getDAO('RoleDAO');
				$rolePaths = null;
				$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
				if (empty($args)) {
					$users = $userGroupDao->getUsersByContextId($journal->getId());
					$users = $users->toArray();
				} else {
					$users = array();
					$rolePaths = array();
					foreach ($args as $rolePath) {
						$roleId = $roleDao->getRoleIdFromPath($rolePath);
						$thisRoleUsers = $roleDao->getUsersByRoleId($roleId, $journal->getId());
						foreach ($thisRoleUsers->toArray() as $user) {
							$users[$user->getId()] = $user;
						}
						$rolePaths[] = $rolePath;
					}
					$users = array_values($users);
				}
				$userExportDom = new UserExportDom();
				$doc =& $userExportDom->exportUsers($journal, $users, $rolePaths);
				if (($h = fopen($xmlFile, 'wb'))===false) {
					echo __('plugins.importexport.users.export.errorsOccurred') . ":\n";
					echo __('plugins.importexport.users.export.couldNotWriteFile', array('fileName' => $xmlFile)) . "\n";
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
		echo __('plugins.importexport.users.cliUsage', array(
			'scriptName' => $scriptName,
			'pluginName' => $this->getName()
		)) . "\n";
	}
}

?>
