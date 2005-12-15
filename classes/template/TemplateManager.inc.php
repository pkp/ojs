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

import('search.ArticleSearch');

class TemplateManager extends Smarty {

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
		$this->template_dir = $baseDir . '/templates/';
		$this->compile_dir = $cachePath . '/t_compile/';
		$this->config_dir = $cachePath . '/t_config/';
		$this->cache_dir = $cachePath . '/t_cache/';
		
		// TODO: Investigate caching behaviour and if OJS can take advantage of it
		//$this->caching = true;
		//$this->compile_check = true;
		
		// Assign common variables
		$this->assign('defaultCharset', Config::getVar('i18n', 'client_charset'));
		$this->assign('baseUrl', Request::getBaseUrl());
		$this->assign('pageTitle', 'common.openJournalSystems');
		$this->assign('requestedPage', Request::getRequestedPage());
		$this->assign('currentUrl', Request::getRequestUrl());
		$this->assign('dateFormatTrunc', Config::getVar('general', 'date_format_trunc'));
		$this->assign('dateFormatShort', Config::getVar('general', 'date_format_short'));
		$this->assign('dateFormatLong', Config::getVar('general', 'date_format_long'));
		$this->assign('datetimeFormatShort', Config::getVar('general', 'datetime_format_short'));
		$this->assign('datetimeFormatLong', Config::getVar('general', 'datetime_format_long'));
		$this->assign('currentLocale', Locale::getLocale());
		$this->assign('articleSearchByOptions', array(
			'' => 'search.allFields',
			ARTICLE_SEARCH_AUTHOR => 'search.author',
			ARTICLE_SEARCH_TITLE => 'article.title',
			ARTICLE_SEARCH_ABSTRACT => 'search.abstract',
			ARTICLE_SEARCH_INDEX_TERMS => 'search.indexTerms',
			ARTICLE_SEARCH_GALLEY_FILE => 'search.fullText'
		));
		
		if (!defined('SESSION_DISABLE_INIT')) {
			/* Kludge to make sure no code that tries to connect to the database is executed
			 * (e.g., when loading installer pages). */
			$sessionManager = &SessionManager::getManager();
			$session = &$sessionManager->getUserSession();
			$isUserLoggedIn = Validation::isLoggedIn();
			$this->assign_by_ref('userSession', $session);
			$this->assign('isUserLoggedIn', $isUserLoggedIn);
			$this->assign('loggedInUsername', $session->getSessionVar('username'));
			
			$journal = &Request::getJournal();
			$site = &Request::getSite();
			
			if (isset($journal)) {
				$this->assign_by_ref('currentJournal', $journal);
				$journalTitle = $journal->getTitle();
				$this->assign('siteTitle', $journalTitle);
				$this->assign('publicFilesDir', Request::getBaseUrl() . '/' . PublicFileManager::getJournalFilesPath($journal->getJournalId()));

				$locales = &$journal->getSupportedLocaleNames();
				$this->assign('alternateLocale1', $journal->getSetting('alternateLocale1'));
				$this->assign('alternateLocale2', $journal->getSetting('alternateLocale2'));
				
				// Assign additional navigation bar items
				$navMenuItems = &$journal->getSetting('navItems');
				$this->assign_by_ref('navMenuItems', $navMenuItems);

				// Assign journal page header
				$this->assign('displayPageHeaderTitle', $journal->getJournalPageHeaderTitle());
				$this->assign('displayPageHeaderLogo', $journal->getJournalPageHeaderLogo());
				$this->assign('alternatePageHeader', $journal->getSetting('journalPageHeader'));
				$this->assign('metaSearchDescription', $journal->getSetting('searchDescription'));
				$this->assign('metaSearchKeywords', $journal->getSetting('searchKeywords'));
				$this->assign('metaCustomHeaders', $journal->getSetting('customHeaders'));
				$this->assign('numPageLinks', $journal->getSetting('numPageLinks'));
				$this->assign('itemsPerPage', $journal->getSetting('itemsPerPage'));
				
				// Assign stylesheet and footer
				$this->assign('pageStyleSheet', $journal->getSetting('journalStyleSheet'));
				$this->assign('pageFooter', $journal->getSetting('journalPageFooter'));	
				
			} else {
				$this->assign('siteTitle', $site->getTitle());
				$this->assign('publicFilesDir', Request::getBaseUrl() . '/' . PublicFileManager::getSiteFilesPath());
				$locales = &$site->getSupportedLocaleNames();
				$this->assign('itemsPerPage', Config::getVar('interface', 'items_per_page'));
				$this->assign('numPageLinks', Config::getVar('interface', 'page_links'));
			}
				
			if (!$site->getJournalRedirect()) {
				$this->assign('hasOtherJournals', true);
			}
			
		} else {
			$locales = &Locale::getAllLocales();
			$this->assign('languageToggleNoUser', true);
		}
			
