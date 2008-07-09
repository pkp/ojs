<?php

/**
 * @defgroup template
 */
 
/**
 * @file classes/template/TemplateManager.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TemplateManager
 * @ingroup template
 *
 * @brief Class for accessing the underlying template engine.
 * Currently integrated with Smarty (from http://smarty.php.net/).
 */

// $Id$


/* This definition is required by Smarty */
define('SMARTY_DIR', Core::getBaseDir() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'smarty' . DIRECTORY_SEPARATOR);

require_once('smarty/Smarty.class.php');
require_once('smarty/plugins/modifier.escape.php'); // Seems to be needed?

import('search.ArticleSearch');

define('CACHEABILITY_NO_CACHE',		'no-cache');
define('CACHEABILITY_NO_STORE',		'no-store');
define('CACHEABILITY_PUBLIC',		'public');
define('CACHEABILITY_MUST_REVALIDATE',	'must-revalidate');
define('CACHEABILITY_PROXY_REVALIDATE',	'proxy-revalidate');

class TemplateManager extends Smarty {
	/** @var $styleSheets array of URLs to stylesheets */
	var $styleSheets;

	/** @var $initialized Kludge because of reference problems with
	    TemplateManager::getManager() invoked during constructor process */
	var $initialized;

	/** @var $cacheability string Type of cacheability (Cache-Control). */
	var $cacheability;

