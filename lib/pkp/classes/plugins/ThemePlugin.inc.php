<?php

/**
 * @file classes/plugins/ThemePlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ThemePlugin
 * @ingroup plugins
 *
 * @brief Abstract class for theme plugins
 */

import('lib.pkp.classes.plugins.LazyLoadPlugin');

define('LESS_FILENAME_SUFFIX', '.less');
define('THEME_OPTION_PREFIX', 'themeOption_');

abstract class ThemePlugin extends LazyLoadPlugin {
	/**
	 * Collection of styles
	 *
	 * @see self::_registerStyles
	 * @var $styles array
	 */
	public $styles = array();

	/**
	 * Collection of scripts
	 *
	 * @see self::_registerScripts
	 * @var $scripts array
	 */
	public $scripts = array();

	/**
	 * Theme-specific options
	 *
	 * @var $options array;
	 */
	public $options = array();

	/**
	 * Parent theme (optional)
	 *
	 * @var $parent ThemePlugin
	 */
	public $parent;

	/**
	 * Stored reference to option values
	 *
	 * A null value indicates that no lookup has occured. If no options are set,
	 * the lookup will assign an empty array.
	 *
	 * @var $optionValues null|array;
	 */
	private $_optionValues = null;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * @copydoc Plugin::register
	 */
	function register($category, $path) {
		if (!parent::register($category, $path)) return false;

		// Don't perform any futher operations if theme is not currently active
		if (!$this->isActive()) {
			return true;
		}

		// Themes must initialize their functionality after all theme plugins
		// have been loaded in order to make use of parent/child theme
		// relationships
		HookRegistry::register('PluginRegistry::categoryLoaded::themes', array($this, 'themeRegistered'));
		HookRegistry::register('PluginRegistry::categoryLoaded::themes', array($this, 'initAfter'));

		// Save any theme options displayed on the appearance and site settings
		// forms
		HookRegistry::register('appearanceform::execute', array($this, 'saveOptionsForm'));
		HookRegistry::register('appearanceform::readuservars', array($this, 'readOptionsFormUserVars'));
		HookRegistry::register('sitesetupform::execute', array($this, 'saveOptionsForm'));
		HookRegistry::register('sitesetupform::readuservars', array($this, 'readOptionsFormUserVars'));

		return true;
	}

	/**
	 * Fire the init() method when a theme is registered
	 *
	 * @param $themes array List of all loaded themes
	 * @return null
	 */
	public function themeRegistered($themes) {

		// Don't fully initialize the theme until OJS is installed, so that
		// there are no requests to the database before it exists
		if (defined('SESSION_DISABLE_INIT')) {
			return;
		}

		$this->init();
	}

	/**
	 * The primary method themes should use to add styles, scripts and fonts,
	 * or register hooks. This method is only fired for the currently active
	 * theme.
	 *
	 * @return null
	 */
	public abstract function init();

	/**
	 * Perform actions after the theme has been initialized
	 *
	 * Registers templates, styles and scripts that have been added by the
	 * theme or any parent themes
	 */
	public function initAfter() {
		$this->_registerTemplates();
		$this->_registerStyles();
		$this->_registerScripts();
	}

	/**
	 * Determine whether or not this plugin is currently active
	 *
	 * This only returns true if the theme is currently the selected theme
	 * in a given context. Use self::getEnabled() if you want to know if the
	 * theme is available for use on the site.
	 *
	 * @return boolean
	 */
	public function isActive() {
		if (defined('SESSION_DISABLE_INIT')) return false;
		$request = $this->getRequest();
		$context = $request->getContext();
		if (is_a($context, 'Context')) {
			$activeTheme = $context->getSetting('themePluginPath');
		} else {
			$site = $request->getSite();
			$activeTheme = $site->getSetting('themePluginPath');
		}

		return $activeTheme == basename($this->getPluginPath());
	}

