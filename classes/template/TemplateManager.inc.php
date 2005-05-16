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
		$this->assign('requestPageUrl', Request::getPageUrl() . '/' . Request::getRequestedPage());
		$this->assign('pagePath', '/' . Request::getRequestedPage() . (($requestedOp = Request::getRequestedOp()) == '' ? '' : '/' . $requestedOp));
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
			ARTICLE_SEARCH_GALLEY_FILE => 'search.fullText'
		));
		
		if (!defined('SESSION_DISABLE_INIT')) {
			/* Kludge to make sure no code that tries to connect to the database is executed
			 * (e.g., when loading installer pages). */
			$sessionManager = &SessionManager::getManager();
			$session = &$sessionManager->getUserSession();
			$isUserLoggedIn = Validation::isLoggedIn();
			$this->assign('userSession', $session);
			$this->assign('isUserLoggedIn', $isUserLoggedIn);
			$this->assign('loggedInUsername', $session->getSessionVar('username'));
			
			$journal = &Request::getJournal();
			$site = &Request::getSite();
			
			if (isset($journal)) {
				$this->assign('currentJournal', $journal);
				$journalTitle = $journal->getTitle();
				$this->assign('siteTitle', $journalTitle);
				$this->assign('publicFilesDir', Request::getBaseUrl() . '/' . PublicFileManager::getJournalFilesPath($journal->getJournalId()));

				$locales = &$journal->getSupportedLocaleNames();
				$this->assign('alternateLocale1', $journal->getSetting('alternateLocale1'));
				$this->assign('alternateLocale2', $journal->getSetting('alternateLocale2'));
				
				// Assign additional navigation bar items
				$navMenuItems = &$journal->getSetting('navItems');
				$this->assign('navMenuItems', $navMenuItems);
				
				if (!$site->getJournalRedirect()) {
					$this->assign('hasOtherJournals', true);
				}

				// Assign journal page header
				$this->assign('pageHeaderTitle', $journal->getJournalPageHeaderTitle());
				$this->assign('pageHeaderLogo', $journal->getJournalPageHeaderLogo());
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
			
		} else {
			$locales = &Locale::getAllLocales();
			$this->assign('languageToggleNoUser', true);
		}
			
		if (isset($locales) && count($locales) > 1) {
			$this->assign('enableLanguageToggle', true);
			$this->assign('languageToggleLocales', $locales);
		}
		
		// Register custom functions
		$this->register_function('translate', array(&$this, 'smartyTranslate'));
		$this->register_function('assign_translate', array(&$this, 'smartyAssignTranslate'));
		$this->register_function('html_options_translate', array(&$this, 'smartyHtmlOptionsTranslate'));
		$this->register_block('iterate', array(&$this, 'smartyIterate'));
		$this->register_function('page_links', array(&$this, 'smartyPageLinks'));
		$this->register_function('page_info', array(&$this, 'smartyPageInfo'));
		$this->register_function('get_help_id', array(&$this, 'smartyGetHelpId'));
		$this->register_function('icon', array(&$this, 'smartyIcon'));
		$this->register_function('help_topic', array(&$this, 'smartyHelpTopic'));
	}

	/**
	 * Dislay the template.
	 */
	function display($template, $sendContentType = 'text/html') {
		// Explicitly set the character encoding
		// Required in case server is using Apache's AddDefaultCharset directive
		// (which can prevent browser auto-detection of the proper character set)
		header('Content-Type: ' . $sendContentType . '; charset=' . Config::getVar('i18n', 'client_charset'));
		
		if (Config::getVar('debug', 'show_stats')) {
			// FIXME Stats do not include template rendering -- put this code in the footer template directly rather than here
			$this->assign('enableDebugStats', true);
			$this->assign('debugExecutionTime', Core::microtime() - Registry::get('system.debug.startTime'));
			$dbconn = &DBConnection::getInstance();
			$this->assign('debugNumDatabaseQueries', $dbconn->getNumQueries());
		}
		
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
	// Custom template functions, modifiers, etc.
	//
	
	/**
	 * Smarty usage: {translate key="localization.key.name" [paramName="paramValue" ...]}
	 *
	 * Custom Smarty function for translating localization keys.
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
	 * Smarty usage: {translate var="varName" key="localization.key.name" [paramName="paramValue" ...]} 
	 *
	 * Same as Smarty translate except translated string is assigned to variable.
	 * @see TemplateManager#smartyTranslate
	 */
	function smartyAssignTranslate($params, &$smarty) {
		if (isset($params['var'])) {
			$smarty->assign($params['var'], $smarty->smartyTranslate($params, $smarty));
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
				return $this->get_template_vars('pageUrl') . "/help/view/" . $translatedKey;
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
			$link = $this->get_template_vars('pageUrl') . "/help/view/" . $translatedKey;
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

				// if onClick parameter specified use it, otherwise use localization version
				if (isset($params['onClick'])) {
					$iconHtml .= 'onClick="' . $params['onClick'] . '" ';
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
	 * Display page links for a listing of items that has been
	 * divided onto multiple pages.
	 * Usage:
	 * {page_links
	 * 	name="nameMustMatchGetRangeInfoCall"
	 * 	iterator=$myIterator
	 * }
	 * This function works correctly IFF the current URL
	 * contains enough information to display the page correctly;
	 * Pages requiring POST parameters WILL NOT work properly.
	 */
	function smartyPageLinks($params, &$smarty) {
		$iterator = $params['iterator'];

		$numPageLinks = $smarty->get_template_vars('numPageLinks');
		if (!is_numeric($numPageLinks)) $numPageLinks=10;

		$page = $iterator->getPage();
		$pageCount = $iterator->getPageCount();
		$itemTotal = $iterator->getCount();

		$pageBase = max($page - floor($numPageLinks / 2), 1);
		$paramName = $params['name'] . 'Page';

		if ($pageCount<=1) return '';

		$value = '';

		if ($page>1) {
			$value .= '<a href="' . $this->_makePageUrl(Request::getCompleteUrl(), $paramName, 1) . '">&lt;&lt;</a>&nbsp;';
			$value .= '<a href="' . $this->_makePageUrl(Request::getCompleteUrl(), $paramName, $page - 1) . '">&lt;</a>&nbsp;';
		}

		for ($i=$pageBase; $i<min($pageBase+$numPageLinks, $pageCount+1); $i++) {
			if ($i == $page) {
				$value .= "<b>$i</b>&nbsp;";
			} else {
				$value .= '<a href="' . $this->_makePageUrl(Request::getCompleteUrl(), $paramName, $i) . '">' . $i . '</a>&nbsp;';
			}
		}
		if ($page < $pageCount) {
			$value .= '<a href="' . $this->_makePageUrl(Request::getCompleteUrl(), $paramName, $page + 1) . '">&gt;</a>&nbsp;';
			$value .= '<a href="' . $this->_makePageUrl(Request::getCompleteUrl(), $paramName, $pageCount) . '">&gt;&gt;</a>&nbsp;';
		}

		return $value;
	}

	/**
	 * PRIVATE function, used by assignPaging to generate a URL with the
	 * specified page number. Replaces the current page number if necessary.
	 */
	function _makePageUrl($currentUrl, $paramName, $pageNum) {
		$url = parse_url($currentUrl);
		if (empty($url['query'])) return "$currentUrl?$paramName=$pageNum";
		if (substr_count($url['query'], "$paramName=")>0) {
			$array=explode("$paramName=", $url['query']);
			$array2=explode('&',$array[1]);
			$url['query']=str_replace("$paramName=$array2[0]", "$paramName=$pageNum", $url["query"]);
			return $this->_glueUrl($url);
		}
		else return "$currentUrl&$paramName=$pageNum";
	}

	function _glueUrl($url) {
		if (!is_array($url)) return false;
	
		$returner = @$url['scheme'] ? @$url['scheme'] . ':' . ((strtolower(@$url['scheme']) == 'mailto') ? '' : '//') : '';
		$returner .= @$url['user'] ? @$url['user'] . (@$url['pass']? ':' . @$url['pass']:'') . '@' : '';
		$returner .= @$url['host'] ? @$url['host'] : '';
		$returner .= @$url['port'] ? ':' . @$url['port'] : '';
		$returner .= @$url['path'] ? @$url['path'] : '';
		$returner .= @$url['query'] ? '?' . @$url['query'] : '';
		$returner .= @$url['fragment'] ? '#'. @$url['fragment'] : '';
		return $returner;
	}
}

?>