	/**
	 * Constructor.
	 * Initialize template engine and assign basic template variables.
	 */
	function TemplateManager() {
		parent::Smarty();

		import('file.PublicFileManager');
		import('cache.CacheManager');

		// Set up Smarty configuration
		$baseDir = Core::getBaseDir();
		$cachePath = CacheManager::getFileCachePath();
		$this->template_dir = $baseDir . DIRECTORY_SEPARATOR . 'templates';
		$this->compile_dir = $cachePath . DIRECTORY_SEPARATOR . 't_compile';
		$this->config_dir = $cachePath . DIRECTORY_SEPARATOR . 't_config';
		$this->cache_dir = $cachePath . DIRECTORY_SEPARATOR . 't_cache';

		// Assign common variables
		$this->styleSheets = array();
		$this->assign_by_ref('stylesheets', $this->styleSheets);
		$this->cacheability = CACHEABILITY_NO_STORE; // Safe default

		$this->assign('defaultCharset', Config::getVar('i18n', 'client_charset'));
		$this->assign('baseUrl', Request::getBaseUrl());
		$this->assign('pageTitle', 'common.openJournalSystems');
		$this->assign('requestedPage', Request::getRequestedPage());
		$this->assign('currentUrl', Request::getCompleteUrl());
		$this->assign('dateFormatTrunc', Config::getVar('general', 'date_format_trunc'));
		$this->assign('dateFormatShort', Config::getVar('general', 'date_format_short'));
		$this->assign('dateFormatLong', Config::getVar('general', 'date_format_long'));
		$this->assign('datetimeFormatShort', Config::getVar('general', 'datetime_format_short'));
		$this->assign('datetimeFormatLong', Config::getVar('general', 'datetime_format_long'));

		// Are we using implicit authentication?
		$this->assign('implicitAuth', Config::getVar('security', 'implicit_auth'));

		$locale = Locale::getLocale();
		$this->assign('currentLocale', $locale);

		if (!defined('SESSION_DISABLE_INIT')) {
			/* Kludge to make sure no code that tries to connect to the database is executed
			 * (e.g., when loading installer pages). */
			$this->assign('isUserLoggedIn', Validation::isLoggedIn());

			$journal = &Request::getJournal();
			$site = &Request::getSite();

			$versionDAO = &DAORegistry::getDAO('VersionDAO'); 
			$currentVersion = $versionDAO->getCurrentVersion();
			$this->assign('currentVersionString', $currentVersion->getVersionString());

			$siteFilesDir = Request::getBaseUrl() . '/' . PublicFileManager::getSiteFilesPath();
			$this->assign('sitePublicFilesDir', $siteFilesDir);
			$this->assign('publicFilesDir', $siteFilesDir); // May be overridden by journal

			$siteStyleFilename = PublicFileManager::getSiteFilesPath() . '/' . $site->getSiteStyleFilename();
			if (file_exists($siteStyleFilename)) $this->addStyleSheet(Request::getBaseUrl() . '/' . $siteStyleFilename);

			if (isset($journal)) {
				$this->assign_by_ref('currentJournal', $journal);
				$journalTitle = $journal->getJournalTitle();
				$this->assign('siteTitle', $journalTitle);
				$this->assign('publicFilesDir', Request::getBaseUrl() . '/' . PublicFileManager::getJournalFilesPath($journal->getJournalId()));

				$this->assign('primaryLocale', $journal->getPrimaryLocale());
				$this->assign('alternateLocales', $journal->getSetting('alternateLocales'));

				// Assign additional navigation bar items
				$navMenuItems = &$journal->getLocalizedSetting('navItems');
				$this->assign_by_ref('navMenuItems', $navMenuItems);

				// Assign journal page header
				$this->assign('displayPageHeaderTitle', $journal->getJournalPageHeaderTitle());
				$this->assign('displayPageHeaderLogo', $journal->getJournalPageHeaderLogo());
				$this->assign('alternatePageHeader', $journal->getLocalizedSetting('journalPageHeader'));
				$this->assign('metaSearchDescription', $journal->getLocalizedSetting('searchDescription'));
				$this->assign('metaSearchKeywords', $journal->getLocalizedSetting('searchKeywords'));
				$this->assign('metaCustomHeaders', $journal->getLocalizedSetting('customHeaders'));
				$this->assign('numPageLinks', $journal->getSetting('numPageLinks'));
				$this->assign('itemsPerPage', $journal->getSetting('itemsPerPage'));
				$this->assign('enableAnnouncements', $journal->getSetting('enableAnnouncements'));

				// Load and apply theme plugin, if chosen
				$themePluginPath = $journal->getSetting('journalTheme');
				if (!empty($themePluginPath)) {
					// Load and activate the theme
					$themePlugin =& PluginRegistry::loadPlugin('themes', $themePluginPath);
					if ($themePlugin) $themePlugin->activate($this);
				}

				// Assign stylesheets and footer
				$journalStyleSheet = $journal->getSetting('journalStyleSheet');
				if ($journalStyleSheet) {
					$this->addStyleSheet(Request::getBaseUrl() . '/' . PublicFileManager::getJournalFilesPath($journal->getJournalId()) . '/' . $journalStyleSheet['uploadName']);
				}
				
				import('payment.ojs.OJSPaymentManager');
				$paymentManager =& OJSPaymentManager::getManager();
				$this->assign('journalPaymentsEnabled', $paymentManager->isConfigured());				

				$this->assign('pageFooter', $journal->getLocalizedSetting('journalPageFooter'));	
			} else {
				// Add the site-wide logo, if set for this locale or the primary locale
				$this->assign('displayPageHeaderTitle', $site->getSitePageHeaderTitle());

				$this->assign('siteTitle', $site->getSiteTitle());
				$this->assign('itemsPerPage', Config::getVar('interface', 'items_per_page'));
				$this->assign('numPageLinks', Config::getVar('interface', 'page_links'));
			}

			if (!$site->getJournalRedirect()) {
				$this->assign('hasOtherJournals', true);
			}
		}

		// If there's a locale-specific stylesheet, add it.
		if (($localeStyleSheet = Locale::getLocaleStyleSheet($locale)) != null) $this->addStyleSheet(Request::getBaseUrl() . '/' . $localeStyleSheet);

		// Register custom functions
		$this->register_modifier('translate', array('Locale', 'translate'));
		$this->register_modifier('strip_unsafe_html', array('String', 'stripUnsafeHtml'));
		$this->register_modifier('String_substr', array('String', 'substr'));
		$this->register_modifier('to_array', array(&$this, 'smartyToArray'));
		$this->register_modifier('escape', array(&$this, 'smartyEscape'));
		$this->register_modifier('explode', array(&$this, 'smartyExplode'));
		$this->register_modifier('assign', array(&$this, 'smartyAssign'));
		$this->register_function('translate', array(&$this, 'smartyTranslate'));
		$this->register_function('flush', array(&$this, 'smartyFlush'));
		$this->register_function('call_hook', array(&$this, 'smartyCallHook'));
		$this->register_function('html_options_translate', array(&$this, 'smartyHtmlOptionsTranslate'));
		$this->register_block('iterate', array(&$this, 'smartyIterate'));
		$this->register_function('call_progress_function', array(&$this, 'smartyCallProgressFunction'));
		$this->register_function('page_links', array(&$this, 'smartyPageLinks'));
		$this->register_function('page_info', array(&$this, 'smartyPageInfo'));
		$this->register_function('get_help_id', array(&$this, 'smartyGetHelpId'));
		$this->register_function('icon', array(&$this, 'smartyIcon'));
		$this->register_function('help_topic', array(&$this, 'smartyHelpTopic'));
		$this->register_function('get_debug_info', array(&$this, 'smartyGetDebugInfo'));
		$this->register_function('assign_mailto', array(&$this, 'smartyAssignMailto'));
		$this->register_function('display_template', array(&$this, 'smartyDisplayTemplate'));

		$this->register_function('url', array(&$this, 'smartyUrl'));

		$this->initialized = false;
	}