		if (isset($locales) && count($locales) > 1) {
			$this->assign('enableLanguageToggle', true);
			$this->assign('languageToggleLocales', $locales);
		}
		
		// Register custom functions
		$this->register_modifier('strip_unsafe_html', array('String', 'stripUnsafeHtml'));
		$this->register_modifier('to_array', array(&$this, 'smartyToArray'));
		$this->register_modifier('explode', array(&$this, 'smartyExplode'));
		$this->register_modifier('assign', array(&$this, 'smartyAssign'));
		$this->register_function('translate', array(&$this, 'smartyTranslate'));
		$this->register_function('flush', array(&$this, 'smartyFlush'));
		$this->register_function('call_hook', array(&$this, 'smartyCallHook'));
		$this->register_function('html_options_translate', array(&$this, 'smartyHtmlOptionsTranslate'));
		$this->register_block('iterate', array(&$this, 'smartyIterate'));
		$this->register_function('page_links', array(&$this, 'smartyPageLinks'));
		$this->register_function('page_info', array(&$this, 'smartyPageInfo'));
		$this->register_function('get_help_id', array(&$this, 'smartyGetHelpId'));
		$this->register_function('icon', array(&$this, 'smartyIcon'));
		$this->register_function('help_topic', array(&$this, 'smartyHelpTopic'));
		$this->register_function('get_debug_info', array(&$this, 'smartyGetDebugInfo'));
		$this->register_function('assign_mailto', array(&$this, 'smartyAssignMailto'));

