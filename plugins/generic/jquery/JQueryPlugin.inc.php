<?php

/**
 * @file plugins/generic/jquery/JQueryPlugin.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JQueryPlugin
 * @ingroup plugins_generic_jquery
 *
 * @brief Plugin to allow jQuery scripts to be added to OJS
 */

// $Id$


import('classes.plugins.GenericPlugin');

define('JS_SCRIPTS_DIR', 'lib' . DIRECTORY_SEPARATOR . 'pkp' . DIRECTORY_SEPARATOR . 'js');
define('JQUERY_INSTALL_PATH', JS_SCRIPTS_DIR . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'jquery');
define('JQUERY_JS_PATH', JQUERY_INSTALL_PATH . DIRECTORY_SEPARATOR . 'jquery.min.js');

class JQueryPlugin extends GenericPlugin {
	/**
	 * Register the plugin, if enabled; note that this plugin
	 * runs under both Journal and Site contexts.
	 * @param $category string
	 * @param $path string
	 * @return boolean
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			$this->addLocaleData();
			if ($this->isJQueryInstalled()) {
				HookRegistry::register('TemplateManager::display',array(&$this, 'displayCallback'));
				$user =& Request::getUser();
				if ($user) HookRegistry::register('Templates::Common::Footer::PageFooter', array(&$this, 'footerCallback'));
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->addStyleSheet(Request::getBaseUrl() . '/lib/pkp/styles/jqueryUi.css');
				$templateMgr->addStyleSheet(Request::getBaseUrl() . '/lib/pkp/styles/jquery.pnotify.default.css');
			}
			return true;
		}
		return false;
	}

	/**
	 * Get the URL for the jQuery script
	 * @return string
	 */
	function getScriptPath() {
		return Request::getBaseUrl() . DIRECTORY_SEPARATOR . JQUERY_JS_PATH;
	}

	/**
	 * Given a $page and $op, return a list of scripts that should be loaded
	 * @param $page string The requested page
	 * @param $op string The requested operation
	 * @return array
	 */
	function getEnabledScripts($page, $op) {
		$scripts = array();
		switch ("$page/$op") {
			case 'article/view':
				$scripts[] = 'lib/pkp/js/articleView.js';
				break;
			case 'editor/submissionCitations':
			case 'sectionEditor/submissionCitations':
				$scripts[] = 'plugins/generic/jquery/scripts/grid-clickhandler.js';
				$scripts[] = 'lib/pkp/js/modal.js';
				$scripts[] = 'lib/pkp/js/lib/jquery/plugins/validate/jquery.validate.min.js';
				$scripts[] = 'lib/pkp/js/jqueryValidatorI18n.js';
				break;
			case 'editor/submissions':
				$scripts[] = 'plugins/generic/jquery/scripts/submissionSearch.js';
				break;
			case 'admin/journals':
			case 'editor/backIssues':
			case 'manager/groupMembership':
			case 'manager/groups':
			case 'manager/reviewFormElements':
			case 'manager/reviewForms':
			case 'manager/sections':
			case 'manager/subscriptionTypes':
			case 'rtadmin/contexts':
			case 'rtadmin/searches':
			case 'subscriptionManager/subscriptionTypes':
				$scripts[] = 'plugins/generic/jquery/scripts/jquery.tablednd_0_5.js';
				$scripts[] = 'plugins/generic/jquery/scripts/tablednd.js';
				break;

		}
		$user =& Request::getUser();
		if ($user) $scripts[] = 'lib/pkp/js/jquery.pnotify.js';
		return $scripts;
	}