	/**
	 * Flag the page as cacheable (or not).
	 * @param $cacheability boolean optional
	 */
	function setCacheability($cacheability = CACHEABILITY_PUBLIC) {
		$this->cacheability = $cacheability;
	}

	function initialize() {
		// This code cannot be called in the constructor because of
		// reference problems, i.e. callers that need getManager fail.

		// Load the block plugins.
		$plugins =& PluginRegistry::loadCategory('blocks');

		$this->initialized = true;
	}

	function addStyleSheet($url) {
		array_push($this->styleSheets, $url);
	}

	/**
	 * Display the template.
	 */
	function display($template, $sendContentType = 'text/html', $hookName = 'TemplateManager::display') {
		if (!$this->initialized) {
			$this->initialize();
		}

		$charset = Config::getVar('i18n', 'client_charset');

		// Give any hooks registered against the TemplateManager
		// the opportunity to modify behavior; otherwise, display
		// the template as usual.

		$output = null;
		if (!HookRegistry::call($hookName, array(&$this, &$template, &$sendContentType, &$charset, &$output))) {
			// If this is the main display call, send headers.
			if ($hookName == 'TemplateManager::display') {
				// Explicitly set the character encoding
				// Required in case server is using Apache's
				// AddDefaultCharset directive (which can
				// prevent browser auto-detection of the proper
				// character set)
				header('Content-Type: ' . $sendContentType . '; charset=' . $charset);

				// Send caching info
				header('Cache-Control: ' . $this->cacheability);
			}

			// Actually display the template.
			parent::display($template);
		} else {
			// Display the results of the plugin.
			echo $output;
		}
	}

