<?php

/**
 * @defgroup template Template
 * Implements template management.
 */

/**
 * @file classes/template/PKPTemplateManager.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TemplateManager
 * @ingroup template
 *
 * @brief Class for accessing the underlying template engine.
 * Currently integrated with Smarty (from http://smarty.php.net/).
 */

/* This definition is required by Smarty */
define('SMARTY_DIR', Core::getBaseDir() . '/lib/pkp/lib/vendor/smarty/smarty/libs/');

require_once('./lib/pkp/lib/vendor/smarty/smarty/libs/Smarty.class.php');
require_once('./lib/pkp/lib/vendor/smarty/smarty/libs/plugins/modifier.escape.php'); // Seems to be needed?

define('CACHEABILITY_NO_CACHE',		'no-cache');
define('CACHEABILITY_NO_STORE',		'no-store');
define('CACHEABILITY_PUBLIC',		'public');
define('CACHEABILITY_MUST_REVALIDATE',	'must-revalidate');
define('CACHEABILITY_PROXY_REVALIDATE',	'proxy-revalidate');

define('STYLE_SEQUENCE_CORE', 0);
define('STYLE_SEQUENCE_NORMAL', 10);
define('STYLE_SEQUENCE_LATE', 15);
define('STYLE_SEQUENCE_LAST', 20);

define('CDN_JQUERY_VERSION', '1.11.0');
define('CDN_JQUERY_UI_VERSION', '1.11.0');

define('CSS_FILENAME_SUFFIX', 'css');

import('lib.pkp.classes.template.PKPTemplateResource');

class PKPTemplateManager extends Smarty {
	/** @var array of URLs to stylesheets */
	private $_styleSheets = array();

	/** @var array of URLs to javascript files */
	private $_javaScripts = array();

	/** @var array of HTML head content to output */
	private $_htmlHeaders = array();

	/** @var string Type of cacheability (Cache-Control). */
	private $_cacheability;

	/** @var object The form builder vocabulary class. */
	private $_fbv;

	/** @var PKPRequest */
	private $_request;

	/**
	 * Constructor.
	 * Initialize template engine and assign basic template variables.
	 * @param $request PKPRequest
	 */
	function __construct($request) {
		assert(is_a($request, 'PKPRequest'));
		$this->_request = $request;

		parent::__construct();

		// Set up Smarty configuration
		$baseDir = Core::getBaseDir();
		$cachePath = CacheManager::getFileCachePath();

		// Set the default template dir (app's template dir)
		$this->app_template_dir = $baseDir . DIRECTORY_SEPARATOR . 'templates';
		// Set fallback template dir (core's template dir)
		$this->core_template_dir = $baseDir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'pkp' . DIRECTORY_SEPARATOR . 'templates';

		$this->template_dir = array($this->app_template_dir, $this->core_template_dir);
		$this->compile_dir = $cachePath . DIRECTORY_SEPARATOR . 't_compile';
		$this->config_dir = $cachePath . DIRECTORY_SEPARATOR . 't_config';
		$this->cache_dir = $cachePath . DIRECTORY_SEPARATOR . 't_cache';

		$this->_cacheability = CACHEABILITY_NO_STORE; // Safe default
	}

