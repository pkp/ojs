<?php

/**
 * @file plugins/generic/externalFeed/ExternalFeedPlugin.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ExternalFeedPlugin
 * @ingroup plugins_generic_externalFeed
 *
 * @brief ExternalFeed plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class ExternalFeedPlugin extends GenericPlugin {

	const CUSTOM_STYLESHEET = 'externalFeedStyleSheet.css';

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	public function register($category, $path) {
		$success = parent::register($category, $path);

		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;
		if ($success && $this->getEnabled()) {
			$this->import('classes.ExternalFeedDAO');
			
			HookRegistry::register('PluginRegistry::loadCategory', array($this, 'callbackLoadCategory'));
			
			$externalFeedDao = new ExternalFeedDAO($this->getName());
			$returner =& DAORegistry::registerDAO('ExternalFeedDAO', $externalFeedDao);
			
			HookRegistry::register('Templates::Management::Settings::website', array($this, 'callbackShowWebsiteSettingsTabs'));
			HookRegistry::register('LoadComponentHandler', array($this, 'setupGridHandler'));
			
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->addStyleSheet('externalFeed', '/' . $this->getStyleSheetFile());

			// Journal home page display
			HookRegistry::register('TemplateManager::display', array($this, 'displayHomepage'));

			$this->_registerTemplateResource();
		}
		return $success;
	}

	/**
	 * Get the display name of this plugin
	 * 
	 * @return string
	 */
	public function getDisplayName() {
		return __('plugins.generic.externalFeed.displayName');
	}

	public function getDescription() {
		return __('plugins.generic.externalFeed.description');
	}

	/**
	 * Get the filename of the ADODB schema for this plugin.
	 */
	public function getInstallSchemaFile() {
		return $this->getPluginPath() . '/' . 'schema.xml';
	}

	/**
	 * Get plugin CSS URL
	 *
	 * @return string Public plugin CSS URL
	 */
	public function getCssUrl() {
		return $this->getPluginPath() . '/css/';
	}

	/**
	 * Get the filename of the default CSS stylesheet for this plugin.
	 * @return string 
	 */
	public function getDefaultStyleSheetFile() {
		return $this->getCssUrl() . 'externalFeed.css';
	}

	/**
	 * Override the builtin to get the correct template path.
	 * @return string
	 */
	public function getTemplatePath($inCore = false)
	{
		return $this->getTemplateResourceName() . ':templates/';
	}

	/**
	 * @see Plugin::getActions()
	 */
	function getActions($request, $verb) {
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		import('lib.pkp.classes.linkAction.request.RedirectAction');

		$router = $request->getRouter();
		$dispatcher = $request->getDispatcher();

		return array_merge(
				$this->getEnabled()?array(
						new LinkAction(
								'settings',
								new AjaxModal(
										$router->url($request, null, null, 'manage', null, array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic')),
										$this->getDisplayName()
								),
								__('manager.plugins.settings'),
								null
						),
				):array(),
				$this->getEnabled()?array(
						new LinkAction(
								'feeds',
								new RedirectAction($dispatcher->url(
									$request, ROUTE_PAGE,
									null, 'management', 'settings', 'website',
									array('uid' => uniqid()), // Force reload
									'externalFeeds'
								)),
								__('plugins.generic.externalFeed.manager.feeds'),
								null
						),
				):array(),
				parent::getActions($request, $verb)
		);
	}

	/**
	 * @see Plugin::manage()
	 */
	function manage($args, $request) {

		$journal = $request->getJournal();

		switch ($request->getUserVar('verb')) {
			case 'settings':
				$this->import('ExternalFeedSettingsForm');
				$context = $request->getContext();
				AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON,  LOCALE_COMPONENT_PKP_MANAGER);
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->register_function('plugin_url', array($this, 'smartyPluginUrl'));
				$form = new ExternalFeedSettingsForm($this, $context->getId());
				if ($request->getUserVar('save')) {

					if ($request->getUserVar('deleteStyleSheet')) {
						return $form->deleteStyleSheet();
					}
					else {
						if ($temporaryFileId = $request->getUserVar('temporaryFileId')) {
							$user = $request->getUser();
							$temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
							$temporaryFile = $temporaryFileDao->getTemporaryFile($temporaryFileId, $user->getId());
							
							import('classes.file.PublicFileManager');
							$journal = $request->getJournal();
							$publicFileManager = new PublicFileManager();
							$publicFileManager->copyJournalFile($journal->getId(), $temporaryFile->getFilePath(), ExternalFeedPlugin::CUSTOM_STYLESHEET);
							
							$value = array(
								'name' => $temporaryFile->getOriginalFileName(),
								'uploadName' => ExternalFeedPlugin::CUSTOM_STYLESHEET,
								'dateUploaded' => Core::getCurrentDate()
							);
							$this->updateSetting($journal->getId(), 'externalFeedStyleSheet', $value, 'object');
							
							return new JSONMessage(true);
						}
						else {
							return new JSONMessage(false);
						}
					}
					
				}
				else {
					$form->initData();
				}
				return new JSONMessage(true, $form->fetch($request));

			case 'uploadStyleSheet':
				$user = $request->getUser();

				import('lib.pkp.classes.file.TemporaryFileManager');
				$temporaryFileManager = new TemporaryFileManager();
				$temporaryFile = $temporaryFileManager->handleUpload('uploadedFile', $user->getId());
				$valid_types = array('text/plain','text/css');
				if ($temporaryFile && in_array($temporaryFile->getFileType(), $valid_types)) {
					$json = new JSONMessage(true);
					$json->setAdditionalAttributes(array(
							'temporaryFileId' => $temporaryFile->getId()
					));
					return $json;
				} else {
					return new JSONMessage(false, __('common.uploadFailed'));
				}
		}
	}

	/**
	 * Register as a block plugin
	 * @param $hookName string
	 * @param $args array
	 */
	function callbackLoadCategory($hookName, $args) {
		$category =& $args[0];
		$plugins =& $args[1];
		switch ($category) {
			case 'blocks':
				$this->import('ExternalFeedBlockPlugin');
				$blockPlugin = new ExternalFeedBlockPlugin($this);
				$plugins[$blockPlugin->getSeq()][$blockPlugin->getPluginPath()] = $blockPlugin;
				break;
		}
		return false;
	}

	/**
	 * Extend the website settings tabs to include external feeds crud
	 * @param $hookName string The name of the invoked hook
	 * @param $args array Hook parameters
	 * @return boolean Hook handling status
	 */
	public function callbackShowWebsiteSettingsTabs($hookName, $args) {
		$output =& $args[2];
		$request =& Registry::get('request');
		$dispatcher = $request->getDispatcher();
		$output .= '<li><a name="externalFeeds" href="' . $dispatcher->url($request, ROUTE_COMPONENT, null, 'plugins.generic.externalFeed.controllers.grid.ExternalFeedGridHandler', 'index') . '">' . __('plugins.generic.externalFeed.displayName') . '</a></li>';
		return false;
	}

	/**
	 * Permit requests to the external feeds grid handler
	 * @param $hookName string The name of the hook being invoked
	 * @param $args array The parameters to the invoked hook
	 */
	function setupGridHandler($hookName, $params) {
		$component =& $params[0];
		if ($component == 'plugins.generic.externalFeed.controllers.grid.ExternalFeedGridHandler') {
			// Allow the static page grid handler to get the plugin object
			import($component);
			ExternalFeedGridHandler::setPlugin($this);
			return true;
		}
		return false;
	}

	/**
	 * Get the filename of the CSS stylesheet for this plugin.
	 */
	public function getStyleSheetFile() {
		$request = $this->getRequest();
		$journal = $request->getJournal();
		$journalId = $journal->getId();
		
		$styleSheet = $this->getSetting($journalId, 'externalFeedStyleSheet');

		if (empty($styleSheet)) {
			return $this->getDefaultStyleSheetFile();
		} else {
			import('classes.file.PublicFileManager');
			$fileManager = new PublicFileManager();
			return $fileManager->getJournalFilesPath($journalId) . '/' . $styleSheet['uploadName'];
		}
	}

	/**
	 * Display external feed content on journal homepage.
	 * @param $hookName string
	 * @param $args array
	 */
	public function displayHomepage($hookName, $args) {
		$request = $this->getRequest();
		$journal = $request->getJournal();
		$journalId = $journal->getId();

		if ($this->getEnabled()) {
			if (!is_a($request->getRouter(), 'PKPPageRouter')) return false;
			$requestedPage = $request->getRequestedPage();

			$entries = array();
			if (empty($requestedPage) || $requestedPage == 'index') {
				$externalFeedDao =& DAORegistry::getDAO('ExternalFeedDAO');

				$feeds =& $externalFeedDao->getExternalFeedsByJournalId($journal->getId());
				while ($currentFeed =& $feeds->next()) {
					if (!$currentFeed->getDisplayHomepage()) continue;
					$items = null;
					$feedTitle = null;

					$feed = new SimplePie();
					$feed->set_feed_url($currentFeed->getUrl());
					$feed->enable_order_by_date(false);
					$feed->set_cache_location(CacheManager::getFileCachePath());
					$feed->init();

					if ($currentFeed->getLimitItems()) {
						$recentItems = $currentFeed->getRecentItems();
					} else {
						$recentItems = 0;
					}

					$entries[] = array(
						'feedTitle' => $currentFeed->getLocalizedTitle(),
						'items' => $feed->get_items(0, $recentItems),
					);
				}

				$templateManager =& $args[0];
				$templateManager->assign('entries', $entries);
				$templateManager->assign('entry_date_format', Config::getVar('general', 'date_format_short'));
				$output = $templateManager->fetch($this->getTemplatePath(). 'homepage.tpl');
				$additionalHomeContent = $templateManager->get_template_vars('additionalHomeContent');
				$templateManager->assign('additionalHomeContent', $additionalHomeContent . "\n\n" . $output);

			}
		}

	}
}