	/**
	 * Display templates from Smarty and allow hook overrides
	 * 
	 * Smarty usage: {display_template template="name.tpl" hookname="My::Hook::Name"}
	 */
	function smartyDisplayTemplate($params, &$smarty) {
		$templateMgr =& TemplateManager::getManager();
		// This is basically a wrapper for display()
		if (isset($params['template'])) {
			$templateMgr->display($params['template'], "", $params['hookname']);
		}
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
	// Custom template functions, modifiers, etc.
	//

	/**
	 * Smarty usage: {translate key="localization.key.name" [paramName="paramValue" ...]}
	 *
	 * Custom Smarty function for translating localization keys.
	 * Substitution works by replacing tokens like "{$foo}" with the value of the parameter named "foo" (if supplied).
	 * @params $params array associative array, must contain "key" parameter for string to translate plus zero or more named parameters for substitution.
	 * 	Translation variables can be specified also as an optional
	 * 	associative array named "params".
	 * @params $smarty Smarty
	 * @return string the localized string, including any parameter substitutions
	 */
	function smartyTranslate($params, &$smarty) {
		if (isset($params) && !empty($params)) {
			if (!isset($params['key'])) return Locale::translate('');

			$key = $params['key'];
			unset($params['key']);
			if (isset($params['params'])) {
				$paramsArray = $params['params'];
				unset($params['params']);
				$params = array_merge($params, $paramsArray);
			}
			return Locale::translate($key, $params);
		}
	}

	/**
	 * Smarty usage: {assign_mailto var="varName" address="email@address.com" ...]} 
	 *
	 * Generates a hex-encoded mailto address and assigns it to the variable name specified..
	 */
	function smartyAssignMailto($params, &$smarty) {
		if (isset($params['var']) && isset($params['address'])) {
			// Password encoding code taken from Smarty's mailto
			// function.
			$address = $params['address'];
			$address_encode = '';
			for ($x=0; $x < strlen($address); $x++) {
				if(preg_match('!\w!',$address[$x])) {
					$address_encode .= '%' . bin2hex($address[$x]);
				} else {
					$address_encode .= $address[$x];
				}
			}
			$text_encode = '';
			for ($x=0; $x < strlen($text); $x++) {
				$text_encode .= '&#x' . bin2hex($text[$x]).';';
			}

			$mailto = "&#109;&#97;&#105;&#108;&#116;&#111;&#58;";
			$smarty->assign($params['var'], $mailto . $address_encode);
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

	/**
	 * Iterator function for looping through objects extending the
	 * ItemIterator class.
	 * Parameters:
	 *  - from: Name of template variable containing iterator
	 *  - item: Name of template variable to receive each item
	 *  - key: (optional) Name of variable to receive index of current item
	 */
	function smartyIterate($params, $content, &$smarty, &$repeat) {
		$iterator = &$smarty->get_template_vars($params['from']);

		if (isset($params['key'])) {
			if (empty($content)) $smarty->assign($params['key'], 1);
			else $smarty->assign($params['key'], $smarty->get_template_vars($params['key'])+1);
		}

		// If the iterator is empty, we're finished.
		if (!$iterator || $iterator->eof()) {
			if (!$repeat) return $content;
			$repeat = false;
			return '';
		}

		$repeat = true;

		if (isset($params['key'])) {
			list($key, $value) = $iterator->nextWithKey();
			$smarty->assign_by_ref($params['item'], $value);
			$smarty->assign_by_ref($params['key'], $key);
		} else {
			$smarty->assign_by_ref($params['item'], $iterator->next());
		}
		return $content;
	}

	/**
	 * Smarty usage: {get_help_id key="(dir)*.page.topic" url="boolean"}
	 *
	 * Custom Smarty function for retrieving help topic ids.
	 * Direct mapping of page topic key to a numerical value representing the associated help topic xml file
	 * @params $params array associative array, must contain "key" parameter for string to translate
	 * @params $smarty Smarty
	 * @return numerical help topic id
	 */
	function smartyGetHelpId($params, &$smarty) {
		$help =& Help::getHelp();
		if (isset($params) && !empty($params)) {
			if (isset($params['key'])) {
				$key = $params['key'];
				unset($params['key']);
				$translatedKey = $help->translate($key);
			} else {
				$translatedKey = $help->translate('');
			}

			if ($params['url'] == "true") {
				return Request::url(null, 'help', 'view', explode('/', $translatedKey));
			} else {
				return $translatedKey;
			}
		}
	}

	/**
	 * Smarty usage: {help_topic key="(dir)*.page.topic" text="foo"}
	 *
	 * Custom Smarty function for creating anchor tags
	 * @params $params array associative array
	 * @params $smarty Smarty
	 * @return anchor link to related help topic
	 */
	function smartyHelpTopic($params, &$smarty) {
		$help =& Help::getHelp();
		if (isset($params) && !empty($params)) {
			$translatedKey = isset($params['key']) ? $help->translate($params['key']) : $help->translate('');
			$link = Request::url(null, 'help', 'view', explode('/', $translatedKey));
			$text = isset($params['text']) ? $params['text'] : '';
			return "<a href=\"$link\">$text</a>";
		}
	}

	/**
	 * Smarty usage: {icon name="image name" alt="alternative name" url="url path"}
	 *
	 * Custom Smarty function for generating anchor tag with optional url
	 * @params $params array associative array, must contain "name" paramater to create image anchor tag
	 * @return string <a href="url"><img src="path to image/image name" ... /></a>
	 */
	function smartyIcon($params, &$smarty) {
		if (isset($params) && !empty($params)) {
			$iconHtml = '';
			if (isset($params['name'])) {
				// build image tag with standarized size of 16x16
				$disabled = (isset($params['disabled']) && !empty($params['disabled']));
				if (!isset($params['path'])) $params['path'] = 'templates/images/icons/';
				$iconHtml = '<img src="' . $smarty->get_template_vars('baseUrl') . '/' . $params['path'];			
				$iconHtml .= $params['name'] . ($disabled ? '_disabled' : '') . '.gif" alt="';

				// if alt parameter specified use it, otherwise use localization version
				if (isset($params['alt'])) {
					$iconHtml .= $params['alt'];
				} else {
					$iconHtml .= Locale::translate('icon.'.$params['name'].'.alt');
				}
				$iconHtml .= '" ';

				// if onclick parameter specified use it
				if (isset($params['onclick'])) {
					$iconHtml .= 'onclick="' . $params['onclick'] . '" ';
				}


				$iconHtml .= '/>';

				// build anchor with url if specified as a parameter
				if (!$disabled && isset($params['url'])) {
					$iconHtml = '<a href="' . $params['url'] . '" class="icon">' . $iconHtml . '</a>';
				}
			}
			return $iconHtml;
		}
	}

	/**
	 * Display page information for a listing of items that has been
	 * divided onto multiple pages.
	 * Usage:
	 * {page_info from=$myIterator}
	 */
	function smartyPageInfo($params, &$smarty) {
		$iterator = $params['iterator'];

		$itemsPerPage = $smarty->get_template_vars('itemsPerPage');
		if (!is_numeric($itemsPerPage)) $itemsPerPage=25;

		$page = $iterator->getPage();
		$pageCount = $iterator->getPageCount();
		$itemTotal = $iterator->getCount();

		if ($pageCount<1) return '';

		return Locale::translate('navigation.items', array(
			'from' => (($page - 1) * $itemsPerPage) + 1,
			'to' => min($itemTotal, $page * $itemsPerPage),
			'total' => $itemTotal
		));
	}

	/**
	 * Flush the output buffer. This is useful in cases where Smarty templates
	 * are calling functions that take a while to execute so that they can display
	 * a progress indicator or a message stating that the operation may take a while.
	 */
	function smartyFlush($params, &$smarty) {
		$smarty->flush();
	}

	function flush() {
		while (ob_get_level()) {
			ob_end_flush();
		}
		flush();
	}

	/**
	 * Call hooks from a template.
	 */
	function smartyCallHook($params, &$smarty) {
		$output = null;
		HookRegistry::call($params['name'], array(&$params, &$smarty, &$output));
		return $output;
	}

	/**
	 * Get debugging information and assign it to the template.
	 */
	function smartyGetDebugInfo($params, &$smarty) {
		if (Config::getVar('debug', 'show_stats')) {
			$smarty->assign('enableDebugStats', true);
			$smarty->assign('debugExecutionTime', Core::microtime() - Registry::get('system.debug.startTime'));
			$dbconn = &DBConnection::getInstance();
			$smarty->assign('debugNumDatabaseQueries', $dbconn->getNumQueries());
			$smarty->assign_by_ref('debugNotes', Registry::get('system.debug.notes'));
		}

	}

	/**
	 * Generate a URL into OJS. (This is a wrapper around Request::url to make it available to Smarty templates.)
	 */
	function smartyUrl($params, &$smarty) {
		// Extract the variables named in $paramList, and remove them
		// from the params array. Variables remaining in params will be
		// passed along to Request::url as extra parameters.
		$paramList = array('journal', 'page', 'op', 'path', 'anchor', 'escape');
		foreach ($paramList as $param) {
			if (isset($params[$param])) {
				$$param = $params[$param];
				unset($params[$param]);
			} else {
				$$param = null;
			}
		}

		return Request::url($journal, $page, $op, $path, $params, $anchor, !isset($escape) || $escape);
	}

	function setProgressFunction($progressFunction) {
		Registry::set('progressFunctionCallback', $progressFunction);
	}

	function smartyCallProgressFunction($params, &$smarty) {
		$progressFunctionCallback =& Registry::get('progressFunctionCallback');
		if ($progressFunctionCallback) {
			call_user_func($progressFunctionCallback);
		}
	}

	function updateProgressBar($progress, $total) {
		static $lastPercent;
		$percent = round($progress * 100 / $total);
		if (!isset($lastPercent) || $lastPercent != $percent) {
			for($i=1; $i <= $percent-$lastPercent; $i++) {
				echo '<img src="' . Request::getBaseUrl() . '/templates/images/progbar.gif" width="5" height="15">';
			}
		}
		$lastPercent = $percent;

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->flush();
	}

	/**
	 * Display page links for a listing of items that has been
	 * divided onto multiple pages.
	 * Usage:
	 * {page_links
	 * 	name="nameMustMatchGetRangeInfoCall"
	 * 	iterator=$myIterator
	 *	additional_param=myAdditionalParameterValue
	 * }
	 */
	function smartyPageLinks($params, &$smarty) {
		$iterator = $params['iterator'];
		$name = $params['name'];
		if (isset($params['params']) && is_array($params['params'])) {
			$extraParams = $params['params'];
			unset($params['params']);
			$params = array_merge($params, $extraParams);
		}
		if (isset($params['anchor'])) {
			$anchor = $params['anchor'];
			unset($params['anchor']);
		} else {
			$anchor = null;
		}
		if (isset($params['all_extra'])) {
			$allExtra = ' ' . $params['all_extra'];
			unset($params['all_extra']);
		} else {
			$allExtra = '';
		}

		unset($params['iterator']);
		unset($params['name']);

		$numPageLinks = $smarty->get_template_vars('numPageLinks');
		if (!is_numeric($numPageLinks)) $numPageLinks=10;

		$page = $iterator->getPage();
		$pageCount = $iterator->getPageCount();
		$itemTotal = $iterator->getCount();

		$pageBase = max($page - floor($numPageLinks / 2), 1);
		$paramName = $name . 'Page';

		if ($pageCount<=1) return '';

		$value = '';

		if ($page>1) {
			$params[$paramName] = 1;
			$value .= '<a href="' . Request::url(null, null, null, Request::getRequestedArgs(), $params, $anchor) . '"' . $allExtra . '>&lt;&lt;</a>&nbsp;';
			$params[$paramName] = $page - 1;
			$value .= '<a href="' . Request::url(null, null, null, Request::getRequestedArgs(), $params, $anchor) . '"' . $allExtra . '>&lt;</a>&nbsp;';
		}

		for ($i=$pageBase; $i<min($pageBase+$numPageLinks, $pageCount+1); $i++) {
			if ($i == $page) {
				$value .= "<strong>$i</strong>&nbsp;";
			} else {
				$params[$paramName] = $i;
				$value .= '<a href="' . Request::url(null, null, null, Request::getRequestedArgs(), $params, $anchor) . '"' . $allExtra . '>' . $i . '</a>&nbsp;';
			}
		}
		if ($page < $pageCount) {
			$params[$paramName] = $page + 1;
			$value .= '<a href="' . Request::url(null, null, null, Request::getRequestedArgs(), $params, $anchor) . '"' . $allExtra . '>&gt;</a>&nbsp;';
			$params[$paramName] = $pageCount;
			$value .= '<a href="' . Request::url(null, null, null, Request::getRequestedArgs(), $params, $anchor) . '"' . $allExtra . '>&gt;&gt;</a>&nbsp;';
		}

		return $value;
	}

	/**
	 * Convert the parameters of a function to an array.
	 */
	function smartyToArray() {
		return func_get_args();
	}

	/**
	 * Override the built-in smarty escape modifier to set the charset
	 * properly; also add the jsparam escaping method.
	 */
	function smartyEscape($string, $esc_type = 'html', $char_set = null) {
		if ($char_set === null) $char_set = LOCALE_ENCODING;
		switch ($esc_type) {
			case 'jsparam':
				// When including a value in a Javascript parameter,
				// quotes need to be specially handled on top of
				// the usual escaping, as Firefox (and probably others)
				// decodes &#039; as a quote before interpereting
				// the javascript.
				$value = smarty_modifier_escape($string, 'html', $char_set);
				return str_replace('&#039;', '\\\'', $value);
			default:
				return smarty_modifier_escape($string, $esc_type, $char_set);
		}
	}

	/**
	 * Split the supplied string by the supplied separator.
	 */
	function smartyExplode($string, $separator) {
		return explode($separator, $string);
	}

	/**
	 * Assign a value to a template variable.
	 */
	function smartyAssign($value, $varName, $passThru = false) {
		if (isset($varName)) {
			// NOTE: CANNOT use $this, as it's actually
			// a COPY of the real template manager for some PHPs!
			// FIXME: Track this bug down. (Smarty?)
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign($varName, $value);
		}
		if ($passThru) return $value;
	}
}

?>
