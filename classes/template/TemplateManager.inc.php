<?php

/**
 * TemplateManager.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package template
 *
 * Class for accessing the underlying template engine.
 * Currently integrated with Smarty (from http://smarty.php.net/).
 *
 * $Id$
 */

/* This definition is required by Smarty */
define('SMARTY_DIR', Core::getBaseDir() . '/lib/smarty/');

require_once('smarty/Smarty.class.php');
		
class TemplateManager extends Smarty {

	/**
	 * Constructor.
	 * Initialize template engine and assign basic template variables.
	 */
	function TemplateManager() {
		parent::Smarty();
		
		// Set up Smarty configuration
		$baseDir = dirname(dirname(dirname(__FILE__)));
		$this->template_dir = $baseDir . '/templates/';
		$this->compile_dir = $baseDir . '/templates/t_compile/';
		$this->config_dir = $baseDir . '/templates/t_config/';
		$this->cache_dir = $baseDir . '/templates/t_cache/';
		
		// TODO: Investigate caching behaviour and if OJS can take advantage of it
		//$this->caching = true;
		//$this->compile_check = true;
		
		// Assign common variables
		$this->assign('defaultCharset', Config::getVar('i18n', 'client_charset'));
		$this->assign('baseUrl', Request::getBaseUrl());
		$this->assign('pageTitle', 'common.openJournalSystems');
		$this->assign('indexUrl', Request::getIndexUrl());
		$this->assign('pageUrl', Request::getPageUrl());
		$this->assign('pagePath', '/' . Request::getRequestedPage() . (($requestedOp = Request::getRequestedOp()) == '' ? '' : '/' . $requestedOp));
		$this->assign('currentUrl', Request::getRequestUrl());
		$this->assign('dateFormatShort', Config::getVar('general', 'date_format_short'));
		$this->assign('dateFormatLong', Config::getVar('general', 'date_format_long'));
		$this->assign('datetimeFormatShort', Config::getVar('general', 'datetime_format_short'));
		$this->assign('datetimeFormatLong', Config::getVar('general', 'datetime_format_long'));
		$this->assign('currentLocale', Locale::getLocale());
		
		if (!defined('SESSION_DISABLE_INIT')) {
			/* Kludge to make sure no code that tries to connect to the database is executed
			 * (e.g., when loading installer pages). */
			$sessionManager = &SessionManager::getManager();
			$session = &$sessionManager->getUserSession();
			$isUserLoggedIn = Validation::isLoggedIn();
			$this->assign('isUserLoggedIn', $isUserLoggedIn);
			$this->assign('loggedInUsername', $session->getSessionVar('username'));
			
			$journal = &Request::getJournal();
			
			if (isset($journal)) {
				$aboutSubItems = array(
						array('name' => 'about.contact', 'url' => '/about/contact'),
						array('name' => 'about.editorialTeam', 'url' => '/about/editorialTeam'),
						array('name' => 'about.editorialPolicies', 'url' => '/about/editorialPolicies'),
						array('name' => 'about.submissions', 'url' => '/about/submissions')
				);
				
			} else {
				$aboutSubItems = array();
			}
		
			$navMenuItems = array(
				array('name' => 'navigation.home', 'url' => '/index'),
				array('name' => 'navigation.about', 'url' => '/about', 'subItems' => $aboutSubItems)
			);
			
			if ($isUserLoggedIn) {
				$userSubItems = array(
					array('name' => 'user.profile', 'url' => '/user/profile')
				);
				
				if (isset($journal)) {
					$roleDao = &DAORegistry::getDAO('RoleDAO');
					$roles = &$roleDao->getRolesByUserId($session->getUserId(), $journal->getJournalId());
					foreach ($roles as $role) {
						array_push($userSubItems, array('path' => '/user/' . $role->getRolePath(), 'name' => $role->getRoleName(), 'url' => '/' . $role->getRolePath()));
					}
				}

				array_push($navMenuItems,
					array('name' => 'navigation.userHome', 'url' => '/user', 'subItems' => $userSubItems)
				);
					
			} else {
				array_push($navMenuItems,
					array('name' => 'navigation.login', 'url' => '/login'),
					array('name' => 'navigation.register', 'url' => '/user/register')
				);
			}
			
			array_push($navMenuItems,
				array('name' => 'navigation.search', 'url' => '/search',
					'subItems' => array(
						array('name' => 'search.advancedSearch', 'url' => '/search/advanced'),
						array('name' => 'search.authorIndex', 'url' => '/search/authors')
					)
				)
			);
			
			if (isset($journal)) {
				$this->assign('currentJournal', $journal);
				$journalTitle = $journal->getSetting('journalTitle');
				if ($journalTitle == null || empty($journalTitle)) {
					$journalTitle = $journal->getTitle();
				}
				$this->assign('siteTitle', $journalTitle);
				$this->assign('publicFilesDir', PublicFileManager::getJournalFilesPath($journal->getJournalId()));
				$this->assign('siteTitle', $journalTitle);
				$this->assign('publicDir', PublicFileManager::getJournalFilesPath($journal->getJournalId()));
				//set alternate locales
				$locales = &$journal->getSupportedLocaleNames();
				$this->assign('alternateLocale1', $journal->getSetting('alternateLocale1'));
				$this->assign('alternateLocale2', $journal->getSetting('alternateLocale2'));
				//set nav items
				$this->assign('navItems', $journal->getSetting('navItems'));
				//set Journal Page Header
				$titleType = $journal->getSetting('pageHeaderTitleType');
				$this->assign('pageHeaderTitleType', $titleType);
				$this->assign('alternateHeader', $journal->getSetting('alternateHeader'));
				$this->assign('pageLogo', $journal->getSetting('pageHeaderLogoImage'));
				$this->assign('pageHeaderTitle', $journal->getSetting('pageHeaderTitle'));
				$this->assign('pageHeaderTitleImage', $journal->getSetting('pageHeaderTitleImage'));
				//set style sheet and footer
				$this->assign('journalStyleSheet', $journal->getSetting('journalStyleSheet'));
				$this->assign('journalPageFooter', $journal->getSetting('journalPageFooter'));	
			} else {
				$site = &Request::getSite();
				$this->assign('siteTitle', $site->getTitle());
				$this->assign('publicFilesDir', PublicFileManager::getSiteFilesPath());
				$locales = &$site->getSupportedLocaleNames();
			}
		
			$this->assign('navMenuItems', $navMenuItems);
			
		} else {
			$locales = &Locale::getAllLocales();
			$this->assign('languageToggleNoUser', true);
		}
			
		if (isset($locales) && count($locales) > 1) {
			$this->assign('enableLanguageToggle', true);
			$this->assign('languageToggleLocales', $locales);
		}
		
		$this->register_function('translate', array(&$this, 'smartyTranslate'));
		$this->register_function('html_options_translate', array(&$this, 'smartyHtmlOptionsTranslate'));
	}
	