	/**
	 * Initialize the template manager.
	 */
	function initialize() {
		$locale = AppLocale::getLocale();
		$application = PKPApplication::getApplication();
		$router = $this->_request->getRouter();
		assert(is_a($router, 'PKPRouter'));

		$currentContext = $this->_request->getContext();

		$this->assign(array(
			'defaultCharset' => Config::getVar('i18n', 'client_charset'),
			'basePath' => $this->_request->getBasePath(),
			'baseUrl' => $this->_request->getBaseUrl(),
			'requiresFormRequest' => $this->_request->isPost(),
			'currentUrl' => $this->_request->getCompleteUrl(),
			'dateFormatTrunc' => Config::getVar('general', 'date_format_trunc'),
			'dateFormatShort' => Config::getVar('general', 'date_format_short'),
			'dateFormatLong' => Config::getVar('general', 'date_format_long'),
			'datetimeFormatShort' => Config::getVar('general', 'datetime_format_short'),
			'datetimeFormatLong' => Config::getVar('general', 'datetime_format_long'),
			'timeFormat' => Config::getVar('general', 'time_format'),
			'currentContext' => $currentContext,
			'currentLocale' => $locale,
			'pageTitle' => $application->getNameKey(),
			'applicationName' => __($application->getNameKey()),
		));

		if (is_a($router, 'PKPPageRouter')) {
			$this->assign(array(
				'requestedPage' => $router->getRequestedPage($this->_request),
				'requestedOp' => $router->getRequestedOp($this->_request),
			));

			// Register the jQuery script
			$min = Config::getVar('general', 'enable_minified') ? '.min' : '';
			if (Config::getVar('general', 'enable_cdn')) {
				$jquery = '//ajax.googleapis.com/ajax/libs/jquery/' . CDN_JQUERY_VERSION . '/jquery' . $min . '.js';
				$jqueryUI = '//ajax.googleapis.com/ajax/libs/jqueryui/' . CDN_JQUERY_UI_VERSION . '/jquery-ui' . $min . '.js';
			} else {
				$jquery = $this->_request->getBaseUrl() . '/lib/pkp/lib/components/jquery/jquery' . $min . '.js';
				$jqueryUI = $this->_request->getBaseUrl() . '/lib/pkp/lib/components/jquery-ui/jquery-ui' . $min . '.js';
			}
			$this->addJavaScript(
				'jquery',
				$jquery,
				array(
					'priority' => STYLE_SEQUENCE_CORE,
					'contexts' => 'backend',
				)
			);
			$this->addJavaScript(
				'jqueryUI',
				$jqueryUI,
				array(
					'priority' => STYLE_SEQUENCE_CORE,
					'contexts' => 'backend',
				)
			);

			// Register the pkp-lib JS library
			$this->registerJSLibraryData();
			$this->registerJSLibrary();

			// Load Noto Sans font from Google Font CDN
			// To load extended latin or other character sets, see:
			// https://www.google.com/fonts#UsePlace:use/Collection:Noto+Sans
			if (Config::getVar('general', 'enable_cdn')) {
				$this->addStyleSheet(
					'pkpLibNotoSans',
					'//fonts.googleapis.com/css?family=Noto+Sans:400,400italic,700,700italic',
					array(
						'priority' => STYLE_SEQUENCE_CORE,
						'contexts' => 'backend',
					)
				);
			}

			// Register the primary backend stylesheet
			if ($dispatcher = $this->_request->getDispatcher()) {
				$this->addStyleSheet(
					'pkpLib',
					$dispatcher->url($this->_request, ROUTE_COMPONENT, null, 'page.PageHandler', 'css'),
					array(
						'priority' => STYLE_SEQUENCE_CORE,
						'contexts' => 'backend',
					)
				);
			}

			// Add reading language flag based on locale
			$this->assign('currentLocaleLangDir', AppLocale::getLocaleDirection($locale) );

			// If there's a locale-specific stylesheet, add it.
			if (($localeStyleSheet = AppLocale::getLocaleStyleSheet($locale)) != null) {
				$this->addStyleSheet(
					'pkpLibLocale',
					$this->_request->getBaseUrl() . '/' . $localeStyleSheet,
					array(
						'contexts' => array('frontend', 'backend'),
					)
				);
			}

			// Register colour picker assets on the appearance page
			$this->addJavaScript(
				'spectrum',
				$this->_request->getBaseUrl() . '/lib/pkp/js/lib/jquery/plugins/spectrum/spectrum.js',
				array(
					'contexts' => array('backend-management-settings', 'backend-admin-settings', 'backend-admin-contexts'),
				)
			);
			$this->addStyleSheet(
				'spectrum',
				$this->_request->getBaseUrl() . '/lib/pkp/js/lib/jquery/plugins/spectrum/spectrum.css',
				array(
					'contexts' => array('backend-management-settings', 'backend-admin-settings', 'backend-admin-contexts'),
				)
			);

			// Register recaptcha on relevant pages
			if (Config::getVar('captcha', 'recaptcha') && Config::getVar('captcha', 'captcha_on_register')) {
				$this->addJavaScript(
					'recaptcha',
					'https://www.google.com/recaptcha/api.js',
					array(
						'contexts' => array('frontend-user-register', 'frontend-user-registerUser'),
					)
				);
			}

			// Register meta tags
			if (Config::getVar('general', 'installed')) {
				if (($this->_request->getRequestedPage()=='' || $this->_request->getRequestedPage() == 'index') && $currentContext && $currentContext->getLocalizedSetting('searchDescription')) {
					$this->addHeader('searchDescription', '<meta name="description" content="' . $currentContext->getLocalizedSetting('searchDescription') . '">');
				}

				$this->addHeader(
					'generator',
					'<meta name="generator" content="' . __($application->getNameKey()) . ' ' . $application->getCurrentVersion()->getVersionString(false) . '">',
					array(
						'contexts' => array('frontend','backend'),
					)
				);

				if ($currentContext) {
					$customHeaders = $currentContext->getLocalizedSetting('customHeaders');
					if (!empty($customHeaders)) {
						$this->addHeader('customHeaders', $customHeaders);
					}
				}
			}

			if ($currentContext && !$currentContext->getEnabled()) {
				$this->addHeader(
					'noindex',
					'<meta name="robots" content="noindex,nofollow">',
					array(
						'contexts' => array('frontend','backend'),
					)
				);
			}
		}

		// Register custom functions
		$this->register_modifier('translate', array('AppLocale', 'translate'));
		$this->register_modifier('strip_unsafe_html', array('PKPString', 'stripUnsafeHtml'));
		$this->register_modifier('String_substr', array('PKPString', 'substr'));
		$this->register_modifier('dateformatPHP2JQueryDatepicker', array('PKPString', 'dateformatPHP2JQueryDatepicker'));
		$this->register_modifier('to_array', array($this, 'smartyToArray'));
		$this->register_modifier('compare', array($this, 'smartyCompare'));
		$this->register_modifier('concat', array($this, 'smartyConcat'));
		$this->register_modifier('strtotime', array($this, 'smartyStrtotime'));
		$this->register_modifier('explode', array($this, 'smartyExplode'));
		$this->register_modifier('assign', array($this, 'smartyAssign'));
		$this->register_function('csrf', array($this, 'smartyCSRF'));
		$this->register_function('translate', array($this, 'smartyTranslate'));
		$this->register_function('null_link_action', array($this, 'smartyNullLinkAction'));
		$this->register_function('help', array($this, 'smartyHelp'));
		$this->register_function('flush', array($this, 'smartyFlush'));
		$this->register_function('call_hook', array($this, 'smartyCallHook'));
		$this->register_function('html_options_translate', array($this, 'smartyHtmlOptionsTranslate'));
		$this->register_block('iterate', array($this, 'smartyIterate'));
		$this->register_function('page_links', array($this, 'smartyPageLinks'));
		$this->register_function('page_info', array($this, 'smartyPageInfo'));
		$this->register_function('pluck_files', array($this, 'smartyPluckFiles'));

		// Modified vocabulary for creating forms
		$fbv = $this->getFBV();
		$this->register_block('fbvFormSection', array($fbv, 'smartyFBVFormSection'));
		$this->register_block('fbvFormArea', array($fbv, 'smartyFBVFormArea'));
		$this->register_function('fbvFormButtons', array($fbv, 'smartyFBVFormButtons'));
		$this->register_function('fbvElement', array($fbv, 'smartyFBVElement'));
		$this->assign('fbvStyles', $fbv->getStyles());

		$this->register_function('fieldLabel', array($fbv, 'smartyFieldLabel'));

		// register the resource name "core"
		$coreResource = new PKPTemplateResource($this->core_template_dir);
		$this->register_resource('core', array(
			array($coreResource, 'fetch'),
			array($coreResource, 'fetchTimestamp'),
			array($coreResource, 'getSecure'),
			array($coreResource, 'getTrusted')
		));

		$appResource = new PKPTemplateResource($this->app_template_dir);
		$this->register_resource('app', array(
			array($appResource, 'fetch'),
			array($appResource, 'fetchTimestamp'),
			array($appResource, 'getSecure'),
			array($appResource, 'getTrusted')
		));

		$this->register_function('url', array($this, 'smartyUrl'));
		// ajax load into a div or any element
		$this->register_function('load_url_in_el', array($this, 'smartyLoadUrlInEl'));
		$this->register_function('load_url_in_div', array($this, 'smartyLoadUrlInDiv'));

		// load stylesheets/scripts/headers from a given context
		$this->register_function('load_stylesheet', array($this, 'smartyLoadStylesheet'));
		$this->register_function('load_script', array($this, 'smartyLoadScript'));
		$this->register_function('load_header', array($this, 'smartyLoadHeader'));

		/**
		 * Kludge to make sure no code that tries to connect to the
		 * database is executed (e.g., when loading installer pages).
		 */
		if (!defined('SESSION_DISABLE_INIT')) {
			$application = PKPApplication::getApplication();
			$this->assign(array(
				'isUserLoggedIn' => Validation::isLoggedIn(),
				'isUserLoggedInAs' => Validation::isLoggedInAs(),
				'itemsPerPage' => Config::getVar('interface', 'items_per_page'),
				'numPageLinks' => Config::getVar('interface', 'page_links'),
			));

			$user = $this->_request->getUser();
			$hasSystemNotifications = false;
			if ($user) {
				$notificationDao = DAORegistry::getDAO('NotificationDAO');
				$notifications = $notificationDao->getByUserId($user->getId(), NOTIFICATION_LEVEL_TRIVIAL);
				if ($notifications->getCount() > 0) {
					$this->assign('hasSystemNotifications', true);
				}

				// Assign the user name to be used in the sitenav
				$this->assign(array(
					'loggedInUsername' => $user->getUserName(),
					'initialHelpState' => (int) $user->getInlineHelp(),
				));
			}
		}

		// Load enabled block plugins and setup active sidebar variables
		PluginRegistry::loadCategory('blocks', true);
		$sidebarHooks = HookRegistry::getHooks('Templates::Common::Sidebar');
		$this->assign(array(
			'hasSidebar' => !empty($sidebarHooks),
		));
	}

