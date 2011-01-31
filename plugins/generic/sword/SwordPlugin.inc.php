<?php

/**
 * @file plugins/generic/sword/SwordPlugin.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SwordPlugin
 * @ingroup plugins_generic_sword
 *
 * @brief SWORD deposit plugin class
 */

// $Id$


define('SWORD_DEPOSIT_TYPE_AUTOMATIC',		1);
define('SWORD_DEPOSIT_TYPE_OPTIONAL_SELECTION',	2);
define('SWORD_DEPOSIT_TYPE_OPTIONAL_FIXED',	3);
define('SWORD_DEPOSIT_TYPE_MANAGER',		4);

import('lib.pkp.classes.plugins.GenericPlugin');

class SwordPlugin extends GenericPlugin {
	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return Locale::translate('plugins.generic.sword.displayName');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return Locale::translate('plugins.generic.sword.description');
	}

	function register($category, $path) {
		if (parent::register($category, $path)) {
			HookRegistry::register('PluginRegistry::loadCategory', array(&$this, 'callbackLoadCategory'));
			if ($this->getEnabled()) {
				HookRegistry::register('LoadHandler', array(&$this, 'callbackLoadHandler'));
				HookRegistry::register('SectionEditorAction::emailEditorDecisionComment', array(&$this, 'callbackAuthorDeposits'));
			}
			$this->addLocaleData();
			return true;
		}
		return false;
	}

	/**
	 * Check whether or not this plugin is enabled
	 * @return boolean
	 */
	function getEnabled() {
		$journal =& Request::getJournal();
		$journalId = $journal?$journal->getId():0;
		return $this->getSetting($journalId, 'enabled');
	}

	/**
	 * Register as a block plugin, even though this is a generic plugin.
	 * This will allow the plugin to behave as a block plugin, i.e. to
	 * have layout tasks performed on it.
	 * @param $hookName string
	 * @param $args array
	 */
	function callbackLoadCategory($hookName, $args) {
		$category =& $args[0];
		$plugins =& $args[1];
		switch ($category) {
			case 'importexport':
				// This plugin is only available on PHP5.x.
				if (!checkPhpVersion('5.0.0')) break;
				$this->import('SwordImportExportPlugin');
				$importExportPlugin = new SwordImportExportPlugin($this->getName());
				$plugins[$importExportPlugin->getSeq()][$importExportPlugin->getPluginPath()] =& $importExportPlugin;
				break;
		}
		return false;
	}

	/**
	 * Hook registry function that is called to display the sword deposit page for authors.
	 * @param $hookName string
	 * @param $args array
	 */
	function callbackLoadHandler($hookName, $args) {
		$page =& $args[0];
		if ($page === 'sword') {
			define('HANDLER_CLASS', 'SwordHandler');
			define('SWORD_PLUGIN_NAME', $this->getName());
			$handlerFile =& $args[2];
			$handlerFile = $this->getPluginPath() . '/' . 'SwordHandler.inc.php';
		}
	}

	/**
	 * Hook registry function that is called when it's time to perform all automatic
	 * deposits and notify the author of optional deposits.
	 * @param $hookName string
	 * @param $args array
	 */
	function callbackAuthorDeposits($hookName, $args) {
		$sectionEditorSubmission =& $args[0];

		// Determine if the most recent decision was an "Accept"
		$decisions = $sectionEditorSubmission->getDecisions();
		$decisions = array_pop($decisions); // Rounds
		$decision = array_pop($decisions);
		$decisionConst = $decision?$decision['decision']:null;
		if ($decisionConst != SUBMISSION_EDITOR_DECISION_ACCEPT) return false;

		// The most recent decision was an "Accept"; perform auto deposits.
		$journal =& Request::getJournal();
		$depositPoints = $this->getSetting($journal->getId(), 'depositPoints');
		import('classes.sword.OJSSwordDeposit');

		import('lib.pkp.classes.notification.NotificationManager');
		$notificationManager = new NotificationManager();

		$sendDepositNotification = $this->getSetting($journal->getId(), 'allowAuthorSpecify') ? true : false;

		foreach ($depositPoints as $depositPoint) {
			$depositType = $depositPoint['type'];

			if ($depositType == SWORD_DEPOSIT_TYPE_OPTIONAL_SELECTION || $depositType == SWORD_DEPOSIT_TYPE_OPTIONAL_FIXED) $sendDepositNotification = true;
			if ($depositType != SWORD_DEPOSIT_TYPE_AUTOMATIC) continue;

			// For each automatic deposit point, perform a deposit.
			$deposit = new OJSSwordDeposit($sectionEditorSubmission);
			$deposit->setMetadata();
			$deposit->addEditorial();
			$deposit->createPackage();
			$deposit->deposit(
				$depositPoint['url'],
				$depositPoint['username'],
				$depositPoint['password']
			);
			$deposit->cleanup();
			unset($deposit);

			$notificationManager->createTrivialNotification(Locale::translate('notification.notification'), Locale::translate('plugins.generic.sword.automaticDepositComplete', array('itemTitle' => $sectionEditorSubmission->getLocalizedTitle(), 'repositoryName' => $depositPoint['name'])), NOTIFICATION_TYPE_SUCCESS, null, false);
		}

		if ($sendDepositNotification) {
			$submittingUser =& $sectionEditorSubmission->getUser();

			import('classes.mail.ArticleMailTemplate');
			$contactName = $journal->getSetting('contactName');
			$contactEmail = $journal->getSetting('contactEmail');
			$mail = new ArticleMailTemplate($sectionEditorSubmission, 'SWORD_DEPOSIT_NOTIFICATION', null, null, $journal, true, true);
			$mail->setFrom($contactEmail, $contactName);
			$mail->addRecipient($submittingUser->getEmail(), $submittingUser->getFullName());

			$mail->assignParams(array(
				'journalName' => $journal->getLocalizedTitle(),
				'articleTitle' => $sectionEditorSubmission->getLocalizedTitle(),
				'swordDepositUrl' => Request::url(
					null, 'sword', 'index', $sectionEditorSubmission->getId()
				)
			));

			$mail->send();
		}

		return false;
	}

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array(
				'disable',
				Locale::translate('manager.plugins.disable')
			);
			$verbs[] = array(
				'settings',
				Locale::translate('plugins.generic.sword.settings')
			);
		} else {
			$verbs[] = array(
				'enable',
				Locale::translate('manager.plugins.enable')
			);
		}
		return $verbs;
	}

 	/*
 	 * Execute a management verb on this plugin
 	 * @param $verb string
 	 * @param $args array
	 * @param $message string Location for the plugin to put a result msg
 	 * @return boolean
 	 */
	function manage($verb, $args, &$message) {
		$returner = true;
		$journal =& Request::getJournal();
		$this->addLocaleData();

		switch ($verb) {
			case 'settings':
				Locale::requireComponents(array(LOCALE_COMPONENT_APPLICATION_COMMON,  LOCALE_COMPONENT_PKP_MANAGER));
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));

				$this->import('SettingsForm');
				$form = new SettingsForm($this, $journal->getId());

				if (Request::getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						Request::redirect(null, null, 'plugins');
					} else {
						$form->display();
					}
				} else {
					$form->initData();
					$form->display();
				}
				break;
			case 'enable':
				$this->updateSetting($journal->getId(), 'enabled', true);
				$message = Locale::translate('plugins.generic.sword.enabled');
				$returner = false;
				break;
			case 'disable':
				$this->updateSetting($journal->getId(), 'enabled', false);
				$message = Locale::translate('plugins.generic.sword.disabled');
				$returner = false;
				break;
			case 'createDepositPoint':
			case 'editDepositPoint':
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));

				$depositPointId = array_shift($args);
				if ($depositPointId == '') $depositPointId = null;
				else $depositPointId = (int) $depositPointId;
				$this->import('DepositPointForm');
				$form = new DepositPointForm($this, $journal->getId(), $depositPointId);

				if (Request::getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						Request::redirect(null, null, null, array('generic', $this->getName(), 'settings'));
					} else {
						$form->display();
					}
				} else {
					$form->initData();
					$form->display();
				}
				break;
			case 'deleteDepositPoint':
				$journalId = $journal->getId();
				$depositPointId = (int) array_shift($args);
				$depositPoints = $this->getSetting($journalId, 'depositPoints');
				unset($depositPoints[$depositPointId]);
				$this->updateSetting($journalId, 'depositPoints', $depositPoints);
				Request::redirect(null, null, null, array('generic', 'SwordPlugin', 'settings'));
				break;
		}

		return $returner;
	}

	function getTypeMap() {
		return array(
			SWORD_DEPOSIT_TYPE_AUTOMATIC => 'plugins.generic.sword.depositPoints.type.automatic',
			SWORD_DEPOSIT_TYPE_OPTIONAL_SELECTION => 'plugins.generic.sword.depositPoints.type.optionalSelection',
			SWORD_DEPOSIT_TYPE_OPTIONAL_FIXED => 'plugins.generic.sword.depositPoints.type.optionalFixed',
			SWORD_DEPOSIT_TYPE_MANAGER => 'plugins.generic.sword.depositPoints.type.manager'
		);
	}

	function getInstallEmailTemplatesFile() {
		return ($this->getPluginPath() . DIRECTORY_SEPARATOR . 'emailTemplates.xml');
	}

	function getInstallEmailTemplateDataFile() {
		return ($this->getPluginPath() . '/locale/{$installedLocale}/emailTemplates.xml');
	}
}

?>