	/**
	 * Add a stylesheet to load with this theme
	 *
	 * Style paths with a .less extension will be compiled and redirected to
	 * the compiled file.
	 *
	 * @param $name string A name for this stylesheet
	 * @param $style string The stylesheet. Should be a path relative to the
	 *   theme directory or, if the `inline` argument is included, style data to
	 *   be output.
	 * @param $args array Optional arguments hash. Supported args:
	 *   'context': Whether to load this on the `frontend` or `backend`.
	 *      default: `frontend`
	 *   'priority': Controls order in which styles are printed
	 *   'addLess': Additional LESS files to process before compiling. Array
	 *   'addLessVariables': A string containing additional LESS variables to
	 *      parse before compiling. Example: "@bg:#000;"
	 *   `inline` bool Whether the $style value should be output directly as
	 *      style data.
	 */
	public function addStyle($name, $style, $args = array()) {

		// Pass a file path for LESS files
		if (substr($style, (strlen(LESS_FILENAME_SUFFIX) * -1)) === LESS_FILENAME_SUFFIX) {
			$args['style'] = $this->_getBaseDir($style);

		// Pass a URL for other files
		} elseif (empty($args['inline'])) {
			if (isset($args['baseUrl'])) {
				$args['style'] = $args['baseUrl'] . $style;
			} else {
				$args['style'] = $this->_getBaseUrl($style);
			}

		// Leave inlined styles alone
		} else {
			$args['style'] = $style;
		}

		// Generate file paths for any additional LESS files to compile with
		// this style
		if (isset($args['addLess'])) {
			foreach ($args['addLess'] as &$file) {
				$file = $this->_getBaseDir($file);
			}
		}

		$this->styles[$name] = $args;
	}

	/**
	 * Modify the params of an existing stylesheet
	 *
	 * @param $name string The name of the stylesheet to modify
	 * @param $args array Parameters to modify.
	 * @see self::addStyle()
	 * @return null
	 */
	public function modifyStyle($name, $args = array()) {

		$style = &$this->getStyle($name);

		if (empty($style)) {
			return;
		}

		if (isset($args['addLess'])) {
			foreach ($args['addLess'] as &$file) {
				$file = $this->_getBaseDir($file);
			}
		}

		if (isset($args['style']) && !isset($args['inline'])) {
			$args['style'] = substr($args['style'], (strlen(LESS_FILENAME_SUFFIX) * -1)) == LESS_FILENAME_SUFFIX ? $this->_getBaseDir($args['style']) : $this->_getBaseUrl($args['style']);
		}

		$style = array_merge($style, $args);
	}

	/**
	 * Remove a registered stylesheet
	 *
	 * @param $name string The name of the stylesheet to remove
	 * @return bool Whether or not the stylesheet was found and removed.
	 */
	public function removeStyle($name) {

		if (isset($this->styles[$name])) {
			unset($this->styles[$name]);
			return true;
		}

		return $this->parent ? $this->parent->removeStyle($name) : false;
	}

	/**
	 * Get a style from this theme or any parent theme
	 *
	 * @param $name string The name of the style to retrieve
	 * @return array|null Reference to the style or null if not found
	 */
	public function &getStyle($name) {

		// Search this theme
		if (isset($this->styles[$name])) {
			$style = &$this->styles[$name];
			return $style;
		}

		// If no parent theme, no style was found
		if (!isset($this->parent)) {
			$style = null;
			return $style;
		}

		return $this->parent->getStyle($name);
	}

	/**
	 * Add a script to load with this theme
	 *
	 * @param $name string A name for this script
	 * @param $script string The script to be included. Should be path relative
	 *   to the theme or, if the `inline` argument is included, script data to
	 *   be output.
	 * @param $args array Optional arguments hash. Supported args:
	 *   `context` string Whether to load this on the `frontend` or `backend`.
	 *      default: frontend
	 *   `priority` int Controls order in which scripts are printed
	 *      default: STYLE_SEQUENCE_NORMAL
	 *   `inline` bool Whether the $script value should be output directly as
	 *      script data. Used to pass backend data to the scripts.
	 */
	public function addScript($name, $script, $args = array()) {

		if (!empty($args['inline'])) {
			$args['script'] = $script;
		} elseif (isset($args['baseUrl'])) {
			$args['script'] = $args['baseUrl'] . $script;
		} else {
			$args['script'] = $this->_getBaseUrl($script);
		}

		$this->scripts[$name] = $args;
	}