	/**
	 * Override the Smarty {include ...} function to allow hooks to be
	 * called.
	 */
	function _smarty_include($params) {
		if (!HookRegistry::call('TemplateManager::include', array($this, &$params))) {
			return parent::_smarty_include($params);
		}
		return false;
	}

	/**
	 * Flag the page as cacheable (or not).
	 * @param $cacheability boolean optional
	 */
	function setCacheability($cacheability = CACHEABILITY_PUBLIC) {
		$this->_cacheability = $cacheability;
	}

	/**
	 * Compile a LESS stylesheet
	 *
	 * @param $name string Unique name for this LESS stylesheet
	 * @param $lessFile string Path to the LESS file to compile
	 * @param $args array Optional arguments. SUpports:
	 *   'baseUrl': Base URL to use when rewriting URLs in the LESS file.
	 *   'addLess': Array of additional LESS files to parse before compiling
	 * @return string Compiled CSS styles
	 */
	public function compileLess($name, $lessFile, $args = array()) {

		// Load the LESS compiler
		require_once('lib/pkp/lib/vendor/oyejorge/less.php/lessc.inc.php');
		$less = new Less_Parser(array(
			'relativeUrls' => false,
			'compress' => true,
		));

		$request = $this->_request;

		// Allow plugins to intervene
		HookRegistry::call('PageHandler::compileLess', array(&$less, &$lessFile, &$args, $name, $request));

		// Read the stylesheet
		$less->parseFile($lessFile);

		// Add extra LESS files before compiling
		if (isset($args['addLess']) && is_array($args['addLess'])) {
			foreach ($args['addLess'] as $addless) {
				$less->parseFile($addless);
			}
		}

		// Add extra LESS variables before compiling
		if (isset($args['addLessVariables'])) {
			$less->parse($args['addLessVariables']);
		}

		// Set the @baseUrl variable
		$baseUrl = !empty($args['baseUrl']) ? $args['baseUrl'] : $request->getBaseUrl(true);
		$less->parse("@baseUrl: '$baseUrl';");

		return $less->getCSS();
	}

	/**
	 * Save LESS styles to a cached file
	 *
	 * @param $path string File path to save the compiled styles
	 * @param styles string CSS styles compiled from the LESS
	 * @return bool success/failure
	 */
	public function cacheLess($path, $styles) {
		if (file_put_contents($path, $styles) === false) {
			error_log("Unable to write \"$path\".");
			return false;
		}

		return true;
	}

	/**
	 * Retrieve the file path for a cached LESS file
	 *
	 * @param $name string Unique name for the LESS file
	 * @return $path string Path to the less file or false if not found
	 */
	public function getCachedLessFilePath($name) {
		$cacheDirectory = CacheManager::getFileCachePath();
		$context = $this->_request->getContext();
		$contextId = is_a($context, 'Context') ? $context->getId() : 0;
		return $cacheDirectory . DIRECTORY_SEPARATOR . $contextId . '-' . $name . '.css';
	}

	/**
	 * Register a stylesheet with the style handler
	 *
	 * @param $name string Unique name for the stylesheet
	 * @param $style string The stylesheet to be included. Should be a URL
	 *   or, if the `inline` argument is included, stylesheet data to be output.
	 * @param $args array Key/value array defining display details
	 *   `priority` int The order in which to print this stylesheet.
	 *      Default: STYLE_SEQUENCE_NORMAL
	 *   `contexts` string|array Where the stylesheet should be loaded.
	 *      Default: array('frontend')
	 *   `inline` bool Whether the $stylesheet value should be output directly as
	 *      stylesheet data. Used to pass backend data to the scripts.
	 */
	function addStyleSheet($name, $style, $args = array()) {

		$args = array_merge(
			array(
				'priority' => STYLE_SEQUENCE_NORMAL,
				'contexts' => array('frontend'),
				'inline'   => false,
			),
			$args
		);

		$args['contexts'] = (array) $args['contexts'];
		foreach($args['contexts'] as $context) {
			$this->_styleSheets[$context][$args['priority']][$name] = array(
				'style' => $style,
				'inline' => $args['inline'],
			);
		}
	}