		$this->register_function('url', array(&$this, 'smartyUrl'));
	}

	/**
	 * Dislay the template.
	 */
	function display($template, $sendContentType = 'text/html') {
		$charset = Config::getVar('i18n', 'client_charset');

		// Give any hooks registered against the TemplateManager
		// the opportunity to modify behavior; otherwise, display
		// the template as usual.
		if (!HookRegistry::call('TemplateManager::display', array(&$this, &$template, &$sendContentType, &$charset))) {
			// Explicitly set the character encoding
			// Required in case server is using Apache's AddDefaultCharset directive
			// (which can prevent browser auto-detection of the proper character set)
			header('Content-Type: ' . $sendContentType . '; charset=' . $charset);

			// Actually display the template.
			parent::display($template);
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
			if (isset($params['key'])) {
				$key = $params['key'];
				unset($params['key']);
				if (isset($params['params'])) {
					$paramsArray = $params['params'];
					unset($params['params']);
					$params = array_merge($params, $paramsArray);
				}
				return Locale::translate($key, $params);
				
			} else {
				return Locale::translate('');
			}
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

		if ($iterator && !$iterator->eof()) {
			$repeat = true;

			if (isset($params['key'])) {
				list($key, $value) = $iterator->nextWithKey();
				$smarty->assign_by_ref($params['item'], $value);
				$smarty->assign_by_ref($params['key'], $key);
			} else {
				$smarty->assign_by_ref($params['item'], $iterator->next());
			}
		} else {
			$repeat = false;
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
		if (isset($params) && !empty($params)) {
			if (isset($params['key'])) {
				$key = $params['key'];
				unset($params['key']);
				$translatedKey = Help::translate($key);
			} else {
				$translatedKey = Help::translate('');
			}
			
			if ($params['url'] == "true") {
				return Request::url(null, 'help', 'view', $translatedKey);
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
		if (isset($params) && !empty($params)) {
			$translatedKey = isset($params['key']) ? Help::translate($params['key']) : Help::translate('');
			$link = Request::url(null, 'help', 'view', $translatedKey);
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
				$iconHtml = '<img src="' . $this->get_template_vars('baseUrl') . '/templates/images/icons/';			
				$iconHtml .= $params['name'] . ($disabled ? '_disabled' : '') . '.gif" width="16" height="14" border="0" alt="';
				
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
		flush();
		ob_flush();
	}

	/**
	 * Call hooks from a template.
	 */
	function smartyCallHook($params, &$smarty) {
		HookRegistry::call($params['name'], array(&$params, &$smarty, &$output));
		return $output;
	}

	/**
	 * Get debugging information and assign it to the template.
	 */
	function smartyGetDebugInfo($params, &$smarty) {
		if (Config::getVar('debug', 'show_stats')) {
			$this->assign('enableDebugStats', true);
			$this->assign('debugExecutionTime', Core::microtime() - Registry::get('system.debug.startTime'));
			$dbconn = &DBConnection::getInstance();
			$this->assign('debugNumDatabaseQueries', $dbconn->getNumQueries());
			$this->assign_by_ref('debugNotes', Registry::get('system.debug.notes'));
		}

	}

	/**
	 * Generate a URL into OJS. (This is a wrapper around Request::url to make it available to Smarty templates.)
	 */
	function smartyUrl($params, &$smarty) {
		// Extract the variables named in $paramList, and remove them
		// from the params array. Variables remaining in params will be
		// passed along to Request::url as extra parameters.
		$paramList = array('journal', 'page', 'op', 'path', 'anchor');
		foreach ($paramList as $param) {
			if (isset($params[$param])) {
				$$param = $params[$param];
				unset($params[$param]);
			} else {
				$$param = null;
			}
		}

		return str_replace('&', '&amp;', Request::url($journal, $page, $op, $path, $params, $anchor));
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
	 * Pages requiring POST parameters WILL NOT work properly.
	 */
	function smartyPageLinks($params, &$smarty) {
		$iterator = $params['iterator'];
		$name = $params['name'];
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
			$value .= '<a href="' . Request::url(null, null, null, Request::getRequestedArgs(), $params) . '">&lt;&lt;</a>&nbsp;';
			$params[$paramName] = $page - 1;
			$value .= '<a href="' . Request::url(null, null, null, Request::getRequestedArgs(), $params) . '">&lt;</a>&nbsp;';
		}

		for ($i=$pageBase; $i<min($pageBase+$numPageLinks, $pageCount+1); $i++) {
			if ($i == $page) {
				$value .= "<b>$i</b>&nbsp;";
			} else {
				$params[$paramName] = $i;
				$value .= '<a href="' . Request::url(null, null, null, Request::getRequestedArgs(), $params) . '">' . $i . '</a>&nbsp;';
			}
		}
		if ($page < $pageCount) {
			$params[$paramName] = $page + 1;
			$value .= '<a href="' . Request::url(null, null, null, Request::getRequestedArgs(), $params) . '">&gt;</a>&nbsp;';
			$params[$paramName] = $pageCount;
			$value .= '<a href="' . Request::url(null, null, null, Request::getRequestedArgs(), $params) . '">&gt;&gt;</a>&nbsp;';
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
	 * Split the supplied string by the supplied separator.
	 */
	function smartyExplode($string, $separator) {
		return explode($separator, $string);
	}

	/**
	 * Assign a value to a template variable.
	 */
	function smartyAssign($value, $varName) {
		if (isset($varName)) {
			// NOTE: CANNOT use $this, as it's actually
			// a COPY of the real template manager!
			// FIXME: Track this bug down. (Smarty?)
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign($varName, $value);
		}
	}
}

?>