	/**
	 * Modify the params of an existing script
	 *
	 * @param $name string The name of the script to modify
	 * @param $args array Parameters to modify.
	 * @see self::addScript()
	 * @return null
	 */
	public function modifyScript($name, $args = array()) {

		$script = &$this->getScript($name);

		if (empty($script)) {
			return;
		}

		if (isset($args['path'])) {
			$args['path'] = $this->_getBaseUrl($args['path']);
		}

		$script = array_merge( $script, $args );
	}

	/**
	 * Remove a registered script
	 *
	 * @param $name string The name of the script to remove
	 * @return bool Whether or not the stylesheet was found and removed.
	 */
	public function removeScript($name) {

		if (isset($this->scripts[$name])) {
			unset($this->scripts[$name]);
			return true;
		}

		return $this->parent ? $this->parent->removeScript($name) : false;
	}

	/**
	 * Get a script from this theme or any parent theme
	 *
	 * @param $name string The name of the script to retrieve
	 * @return array|null Reference to the script or null if not found
	 */
	public function &getScript($name) {

		// Search this theme
		if (isset($this->scripts[$name])) {
			$style = &$this->scripts[$name];
			return $style;
		}

		// If no parent theme, no script was found
		if (!isset($this->parent)) {
			return;
		}

		return $this->parent->getScript($name);
	}

	/**
	 * Add a theme option
	 *
	 * Theme options are added programmatically to the Settings > Website >
	 * Appearance form when this theme is activated. Common options are
	 * colour and typography selectors.
	 *
	 * @param $name string Unique name for this setting
	 * @param $type string A pre-registered type of setting. Supported values:
	 *   text|colour|radio. Default: `text`
	 * @param $args array Optional parameters defining this setting. Some setting
	 *   types may accept or require additional arguments.
	 *  `label` string Locale key for a label for this field.
	 *  `description` string Locale key for a description for this field.
	 *  `default` mixed A default value. Default: ''
	 */
	public function addOption($name, $type, $args = array()) {

		if (!empty($this->options[$name])) {
			return;
		}

		$this->options[$name] = array_merge(
			array('type' => $type),
			$args
		);
	}

	/**
	 * Get the value of an option or default if the option is not set
	 *
	 * @param $name The name of the option value to retrieve
	 * @return mixed The value of the option. Will return a default if set in
	 *  the option config. False if no option exists
	 */
	public function getOption($name) {

		// Check if this is a valid option
		if (!isset($this->options[$name])) {
			return $this->parent ? $this->parent->getOption($name) : false;
		}

		// Retrieve option values if they haven't been loaded yet
		if (is_null($this->_optionValues)) {
			$pluginSettingsDAO = DAORegistry::getDAO('PluginSettingsDAO');
			$context = Request::getContext();
			$contextId = $context ? $context->getId() : 0;
			$this->_optionValues = $pluginSettingsDAO->getPluginSettings($contextId, $this->getName());
		}

		if (isset($this->_optionValues[$name])) {
			return $this->_optionValues[$name];
		}

		// Return a default if no value is set
		$option = $this->getOptionConfig($name);
		return $option && isset($option['default']) ? $option['default'] : null;
	}

	/**
	 * Get an option's configuration settings
	 *
	 * This retrives option settings for any option attached to this theme or
	 * any parent theme.
	 *
	 * @param $name The name of the option config to retrieve
	 * @return false|array The config array for this option. Or false if no
	 *  config is found.
	 */
	public function getOptionConfig($name) {

		if (isset($this->options[$name])) {
			return $this->options[$name];
		}

		return $this->parent ? $this->parent->getOptionConfig($name) : false;
	}

	/**
	 * Get all options' configuration settings.
	 *
	 * This retrieves a single array containing options settings for this
	 * theme and any parent themes.
	 *
	 * @return array
	 */
	public function getOptionsConfig() {

		if (!$this->parent) {
			return $this->options;
		}

		return array_merge(
			$this->parent->getOptionsConfig(),
			$this->options
		);
	}