	/**
	 * Register a script with the script handler
	 *
	 * @param $name string Unique name for the script
	 * @param $script string The script to be included. Should be a URL or, if
	 *   the `inline` argument is included, script data to be output.
	 * @param $args array Key/value array defining display details
	 *   `priority` int The order in which to print this script.
	 *      Default: STYLE_SEQUENCE_NORMAL
	 *   `contexts` string|array Where the script should be loaded.
	 *      Default: array('frontend')
	 *   `inline` bool Whether the $script value should be output directly as
	 *      script data. Used to pass backend data to the scripts.
	 */
	function addJavaScript($name, $script, $args = array()) {

		$args = array_merge(
			array(
				'priority' => STYLE_SEQUENCE_NORMAL,
				'contexts' => array('frontend'),
				'inline'   => false,
			),
			$args
		);

		$args['contexts'] = (array) $args['contexts'];
		foreach($args['contexts'] as $context) {
			$this->_javaScripts[$context][$args['priority']][$name] = array(
				'script' => $script,
				'inline' => $args['inline'],
			);
		}
	}

	/**
	 * Add a page-specific item to the <head>.
	 *
	 * @param $name string Unique name for the header
	 * @param $header string The header to be included.
	 * @param $args array Key/value array defining display details
	 *   `priority` int The order in which to print this header.
	 *      Default: STYLE_SEQUENCE_NORMAL
	 *   `contexts` string|array Where the header should be loaded.
	 *      Default: array('frontend')
	 */
	function addHeader($name, $header, $args = array()) {

		$args = array_merge(
			array(
				'priority' => STYLE_SEQUENCE_NORMAL,
				'contexts' => array('frontend'),
			),
			$args
		);

		$args['contexts'] = (array) $args['contexts'];
		foreach($args['contexts'] as $context) {
			$this->_htmlHeaders[$context][$args['priority']][$name] = array(
				'header' => $header,
			);
		}
	}

	/**
	 * Register all files required by the core JavaScript library
	 */
	function registerJSLibrary() {

		$basePath = $this->_request->getBasePath();
		$baseUrl = $this->_request->getBaseUrl();
		$localeChecks = array(AppLocale::getLocale(), strtolower(substr(AppLocale::getLocale(), 0, 2)));

		// Common $args array used for all our core JS files
		$args = array(
			'priority' => STYLE_SEQUENCE_CORE,
			'contexts' => 'backend',
		);

		// Load jQuery validate separately because it can not be linted
		// properly by our build script
		$this->addJavaScript(
			'jqueryValidate',
			$baseUrl . '/lib/pkp/js/lib/jquery/plugins/validate/jquery.validate.min.js',
			$args
		);
		$localePath = '/lib/pkp/js/lib/jquery/plugins/validate/localization/messages_';
		foreach ($localeChecks as $localeCheck) {
			if (file_exists($basePath . $localePath . $localeCheck .'.js')) {
				$this->addJavaScript('jqueryValidateLocale', $baseUrl . $localePath . $localeCheck . '.js', $args);
			}
		}

		$this->addJavaScript(
			'plUpload',
			$baseUrl . '/lib/pkp/lib/vendor/moxiecode/plupload/js/plupload.full.min.js',
			$args
		);
		$this->addJavaScript(
			'jQueryPlUpload',
			$baseUrl . '/lib/pkp/lib/vendor/moxiecode/plupload/js/jquery.ui.plupload/jquery.ui.plupload.js',
			$args
		);
		$localePath = '/lib/pkp/lib/vendor/moxiecode/plupload/js/i18n/';
		foreach ($localeChecks as $localeCheck) {
			if (file_exists($basePath . $localePath . $localeCheck . '.js')) {
				$this->addJavaScript('plUploadLocale', $baseUrl . $localePath . $localeCheck . '.js.', $args);
			}
		}

		$this->addJavaScript('pNotify', $baseUrl . '/lib/pkp/js/lib/pnotify/pnotify.core.js', $args);
		$this->addJavaScript('pNotifyButtons', $baseUrl . '/lib/pkp/js/lib/pnotify/pnotify.buttons.js', $args);

		// Load minified file if it exists
		if (Config::getVar('general', 'enable_minified')) {
			$path = $basePath . '/js/pkp.min.js';
			if (file_exists($path)) {
				$this->addJavaScript(
					'pkpLib',
					$path,
					array(
						'priority' => STYLE_SEQUENCE_CORE,
						'contexts' => array('backend', 'frontend')
					)
				);
				return;
			}
		}

		// Otherwise retrieve and register all script files
		$minifiedScripts = array_filter(array_map('trim', file('registry/minifiedScripts.txt')), function($s) {
			return strlen($s) && $s[0] != '#'; // Exclude empty and commented (#) lines
		});
		foreach ($minifiedScripts as $key => $script) {
			$this->addJavaScript( 'pkpLib' . $key, "$baseUrl/$script", $args);
		}
	}

	/**
	 * Register JavaScript data used by the core JS library
	 *
	 * This function registers script data that is required by the core JS
	 * library. This data is queued after jQuery but before the pkp-lib
	 * framework, allowing dynamic data to be passed to the framework. It is
	 * intended to be used for passing constants and locale strings, but plugins
	 * may also take advantage of a hook to include data required by their own
	 * scripts, when integrating with the pkp-lib framework.
	 */
	function registerJSLibraryData() {

		$application = PKPApplication::getApplication();

		// Instantiate the namespace
		$output = '$.pkp = $.pkp || {};';

		// Load data intended for general use by the app
		$app_data = array(
			'baseUrl' => $this->_request->getBaseUrl(),
		);
		$output .= '$.pkp.app = ' . json_encode($app_data) . ';';

		// Load exposed constants
		$exposedConstants = $application->getExposedConstants();
		if (!empty($exposedConstants)) {
			$output .= '$.pkp.cons = ' . json_encode($exposedConstants) . ';';
		}

		// Load locale keys
		$localeKeys = $application->getJSLocaleKeys();
		if (!empty($localeKeys)) {

			// Replace periods in the key name with underscores for better-
			// formatted JS keys
			$jsLocaleKeys = array();
			foreach($localeKeys as $key) {
				$jsLocaleKeys[str_replace('.', '_', $key)] = __($key);
			}

			$output .= '$.pkp.locale = ' . json_encode($jsLocaleKeys) . ';';
		}

		// Allow plugins to load data within their own namespace
		$plugin_data = array();
		HookRegistry::call('TemplateManager::registerJSLibraryData', array(&$plugin_data));

		if (!empty($plugin_data) && is_array($plugin_data)) {
			$output .= '$.pkp.plugins = {};';
			foreach($plugin_data as $namespace => $data) {
				$output .= $namespace . ' = ' . json_encode($data) . ';';
			}
		}

		$this->addJavaScript(
			'pkpLibData',
			$output,
			array(
				'priority' => STYLE_SEQUENCE_CORE,
				'contexts' => 'backend',
				'inline'   => true,
			)
		);
	}