	/**
	 * Dislay the template.
	 */
	function display($template, $sendContentType = true) {
		// Explicitly set the character encoding
		// Required in case server is using Apache's AddDefaultCharset directive
		// (which can prevent browser auto-detection of the proper character set)
		header('Content-Type: text/html; charset=' . Config::getVar('i18n', 'client_charset'));
		parent::display($template);
	}
	
	/**
	 * Clear template compile and cache directories.
	 */
	function clearTemplateCache() {
		$this->clear_compiled_tpl();
		$this->clear_all_cache();
	}
	
	/**
	 * Return an instance of the template manager.
	 * @return TemplateManager the template manager object
	 */
	function &getManager() {
		static $instance;
		
		if (!isset($instance)) {
			$instance = new TemplateManager();
		}
		return $instance;
	}
	
	
	//
	// Custom template functions
	//
	
	/**
	 * Smarty usage: {translate key="localization.key.name" [paramName="paramValue" ...]}
	 *
	 * Custom Smarty function for handling translation of strings.
	 * Substitution works by replacing tokens like "{$foo}" with the value of the parameter named "foo" (if supplied).
	 * @params $params array associative array, must contain "key" parameter for string to translate plus zero or more named parameters for substitution
	 * @params $smarty Smarty
	 * @return string the localized string, including any parameter substitutions
	 */
	function smartyTranslate($params, &$smarty) {
		if (isset($params) && !empty($params)) {
			if (isset($params['key'])) {
				$key = $params['key'];
				unset($params['key']);
				return Locale::translate($key, $params);
				
			} else {
				return Locale::translate('');
			}
		}
	}
	
	/**
	 * Smarty usage: {html_options_translate ...}
	 * For parameter usage, see http://smarty.php.net/manual/en/language.function.html.options.php
	 *
	 * Identical to Smarty's "html_options" function except option values are translated from i18n keys.
	 * @params $params array 
	 * @params $smarty Smarty
	 */
	function smartyHtmlOptionsTranslate($params, &$smarty) {
		if (isset($params['options'])) {
			if (isset($params['translateValues'])) {
				// Translate values AND output
				$newOptions = array();
				foreach ($params['options'] as $k => $v) {
					$newOptions[Locale::translate($k)] = Locale::translate($v);
				}
				$params['options'] = $newOptions;
				
			} else {
				// Just translate output
				$params['options'] = array_map(array('Locale', 'translate'), $params['options']);
			}
			
		}
		
		if (isset($params['output'])) {
			$params['output'] = array_map(array('Locale', 'translate'), $params['output']);
			
		}
		
		if (isset($params['values']) && isset($params['translateValues'])) {
			$params['values'] = array_map(array('Locale', 'translate'), $params['values']);
		}
		
		require_once($this->_get_plugin_filepath('function','html_options'));
		return smarty_function_html_options($params, $smarty);
	}
	
	/* Deprecated. Old gettext localization function.
	function smartyTranslateOld($params, $content, &$smarty) {
		if (isset($content) && !empty($content)) {
			$content = Locale::translate($content);
			
			if (empty($params)) {
				return $content;
				
			} else {
				return call_user_func_array('sprintf', array_merge(array($content), $params));
			}
		}
	}
	*/
	
}

?>