	/**
	 * Modify option configuration settings
	 *
	 * @param $name The name of the option config to retrieve
	 * @param $args The new configuration settings for this option
	 * @return bool Whether the option was found and the config was updated.
	 */
	public function modifyOptionsConfig($name, $args = array()) {

		if (isset($this->options[$name])) {
			$this->options[$name] = $args;
			return true;
		}

		return $this->parent ? $this->parent->modifyOptionsConfig($name, $args) : false;
	}

	/**
	 * Remove an option
	 *
	 * @param $name The name of the option to remove
	 * @return bool Whether the option was found and removed
	 */
	public function removeOption($name) {

		if (isset($this->options[$name])) {
			unset($this->options[$name]);
			return true;
		}

		return $this->parent ? $this->parent->removeOption($name) : false;
	}

	/**
	 * Get all option values
	 *
	 * This retrieves a single array containing option values for this theme
	 * and any parent themes.
	 *
	 * @return array
	 */
	public function getOptionValues() {

		$pluginSettingsDAO = DAORegistry::getDAO('PluginSettingsDAO');

		$context = Request::getContext();
		$contextId = empty($context) ? 0 : $context->getId();
		$values = $pluginSettingsDAO->getPluginSettings($contextId, $this->getName());
		$values = array_intersect_key($values, $this->options);

		if (!$this->parent) {
			return $values;
		}

		return array_merge(
			$this->parent->getOptionValues(),
			$values
		);
	}

	/**
	 * Sanitize and save a theme option
	 *
	 * @param $name string A unique id for the option to save
	 * @param $value mixed The new value to save
	 * @param $contextId int Optional context id. Defaults to the current
	 *  context
	 */
	public function saveOption($name, $value, $contextId = null) {

		$option = !empty($this->options[$name]) ? $this->options[$name] : null;

		if (is_null($option)) {
			return $this->parent ? $this->parent->saveOption($name, $value, $contextId) : false;
		}

		$type = '';
		switch ($option['type']) {
			case 'text' :
			case 'select' :
			case 'colour' :
				$type = 'text';
				break;
		}

		if (is_null($contextId)) {
			$context = Request::getContext();
			$contextId = $context->getId();
		}

		$this->updateSetting($contextId, $name, $value, $type);

		// Clear the template cache so that new settings can take effect
		$templateMgr = TemplateManager::getManager($this->getRequest());
		$templateMgr->clearTemplateCache();
		$templateMgr->clearCssCache();
	}

	/**
	 * Save options in any form
	 *
	 * This helper function allows you to save theme options attached to any
	 * form by hooking into the form's execute function.
	 *
	 * @see Form::execute()
	 * @param $hookName string
	 * @param $args array Arguments passed via the hook
	 *  `form` Form The form object from which option values can be retrieved.
	 *  `request` Request
	 */
	public function saveOptionsForm($hookName, $args) {

		$form = $args[0];

		$options = $this->getOptionsConfig();

		// Ensure theme options from the site-wide settings form are applied
		// to the site-wide context
		if ($hookName == 'sitesetupform::execute') {
			$contextId = 0;
		}

		foreach ($options as $optionName => $optionArgs) {
			$value = $form->getData(THEME_OPTION_PREFIX . $optionName);
			if ($value === null) {
				continue;
			}
			if (isset($contextId)) {
				$this->saveOption($optionName, $value, $contextId);
			} else {
				$this->saveOption($optionName, $value);
			}
		}
	}

	/**
	 * Retrieve user-entered values for options from any form
	 *
	 * This helper function allows you to hook into any form to add theme option
	 * values to the form's user input data.
	 *
	 * @see Form::readUserVar()
	 * @param $hookName string
	 * @param $args array Arguments passed via the hook
	 *  `form` Form The form object from which option values can be retrieved.
	 *  `vars` Array Key/value store of the user vars read by the form
	 */
	public function readOptionsFormUserVars($hookName, $args) {

		$form = $args[0];

		$options = $this->getOptionsConfig();

		foreach ($options as $optionName => $optionArgs) {
			$fullOptionName = THEME_OPTION_PREFIX . $optionName;
			$form->setData($fullOptionName, Request::getUserVar($fullOptionName));
		}
	}

	/**
	 * Set a parent theme for this theme
	 *
	 * @param $parent string Key in the plugin registry for the parent theme
	 * @return null
	 */
	public function setParent($parent) {
		$parent = PluginRegistry::getPlugin('themes', $parent);

		if (!is_a($parent, 'ThemePlugin')) {
			return;
		}

		$this->parent = $parent;
		$this->parent->init();
	}