	/**
	 * @copydoc Smarty::fetch()
	 */
	function fetch($template, $cache_id = null, $compile_id = null, $display = false) {

		// If no compile ID was assigned, get one.
		if (!$compile_id) $compile_id = $this->getCompileId($template);

		// Give hooks an opportunity to override
		$result = null;
		if (HookRegistry::call($display?'TemplateManager::display':'TemplateManager::fetch', array($this, $template, $cache_id, $compile_id, &$result))) return $result;

		return parent::fetch($template, $cache_id, $compile_id, $display);
	}

	/**
	 * Fetch content via AJAX and add it to the DOM, wrapped in a container element.
	 * @param $id string ID to use for the generated container element.
	 * @param $url string URL to fetch the contents from.
	 * @param $element string Element to use for container.
	 * @return JSONMessage The JSON-encoded result.
	 */
	function fetchAjax($id, $url, $element = 'div') {
		return new JSONMessage(true, $this->smartyLoadUrlInEl(
			array(
				'url' => $url,
				'id' => $id,
				'el' => $element,
			),
			$this
		));
	}

	/**
	 * Calculate a compile ID for a resource.
	 * @param $resourceName string Resource name.
	 * @return string
	 */
	function getCompileId($resourceName) {

		if ( Config::getVar('general', 'installed' ) ) {
			$context = $this->_request->getContext();
			if (is_a($context, 'Context')) {
				$resourceName .= $context->getSetting('themePluginPath');
			}
		}

		return sha1($resourceName);
	}

	/**
	 * Returns the template results as a JSON message.
	 * @param $template string Template filename (or Smarty resource name)
	 * @param $status boolean
	 * @return JSONMessage JSON object
	 */
	function fetchJson($template, $status = true) {
		import('lib.pkp.classes.core.JSONMessage');
		return new JSONMessage($status, $this->fetch($template));
	}

	/**
	 * @copydoc Smarty::display()
	 * @param $template string Template filename (or Smarty resource name)
	 * @param $sendHeaders boolean True iff content type/cache control headers should be sent
	 */
	function display($template, $cache_id = null, $compile_id = null, $sendHeaders = true) {
		// Give any hooks registered against the TemplateManager
		// the opportunity to modify behavior; otherwise, display
		// the template as usual.

		$output = null;
		if (HookRegistry::call('TemplateManager::display', array($this, &$template, &$output))) {
			echo $output;
			return;
		}

		// If this is the main display call, send headers.
		if ($sendHeaders) {
			// Explicitly set the character encoding. Required in
			// case server is using Apache's AddDefaultCharset
			// directive (which can prevent browser auto-detection
			// of the proper character set).
			header('Content-Type: text/html; charset=' . Config::getVar('i18n', 'client_charset'));
			header('Cache-Control: ' . $this->_cacheability);
		}

		// Actually display the template.
		parent::display($template, $cache_id, $compile_id);
	}


	/**
	 * Clear template compile and cache directories.
	 */
	function clearTemplateCache() {
		$this->clear_compiled_tpl();
		$this->clear_all_cache();
	}

	/**
	 * Clear all compiled CSS files
	 */
	public function clearCssCache() {
		$cacheDirectory = CacheManager::getFileCachePath();
		$files = scandir($cacheDirectory);
		array_map('unlink', glob(CacheManager::getFileCachePath() . DIRECTORY_SEPARATOR . '*.' . CSS_FILENAME_SUFFIX));
	}

	/**
	 * Return an instance of the template manager.
	 * @param $request PKPRequest
	 * @return TemplateManager the template manager object
	 */
	static function &getManager($request = null) {
		if (!isset($request)) {
			$request = Registry::get('request');
			if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated call without request object.');
		}
		assert(is_a($request, 'PKPRequest'));

		$instance =& Registry::get('templateManager', true, null); // Reference required

		if ($instance === null) {
			$instance = new TemplateManager($request);
			$themes = PluginRegistry::getPlugins('themes');
			if (is_null($themes)) {
				$themes = PluginRegistry::loadCategory('themes', true);
			}
			$instance->initialize();
		}

		return $instance;
	}

	/**
	 * Return an instance of the Form Builder Vocabulary class.
	 * @return TemplateManager the template manager object
	 */
	function getFBV() {
		if(!$this->_fbv) {
			import('lib.pkp.classes.form.FormBuilderVocabulary');
			$this->_fbv = new FormBuilderVocabulary();
		}
		return $this->_fbv;
	}


	//
	// Custom template functions, modifiers, etc.
	//

	/**
	 * Smarty usage: {translate key="localization.key.name" [paramName="paramValue" ...]}
	 *
	 * Custom Smarty function for translating localization keys.
	 * Substitution works by replacing tokens like "{$foo}" with the value of the parameter named "foo" (if supplied).
	 * @param $params array associative array, must contain "key" parameter for string to translate plus zero or more named parameters for substitution.
	 * 	Translation variables can be specified also as an optional
	 * 	associative array named "params".
	 * @param $smarty Smarty
	 * @return string the localized string, including any parameter substitutions
	 */
	function smartyTranslate($params, $smarty) {
		if (isset($params) && !empty($params)) {
			if (!isset($params['key'])) return __('');

			$key = $params['key'];
			unset($params['key']);
			if (isset($params['params']) && is_array($params['params'])) {
				$paramsArray = $params['params'];
				unset($params['params']);
				$params = array_merge($params, $paramsArray);
			}
			return __($key, $params);
		}
	}

