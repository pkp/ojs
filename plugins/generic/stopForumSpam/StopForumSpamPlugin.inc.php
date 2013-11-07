<?php

/**
 * @file plugins/generic/stopForumSpam/StopForumSpamPlugin.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class StopForumSpamPlugin
 * @ingroup plugins_generic_stopForumSpam
 *
 * @brief Stop Forum Spam plugin class
 */

define('STOP_FORUM_SPAM_API_ENDPOINT', 'http://www.stopforumspam.com/api?');

import('lib.pkp.classes.plugins.GenericPlugin');

class StopForumSpamPlugin extends GenericPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;
		if ($success && $this->getEnabled()) {
			// Hook for validate in registration form
			HookRegistry::register('registrationform::validate', array($this, 'validateExecute'));
		}
		return $success;
	}

	function getDisplayName() {
		return __('plugins.generic.stopForumSpam.displayName');
	}

	function getDescription() {
		return __('plugins.generic.stopForumSpam.description');
	}

	/**
	 * Extend the {url ...} smarty to support this plugin.
	 */
	function smartyPluginUrl($params, &$smarty) {
		$path = array($this->getCategory(), $this->getName());
		if (is_array($params['path'])) {
			$params['path'] = array_merge($path, $params['path']);
		} elseif (!empty($params['path'])) {
			$params['path'] = array_merge($path, array($params['path']));
		} else {
			$params['path'] = $path;
		}

		if (!empty($params['id'])) {
			$params['path'] = array_merge($params['path'], array($params['id']));
			unset($params['id']);
		}
		return $smarty->smartyUrl($params, $smarty);
	}

	/**
	 * Set the page's breadcrumbs, given the plugin's tree of items
	 * to append.
	 * @param $subclass boolean
	 */
	function setBreadcrumbs($isSubclass = false) {
		$templateMgr =& TemplateManager::getManager();
		$pageCrumbs = array(
			array(
				Request::url(null, 'user'),
				'navigation.user'
			),
			array(
				Request::url(null, 'manager'),
				'user.role.manager'
			)
		);
		if ($isSubclass) $pageCrumbs[] = array(
			Request::url(null, 'manager', 'plugins'),
			'manager.plugins'
		);

		$templateMgr->assign('pageHierarchy', $pageCrumbs);
	}

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array('settings', __('plugins.generic.stopForumSpam.manager.settings'));
		}
		return parent::getManagementVerbs($verbs);
	}

	function validateExecute($hookName, $params) {

		$form =& $params[0];

		// Prepare HTTP session.
		$curlCh = curl_init();
		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);

		// assemble the URL with our parameters.
		$url = STOP_FORUM_SPAM_API_ENDPOINT;

		$journal =& Request::getJournal();
		$journalId = $journal->getId();

		// By including all three possibilities in the URL, we always get an XML document back from the API call.
		$ip = (bool)$this->getSetting($journalId, 'checkIp') ? urlencode(Request::getRemoteAddr()) : '';
		$url .= 'ip=' . $ip . '&';

		$email = (bool)$this->getSetting($journalId, 'checkEmail') ? urlencode($form->getData('email')) : '';
		$url .= 'email=' . $email . '&';

		$username = (bool)$this->getSetting($journalId, 'checkUsername') ? urlencode($form->getData('username')) : '';
		$url .= 'username=' . $username;

		// Make the request.
		curl_setopt($curlCh, CURLOPT_URL, $url);

		$response = curl_exec($curlCh);

		if (preg_match('/<appears>yes<\/appears>/', $response)) {
			$form->addError(__('plugins.generic.stopForumSpam.checkName'), __('plugins.generic.stopForumSpam.checkMessage'));
			return true;
		}

		return false;
	}

 	/*
 	 * Execute a management verb on this plugin
 	 * @param $verb string
 	 * @param $args array
	 * @param $message string Result status message
	 * @param $messageParams array Parameters for the message key
 	 * @return boolean
 	 */
	function manage($verb, $args, &$message, &$messageParams) {
		if (!parent::manage($verb, $args, $message, $messageParams)) return false;

		switch ($verb) {
			case 'settings':
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
				$journal =& Request::getJournal();

				$this->import('StopForumSpamSettingsForm');
				$form = new StopForumSpamSettingsForm($this, $journal->getId());
				if (Request::getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						Request::redirect(null, 'manager', 'plugin');
						return false;
					} else {
						$this->setBreadCrumbs(true);
						$form->display();
					}
				} else {
					$this->setBreadCrumbs(true);
					$form->initData();
					$form->display();
				}
				return true;
			default:
				// Unknown management verb
				assert(false);
				return false;
		}
	}
}
?>