	/**
	 * Register directories to search for template files
	 *
	 * @return null
	 */
	private function _registerTemplates() {

		// Register parent theme template directory
		if (isset($this->parent) && is_a($this->parent, 'ThemePlugin')) {
			$this->parent->_registerTemplates();
		}

		// Register this theme's template directory
		$request = $this->getRequest();
		$templateManager = TemplateManager::getManager($request);
		array_unshift(
			$templateManager->template_dir,
			$this->_getBaseDir('templates')
		);
	}

	/**
	 * Register stylesheets and font assets
	 *
	 * Passes styles defined by the theme to the template manager for handling.
	 *
	 * @return null
	 */
	private function _registerStyles() {

		if (isset($this->parent)) {
			$this->parent->_registerStyles();
		}

		$request = $this->getRequest();
		$dispatcher = $request->getDispatcher();
		$templateManager = TemplateManager::getManager($request);

		foreach($this->styles as $name => $data) {

			if (empty($data['style'])) {
				continue;
			}

			// Compile LESS files
			if ($dispatcher && substr($data['style'], (strlen(LESS_FILENAME_SUFFIX) * -1)) == LESS_FILENAME_SUFFIX) {
				$styles = $dispatcher->url(
					$request,
					ROUTE_COMPONENT,
					null,
					'page.PageHandler',
					'css',
					null,
					array(
						'name' => $name,
					)
				);
			} else {
				$styles = $data['style'];
			}

			unset($data['style']);

			$templateManager->addStylesheet($name, $styles, $data);
		}
	}

	/**
	 * Register script assets
	 *
	 * Passes scripts defined by the theme to the template manager for handling.
	 *
	 * @return null
	 */
	public function _registerScripts() {

		if (isset($this->parent)) {
			$this->parent->_registerScripts();
		}

		$request = $this->getRequest();
		$templateManager = TemplateManager::getManager($request);

		foreach($this->scripts as $name => $data) {
			$script = $data['script'];
			unset($data['script']);
			$templateManager->addJavaScript($name, $script, $data);
		}
	}

	/**
	 * Get the base URL to be used for file paths
	 *
	 * A base URL for loading LESS/CSS/JS files in <link> elements. It will
	 * also be set to the @baseUrl variable before LESS files are compiloed so
	 * that images and fonts can be located.
	 *
	 * @param $path string An optional path to append to the base
	 * @return string
	 */
	public function _getBaseUrl($path = '') {
		$request = $this->getRequest();
		$path = empty($path) ? '' : DIRECTORY_SEPARATOR . $path;
		return $request->getBaseUrl() . DIRECTORY_SEPARATOR . $this->getPluginPath() . $path;
	}

	/**
	 * Get the base path to be used for file references
	 *
	 * @param $path string An optional path to append to the base
	 * @return string
	 */
	public function _getBaseDir($path = '') {
		$path = empty($path) ? '' : DIRECTORY_SEPARATOR . $path;
		return Core::getBaseDir() . DIRECTORY_SEPARATOR . $this->getPluginPath() . $path;
	}

	/**
	 * Check if the passed colour is dark
	 *
	 * This is a utility function to determine the darkness of a hex colour. This
	 * is designed to be used in theme colour options, so that text can be
	 * adjusted to ensure it's readable on light or dark backgrounds. You can
	 * specify the brightness threshold by passing in a $limit value. Higher
	 * values are brighter.
	 *
	 * Based on: http://stackoverflow.com/a/8468448/1723499
	 *
	 * @since 0.1
	 */
	function isColourDark( $colour, $limit = 130 ) {
		$colour = str_replace( '#', '', $colour );
		$r = hexdec( substr( $colour, 0, 2 ) );
		$g = hexdec( substr( $colour, 2, 2 ) );
		$b = hexdec( substr( $colour, 4, 2 ) );
		$contrast = sqrt(
			$r * $r * .241 +
			$g * $g * .691 +
			$b * $b * .068
		);
		return $contrast < $limit;
	}
}

?>