	/**
	 * Smarty usage: {null_link_action id="linkId" key="localization.key.name" image="imageClassName"}
	 *
	 * Custom Smarty function for displaying a null link action; these will
	 * typically be attached and handled in Javascript.
	 * @param $smarty Smarty
	 * @return string the HTML for the generated link action
	 */
	function smartyNullLinkAction($params, $smarty) {
		assert(isset($params['id']));

		$id = $params['id'];
		$key = isset($params['key'])?$params['key']:null;
		$hoverTitle = isset($params['hoverTitle'])?true:false;
		$image = isset($params['image'])?$params['image']:null;
		$translate = isset($params['translate'])?false:true;

		import('lib.pkp.classes.linkAction.request.NullAction');
		import('lib.pkp.classes.linkAction.LinkAction');
		$key = $translate ? __($key) : $key;
		$this->assign('action', new LinkAction(
			$id, new NullAction(), $key, $image
		));

		$this->assign('hoverTitle', $hoverTitle);
		return $this->fetch('linkAction/linkAction.tpl');
	}

	/**
	 * Smarty usage: {help file="someFile.md" section="someSection" textKey="some.text.key"}
	 *
	 * Custom Smarty function for displaying a context-sensitive help link.
	 * @param $smarty Smarty
	 * @return string the HTML for the generated link action
	 */
	function smartyHelp($params, $smarty) {
		assert(isset($params['file']));

		$params = array_merge(
			array(
				'file' => null, // The name of the Markdown file
				'section' => null, // The (optional) anchor within the Markdown file
				'textKey' => 'help.help', // An (optional) locale key for the link
				'text' => null, // An (optional) literal text for the link
				'class' => null, // An (optional) CSS class string for the link
			),
			$params
		);

		$this->assign(array(
			'helpFile' => $params['file'],
			'helpSection' => $params['section'],
			'helpTextKey' => $params['textKey'],
			'helpText' => $params['text'],
			'helpClass' => $params['class'],
		));

		return $this->fetch('common/helpLink.tpl');
	}