	/**
	 * Hook callback function for TemplateManager::display
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function displayCallback($hookName, $args) {
		// Only pages can receive scripts
		$request =& Registry::get('request');
		if (!is_a($request->getRouter(), 'PKPPageRouter')) return null;

		$page = Request::getRequestedPage();
		$op = Request::getRequestedOp();
		$scripts = JQueryPlugin::getEnabledScripts($page, $op);
		if(empty($scripts)) return null;

		$templateManager =& $args[0];
		$additionalHeadData = $templateManager->get_template_vars('additionalHeadData');
		$baseUrl = $templateManager->get_template_vars('baseUrl');

		if(Config::getVar('general', 'enable_cdn')) {
			$jQueryScript = '<script src="http://www.google.com/jsapi"></script>
			<script>
				google.load("jquery", "1");
				google.load("jqueryui", "1");
			</script>';
		} else {
			$jQueryScript = '<script type="text/javascript" src="' . Request::getBaseUrl() . '/lib/pkp/js/lib/jquery/jquery.min.js"></script>
			<script type="text/javascript" src="' . Request::getBaseUrl() . '/lib/pkp/js/lib/jquery/plugins/jqueryUi.min.js"></script>';
		}
		$jQueryScript .= "\n" . JQueryPlugin::addScripts($baseUrl, $scripts);

		$templateManager->assign('additionalHeadData', $additionalHeadData."\n".$jQueryScript);
	}

	function jsEscape($string) {
		return strtr($string, array('\\'=>'\\\\',"'"=>"\\'",'"'=>'\\"',"\r"=>'\\r',"\n"=>'\\n','</'=>'<\/'));
	}

	/**
	 * Footer callback to load and display notifications
	 */
	function footerCallback($hookName, $args) {
		$output =& $args[2];
		$notificationDao =& DAORegistry::getDAO('NotificationDAO');
		$user =& Request::getUser();
		$notificationsMarkup = '';

		$notifications =& $notificationDao->getNotificationsByUserId($user->getId(), NOTIFICATION_LEVEL_TRIVIAL);
		while ($notification =& $notifications->next()) {
			$notificationTitle = $notification->getTitle();
			if ($notification->getIsLocalized() && !empty($notificationTitle)) $notificationTitle = Locale::translate($notificationTitle);
			if (empty($notificationTitle)) $notificationTitle = Locale::translate('notification.notification');
			$notificationsMarkup .= '$.pnotify({pnotify_title: \'' . $this->jsEscape($notificationTitle) . '\', pnotify_text: \'';
			if ($notification->getIsLocalized()) $notificationsMarkup .= $this->jsEscape(Locale::translate($notification->getContents(), array('param' => $notification->getParam())));
			else $notificationsMarkup .= $this->jsEscape($notification->getContents());
			$notificationsMarkup .= '\'});';
			$notificationDao->deleteNotificationById($notification->getId());
			unset($notification);
		}
		if (!empty($notificationsMarkup)) $notificationsMarkup = "<script type=\"text/javascript\">$notificationsMarkup</script>\n";

		$output .= $notificationsMarkup;
		return false;
	}

	/**
	 * Add scripts contained in scripts/ subdirectory to a string to be returned to callback func.
	 * @param baseUrl string
	 * @param scripts array All enabled scripts for this page
	 * @return string
	 */
	function addScripts($baseUrl, $scripts) {
		$scriptOpen = '	<script language="javascript" type="text/javascript" src="';
		$scriptClose = '"></script>';
		$returner = '';

		foreach ($scripts as $script) {
			if(file_exists(Core::getBaseDir() . DIRECTORY_SEPARATOR . $script)) {
				$returner .= $scriptOpen . $baseUrl . '/' . $script . $scriptClose . "\n";
			}
		}
		return $returner;
	}

	/**
	 * Get the symbolic name of this plugin
	 * @return string
	 */
	function getName() {
		return 'JQueryPlugin';
	}

	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return Locale::translate('plugins.generic.jquery.name');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		if ($this->isJQueryInstalled()) return Locale::translate('plugins.generic.jquery.description');
		return Locale::translate('plugins.generic.jquery.descriptionDisabled', array('jQueryPath' => JQUERY_INSTALL_PATH));
	}

	/**
	 * Check whether or not the JQuery library is installed
	 * @return boolean
	 */
	function isJQueryInstalled() {
		// We may not register jQuery when the application is not yet installed
		// as access to the template manager (see register method) requires db access.
		return Config::getVar('general', 'installed') && file_exists(JQUERY_JS_PATH);
	}
}

?>