	/**
	 * Smarty usage: {html_options_translate ...}
	 * For parameter usage, see http://smarty.php.net/manual/en/language.function.html.options.php
	 *
	 * Identical to Smarty's "html_options" function except option values are translated from i18n keys.
	 * @param $params array
	 * @param $smarty Smarty
	 */
	function smartyHtmlOptionsTranslate($params, $smarty) {
		if (isset($params['options'])) {
			if (isset($params['translateValues'])) {
				// Translate values AND output
				$newOptions = array();
				foreach ($params['options'] as $k => $v) {
					$newOptions[__($k)] = __($v);
				}
				$params['options'] = $newOptions;
			} else {
				// Just translate output
				$params['options'] = array_map(array('AppLocale', 'translate'), $params['options']);
			}
		}

		if (isset($params['output'])) {
			$params['output'] = array_map(array('AppLocale', 'translate'), $params['output']);
		}

		if (isset($params['values']) && isset($params['translateValues'])) {
			$params['values'] = array_map(array('AppLocale', 'translate'), $params['values']);
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
	function smartyIterate($params, $content, $smarty, &$repeat) {
		$iterator =& $smarty->get_template_vars($params['from']);

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
	 * Display page information for a listing of items that has been
	 * divided onto multiple pages.
	 * Usage:
	 * {page_info from=$myIterator}
	 */
	function smartyPageInfo($params, $smarty) {
		$iterator = $params['iterator'];

		if (isset($params['itemsPerPage'])) {
			$itemsPerPage = $params['itemsPerPage'];
		} else {
			$itemsPerPage = $smarty->get_template_vars('itemsPerPage');
			if (!is_numeric($itemsPerPage)) $itemsPerPage=25;
		}

		$page = $iterator->getPage();
		$pageCount = $iterator->getPageCount();
		$itemTotal = $iterator->getCount();

		if ($pageCount<1) return '';

		$from = (($page - 1) * $itemsPerPage) + 1;
		$to = min($itemTotal, $page * $itemsPerPage);

		return __('navigation.items', array(
			'from' => ($to===0?0:$from),
			'to' => $to,
			'total' => $itemTotal
		));
	}

	/**
	 * Flush the output buffer. This is useful in cases where Smarty templates
	 * are calling functions that take a while to execute so that they can display
	 * a progress indicator or a message stating that the operation may take a while.
	 */
	function smartyFlush($params, $smarty) {
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
	function smartyCallHook($params, $smarty) {
		$output = null;
		HookRegistry::call($params['name'], array(&$params, &$smarty, &$output));
		return $output;
	}

	/**
	 * Generate a URL into a PKPApp.
	 * @param $params array
	 * @param $smarty object
	 * Available parameters:
	 * - router: which router to use
	 * - context
	 * - page
	 * - component
	 * - op
	 * - path (array)
	 * - anchor
	 * - escape (default to true unless otherwise specified)
	 * - params: parameters to include in the URL if available as an array
	 */
	function smartyUrl($parameters, $smarty) {
		if ( !isset($parameters['context']) ) {
			// Extract the variables named in $paramList, and remove them
			// from the parameters array. Variables remaining in params will be
			// passed along to Request::url as extra parameters.
			$context = array();
			$application = PKPApplication::getApplication();
			$contextList = $application->getContextList();
			foreach ($contextList as $contextName) {
				if (isset($parameters[$contextName])) {
					$context[$contextName] = $parameters[$contextName];
					unset($parameters[$contextName]);
				} else {
					$context[$contextName] = null;
				}
			}
			$parameters['context'] = $context;
		}

		// Extract the reserved variables named in $paramList, and remove them
		// from the parameters array. Variables remaining in parameters will be passed
		// along to Request::url as extra parameters.
		$paramList = array('params', 'router', 'context', 'page', 'component', 'op', 'path', 'anchor', 'escape');
		foreach ($paramList as $parameter) {
			if (isset($parameters[$parameter])) {
				$$parameter = $parameters[$parameter];
				unset($parameters[$parameter]);
			} else {
				$$parameter = null;
			}
		}

		// Merge parameters specified in the {url paramName=paramValue} format with
		// those optionally supplied in {url params=$someAssociativeArray} format
		$parameters = array_merge($parameters, (array) $params);

		// Set the default router
		if (is_null($router)) {
			if (is_a($this->_request->getRouter(), 'PKPComponentRouter')) {
				$router = ROUTE_COMPONENT;
			} else {
				$router = ROUTE_PAGE;
			}
		}

		// Check the router
		$dispatcher = PKPApplication::getDispatcher();
		$routerShortcuts = array_keys($dispatcher->getRouterNames());
		assert(in_array($router, $routerShortcuts));

		// Identify the handler
		switch($router) {
			case ROUTE_PAGE:
				$handler = $page;
				break;

			case ROUTE_COMPONENT:
				$handler = $component;
				break;

			default:
				// Unknown router type
				assert(false);
		}

		// Let the dispatcher create the url
		return $dispatcher->url($this->_request, $router, $context, $handler, $op, $path, $parameters, $anchor, !isset($escape) || $escape);
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
	function smartyPageLinks($params, $smarty) {
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

		$pageBase = max($page - floor($numPageLinks / 2), 1);
		$paramName = $name . 'Page';

		if ($pageCount<=1) return '';

		$value = '';

		$router = $this->_request->getRouter();
		$requestedArgs = null;
		if (is_a($router, 'PageRouter')) {
			$requestedArgs = $router->getRequestedArgs($this->_request);
		}

		if ($page>1) {
			$params[$paramName] = 1;
			$value .= '<a href="' . $this->_request->url(null, null, null, $requestedArgs, $params, $anchor) . '"' . $allExtra . '>&lt;&lt;</a>&nbsp;';
			$params[$paramName] = $page - 1;
			$value .= '<a href="' . $this->_request->url(null, null, null, $requestedArgs, $params, $anchor) . '"' . $allExtra . '>&lt;</a>&nbsp;';
		}

		for ($i=$pageBase; $i<min($pageBase+$numPageLinks, $pageCount+1); $i++) {
			if ($i == $page) {
				$value .= "<strong>$i</strong>&nbsp;";
			} else {
				$params[$paramName] = $i;
				$value .= '<a href="' . $this->_request->url(null, null, null, $requestedArgs, $params, $anchor) . '"' . $allExtra . '>' . $i . '</a>&nbsp;';
			}
		}
		if ($page < $pageCount) {
			$params[$paramName] = $page + 1;
			$value .= '<a href="' . $this->_request->url(null, null, null, $requestedArgs, $params, $anchor) . '"' . $allExtra . '>&gt;</a>&nbsp;';
			$params[$paramName] = $pageCount;
			$value .= '<a href="' . $this->_request->url(null, null, null, $requestedArgs, $params, $anchor) . '"' . $allExtra . '>&gt;&gt;</a>&nbsp;';
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
	 * Concatenate the parameters and return the result.
	 */
	function smartyConcat() {
		$args = func_get_args();
		return implode('', $args);
	}

	/**
	 * Concatenate the parameters and return the result.
	 * @param $a mixed Parameter A
	 * @param $a mixed Parameter B
	 * @param $strict boolean True iff a strict (===) compare should be used
	 * @param $invert booelan True iff the output should be inverted
	 */
	function smartyCompare($a, $b, $strict = false, $invert = false) {
		$result = $strict?$a===$b:$a==$b;
		return $invert?!$result:$result;
	}

	/**
	 * Convert a string to a numeric time.
	 */
	function smartyStrtotime($string) {
		return strtotime($string);
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
			$this->assign($varName, $value);
		}
		if ($passThru) return $value;
	}

	/**
	 * Smarty usage: {load_url_in_el el="htmlElement" id="someHtmlId" url="http://the.url.to.be.loaded.into.the.grid"}
	 *
	 * Custom Smarty function for loading a URL via AJAX into any HTML element
	 * @param $params array associative array
	 * @param $smarty Smarty
	 * @return string of HTML/Javascript
	 */
	function smartyLoadUrlInEl($params, $smarty) {
		// Required Params
		if (!isset($params['el'])) {
			$smarty->trigger_error("el parameter is missing from load_url_in_el");
		}
		if (!isset($params['url'])) {
			$smarty->trigger_error("url parameter is missing from load_url_in_el");
		}
		if (!isset($params['id'])) {
			$smarty->trigger_error("id parameter is missing from load_url_in_el");
		}

		$this->assign(array(
			'inEl' => $params['el'],
			'inElUrl' => $params['url'],
			'inElElId' => $params['id'],
			'inElClass' => isset($params['class'])?$params['class']:null,
		));

		if (isset($params['placeholder'])) {
			$this->assign('inElPlaceholder', $params['placeholder']);
		} elseif (isset($params['loadMessageId'])) {
			$loadMessageId = $params['loadMessageId'];
			$this->assign('inElPlaceholder', __($loadMessageId, $params));
		} else {
			$this->assign('inElPlaceholder', $this->fetch('common/loadingContainer.tpl'));
		}

		return $this->fetch('common/urlInEl.tpl');
	}

	/**
	 * Smarty usage: {load_url_in_div id="someHtmlId" url="http://the.url.to.be.loaded.into.the.grid"}
	 *
	 * Custom Smarty function for loading a URL via AJAX into a DIV. Convenience
	 * wrapper for smartyLoadUrlInEl.
	 * @param $params array associative array
	 * @param $smarty Smarty
	 * @return string of HTML/Javascript
	 */
	function smartyLoadUrlInDiv($params, $smarty) {
		$params['el'] = 'div';
		return $this->smartyLoadUrlInEl( $params, $smarty );
	}

	/**
	 * Smarty usage: {csrf}
	 *
	 * Custom Smarty function for inserting a CSRF token.
	 * @param $params array associative array
	 * @param $smarty Smarty
	 * @return string of HTML
	 */
	function smartyCSRF($params, $smarty) {
		return '<input type="hidden" name="csrfToken" value="' . htmlspecialchars($this->_request->getSession()->getCSRFToken()) . '">';
	}

	/**
	 * Smarty usage: {load_stylesheet context="frontend" stylesheets=$stylesheets}
	 *
	 * Custom Smarty function for printing stylesheets attached to a context.
	 * @param $params array associative array
	 * @param $smarty Smarty
	 * @return string of HTML/Javascript
	 */
	function smartyLoadStylesheet($params, $smarty) {

		if (empty($params['context'])) {
			$context = 'frontend';
		}

		$stylesheets = $this->getResourcesByContext($this->_styleSheets, $params['context']);

		ksort($stylesheets);

		$output = '';
		foreach($stylesheets as $priorityList) {
			foreach($priorityList as $style) {
				if (!empty($style['inline'])) {
					$output .= '<style type="text/css">' . $style['style'] . '</style>';
				} else {
					$output .= '<link rel="stylesheet" href="' . $style['style'] . '" type="text/css" />';
				}
			}
		}

		return $output;
	}

	/**
	 * Smarty usage: {load_script context="backend" scripts=$scripts}
	 *
	 * Custom Smarty function for printing scripts attached to a context.
	 * @param $params array associative array
	 * @param $smarty Smarty
	 * @return string of HTML/Javascript
	 */
	function smartyLoadScript($params, $smarty) {

		if (empty($params['context'])) {
			$params['context'] = 'frontend';
		}

		$scripts = $this->getResourcesByContext($this->_javaScripts, $params['context']);

		ksort($scripts);

		$output = '';
		foreach($scripts as $priorityList) {
			foreach($priorityList as $name => $data) {
				if ($data['inline']) {
					$output .= '<script type="text/javascript">' . $data['script'] . '</script>';
				} else {
					$output .= '<script src="' . $data['script'] . '" type="text/javascript"></script>';
				}
			}
		}

		return $output;
	}

	/**
	 * Smarty usage: {load_header context="frontent" headers=$headers}
	 *
	 * Custom Smarty function for printing scripts attached to a context.
	 * @param $params array associative array
	 * @param $smarty Smarty
	 * @return string of HTML/Javascript
	 */
	function smartyLoadHeader($params, $smarty) {

		if (empty($params['context'])) {
			$params['context'] = 'frontend';
		}

		$headers = $this->getResourcesByContext($this->_htmlHeaders, $params['context']);

		ksort($headers);

		$output = '';
		foreach($headers as $priorityList) {
			foreach($priorityList as $name => $data) {
				$output .= "\n" . $data['header'];
			}
		}

		return $output;
	}

	/**
	 * Get resources assigned to a context
	 *
	 * A helper function which retrieves script, style and header assets
	 * assigned to a particular context.
	 * @param $resources array Requested resources
	 * @param $context string Requested context
	 * @return array Resources assigned to these contexts
	 */
	function getResourcesByContext($resources, $context) {

		$matches = array();

		if (array_key_exists($context, $resources)) {
			$matches = $resources[$context];
		}

		$page = $this->get_template_vars('requestedPage');
		$page = empty( $page ) ? 'index' : $page;
		$op = $this->get_template_vars('requestedOp');
		$op = empty( $op ) ? 'index' : $op;

		$contexts = array(
			join('-', array($context, $page)),
			join('-', array($context, $page, $op)),
		);

		foreach($contexts as $context) {
			if (array_key_exists($context, $resources)) {
				foreach ($resources[$context] as $priority => $priorityList) {
					if (!array_key_exists($priority, $matches)) {
						$matches[$priority] = array();
					}
					$matches[$priority] = array_merge($matches[$priority], $resources[$context][$priority]);
				}
				$matches += $resources[$context];
			}
		}

		return $matches;
	}

	/**
	 * Smarty usage: {pluck_files files=$availableFiles by="chapter" value=$chapterId}
	 *
	 * Custom Smarty function for plucking files from the array of $availableFiles
	 * related to a submission. Intended to be used on the frontend
	 * @param $params array associative array
	 * @param $smarty Smarty
	 * @return array of SubmissionFile objects
	 */
	function smartyPluckFiles($params, $smarty) {

		// $params['files'] should be an array of SubmissionFile objects
		if (!is_array($params['files'])) {
			error_log('Smarty: {pluck_files} function called without required `files` param. Called in ' . __FILE__ . ':' . __LINE__);
			return array();
		}

		// $params['by'] is one of an approved list of attributes to select by
		if (empty($params['by'])) {
			error_log('Smarty: {pluck_files} function called without required `by` param. Called in ' . __FILE__ . ':' . __LINE__);
			return array();
		}

		// The approved list of `by` attributes
		// chapter Any files assigned to a chapter ID. A value of `any` will return files assigned to any chapter. A value of 0 will return files not assigned to chapter
		// publicationFormat Any files in a given publicationFormat ID
		// component Any files of a component type by class name: SubmissionFile|SubmissionArtworkFile|SupplementaryFile
		// fileExtension Any files with a file extension in all caps: PDF
		// genre Any files with a genre ID (file genres are configurable but typically refer to Manuscript, Bibliography, etc)
		if (!in_array($params['by'], array('chapter','publicationFormat','component','fileExtension','genre'))) {
			error_log('Smarty: {pluck_files} function called without a valid `by` param. Called in ' . __FILE__ . ':' . __LINE__);
			return array();
		}

		// The value to match against. See docs for `by` param
		if (!isset($params['value'])) {
			error_log('Smarty: {pluck_files} function called without required `value` param. Called in ' . __FILE__ . ':' . __LINE__);
			return array();
		}

		// The variable to assign the result to.
		if (empty($params['assign'])) {
			error_log('Smarty: {pluck_files} function called without required `assign` param. Called in ' . __FILE__ . ':' . __LINE__);
			return array();
		}

		$matching_files = array();

		$genreDao = DAORegistry::getDAO('GenreDAO');
		foreach ($params['files'] as $file) {
			switch ($params['by']) {

				case 'chapter':
					$genre = $genreDao->getById($file->getGenreId());
					if (!$genre->getDependent() && method_exists($file, 'getChapterId')) {
						if ($params['value'] === 'any' && $file->getChapterId()) {
							$matching_files[] = $file;
						} elseif($file->getChapterId() === $params['value']) {
							$matching_files[] = $file;
						} elseif ($params['value'] == 0 && !$file->getChapterId()) {
							$matching_files[] = $file;
						}
					}
					break;

				case 'publicationFormat':
					if ($file->getAssocId() == $params['value']) {
						$matching_files[] = $file;
					}
					break;

				case 'component':
					if (get_class($file) == $params['value']) {
						$matching_files[] = $file;
					}
					break;

				case 'fileExtension':
					if ($file->getExtension() == $params['value']) {
						$matching_files[] = $file;
					}
					break;

				case 'genre':
					if ($file->getGenreId() == $params['value']) {
						$matching_files[] = $file;
					}
					break;
			}
		}

		$smarty->assign($params['assign'], $matching_files);
	}
}

?>
