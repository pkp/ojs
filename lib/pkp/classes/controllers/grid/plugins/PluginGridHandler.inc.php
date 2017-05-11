<?php

/**
 * @file controllers/grid/plugins/PluginGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PluginGridHandler
 * @ingroup controllers_grid_plugins
 *
 * @brief Handle plugins grid requests.
 */

import('lib.pkp.classes.controllers.grid.CategoryGridHandler');
import('lib.pkp.controllers.grid.plugins.form.UploadPluginForm');
import('lib.pkp.controllers.grid.plugins.PluginGalleryGridHandler');

abstract class PluginGridHandler extends CategoryGridHandler {
	/**
	 * Constructor
	 * @param $roles array
	 */
	function __construct($roles) {
		$this->addRoleAssignment($roles,
			array('enable', 'disable', 'manage', 'fetchGrid, fetchCategory', 'fetchRow'));

		$this->addRoleAssignment(ROLE_ID_SITE_ADMIN,
			array('uploadPlugin', 'upgradePlugin', 'deletePlugin', 'saveUploadPlugin'));

		parent::__construct();
	}


	//
	// Overridden template methods
	//
	/**
	 * @copydoc GridHandler::initialize()
	 */
	function initialize($request) {
		parent::initialize($request);

		// Load language components
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_PKP_COMMON, LOCALE_COMPONENT_APP_MANAGER);

		// Basic grid configuration
		$this->setTitle('common.plugins');

		// Set the no items row text
		$this->setEmptyRowText('grid.noItems');

		// Columns
		import('lib.pkp.controllers.grid.plugins.PluginGridCellProvider');
		$pluginCellProvider = new PluginGridCellProvider();
		$this->addColumn(
			new GridColumn('name',
				'common.name',
				null,
				null,
				$pluginCellProvider,
				array(
					'showTotalItemsNumber' => true,
					'collapseAllColumnsInCategories' => true
				)
			)
		);

		$descriptionColumn = new GridColumn(
				'description',
				'common.description',
				null,
				null,
				$pluginCellProvider
		);
		$descriptionColumn->addFlag('html', true);
		$this->addColumn($descriptionColumn);

		$this->addColumn(
			new GridColumn('enabled',
				'common.enabled',
				null,
				'controllers/grid/common/cell/selectStatusCell.tpl',
				$pluginCellProvider
			)
		);

		$router = $request->getRouter();

		// Grid level actions.
		$userRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);
		if (in_array(ROLE_ID_SITE_ADMIN, $userRoles)) {
			import('lib.pkp.classes.linkAction.request.AjaxModal');

			// Install plugin.
			$this->addAction(
				new LinkAction(
					'upload',
					new AjaxModal(
						$router->url($request, null, null, 'uploadPlugin'),
						__('manager.plugins.upload'), 'modal_add_file'),
					__('manager.plugins.upload'),
					'add'));
		}
	}

	/**
	 * @copydoc GridHandler::getFilterForm()
	 */
	protected function getFilterForm() {
		return 'controllers/grid/plugins/pluginGridFilter.tpl';
	}

	/**
	 * @copydoc GridHandler::getFilterSelectionData()
	 */
	function getFilterSelectionData($request) {
		$category = $request->getUserVar('category');
		$pluginName = $request->getUserVar('pluginName');

		if (is_null($category)) {
			$category = PLUGIN_GALLERY_ALL_CATEGORY_SEARCH_VALUE;
		}

		return array('category' => $category, 'pluginName' => $pluginName);
	}

	/**
	 * @copydoc GridHandler::renderFilter()
	 */
	function renderFilter($request) {
		$categoriesSymbolic = $this->loadData($request, null);
		$categories = array(PLUGIN_GALLERY_ALL_CATEGORY_SEARCH_VALUE => __('grid.plugin.allCategories'));
		foreach ($categoriesSymbolic as $category) {
			$categories[$category] = __("plugins.categories.$category");
		}
		$filterData = array('categories' => $categories);

		return parent::renderFilter($request, $filterData);
	}

	/**
	 * @copydoc CategoryGridHandler::getCategoryRowInstance()
	 */
	protected function getCategoryRowInstance() {
		import('lib.pkp.controllers.grid.plugins.PluginCategoryGridRow');
		return new PluginCategoryGridRow();
	}

	/**
	 * @copydoc CategoryGridHandler::loadCategoryData()
	 */
	function loadCategoryData($request, $categoryDataElement, $filter) {
		$plugins =& PluginRegistry::loadCategory($categoryDataElement);

		$versionDao = DAORegistry::getDAO('VersionDAO');
		import('lib.pkp.classes.site.VersionCheck');
		$fileManager = new FileManager();

		$notHiddenPlugins = array();
		foreach ((array) $plugins as $plugin) {
			if (!$plugin->getHideManagement()) {
				$notHiddenPlugins[$plugin->getName()] = $plugin;
			}
			$version = $plugin->getCurrentVersion();
			if ($version == null) { // this plugin is on the file system, but not installed.
				$versionFile = $plugin->getPluginPath() . '/version.xml';
				if ($fileManager->fileExists($versionFile)) {
					$versionInfo = VersionCheck::parseVersionXML($versionFile);
					$pluginVersion = $versionInfo['version'];
				} else {
					$pluginVersion = new Version(
						1, 0, 0, 0, // Major, minor, revision, build
						Core::getCurrentDate(), // Date installed
						1,	// Current
						'plugins.'.$plugin->getCategory(), // Type
						basename($plugin->getPluginPath()), // Product
						'',	// Class name
						0,	// Lazy load
						$plugin->isSitePlugin()	// Site wide
					);
				}
				$versionDao->insertVersion($pluginVersion, true);
			}
		}

		if (!is_null($filter) && isset($filter['pluginName']) && $filter['pluginName'] != "") {
			// Find all plugins that have the filter name string in their display names.
			$filteredPlugins = array();
			foreach ($notHiddenPlugins as $plugin) { /* @var $plugin Plugin */
				$pluginName = $plugin->getDisplayName();
				if (stristr($pluginName, $filter['pluginName']) !== false) {
					$filteredPlugins[$plugin->getName()] = $plugin;
				}
			}
			return $filteredPlugins;
		}

		return $notHiddenPlugins;
	}

	/**
	 * @copydoc CategoryGridHandler::getCategoryRowIdParameterName()
	 */
	function getCategoryRowIdParameterName() {
		return 'category';
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	protected function loadData($request, $filter) {
		$categories = PluginRegistry::getCategories();
		if (is_array($filter) && isset($filter['category']) && array_search($filter['category'], $categories) !== false) {
			return array($filter['category'] => $filter['category']);
		} else {
			return array_combine($categories, $categories);
		}
	}


	//
	// Public handler methods.
	//
	/**
	 * Manage a plugin.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function manage($args, $request) {
		$plugin = $this->getAuthorizedContextObject(ASSOC_TYPE_PLUGIN); /* @var $plugin Plugin */
		return $plugin->manage($args, $request);
	}

	/**
	 * Enable a plugin.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function enable($args, $request) {
		$plugin = $this->getAuthorizedContextObject(ASSOC_TYPE_PLUGIN); /* @var $plugin Plugin */
		if ($plugin->getCanEnable()) {
			$plugin->setEnabled(true);
			$user = $request->getUser();
			$notificationManager = new NotificationManager();
			$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_PLUGIN_ENABLED, array('pluginName' => $plugin->getDisplayName()));
		}
		return DAO::getDataChangedEvent($request->getUserVar('plugin'), $request->getUserVar($this->getCategoryRowIdParameterName()));
	}

	/**
	 * Disable a plugin.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function disable($args, $request) {
		$plugin = $this->getAuthorizedContextObject(ASSOC_TYPE_PLUGIN); /* @var $plugin Plugin */
		if ($request->checkCSRF() && $plugin->getCanDisable()) {
			$plugin->setEnabled(false);
			$user = $request->getUser();
			$notificationManager = new NotificationManager();
			$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_PLUGIN_DISABLED, array('pluginName' => $plugin->getDisplayName()));
		}
		return DAO::getDataChangedEvent($request->getUserVar('plugin'), $request->getUserVar($this->getCategoryRowIdParameterName()));
	}

	/**
	 * Show upload plugin form to upload a new plugin.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string
	 */
	function uploadPlugin($args, $request) {
		return $this->_showUploadPluginForm(PLUGIN_ACTION_UPLOAD, $request);
	}

	/**
	 * Show upload plugin form to update an existing plugin.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string
	 */
	function upgradePlugin($args, $request) {
		return $this->_showUploadPluginForm(PLUGIN_ACTION_UPGRADE, $request);
	}

	/**
	 * Upload a plugin file.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function uploadPluginFile($args, $request) {
		import('lib.pkp.classes.file.TemporaryFileManager');
		$temporaryFileManager = new TemporaryFileManager();
		$user = $request->getUser();

		// Return the temporary file id.
		if ($temporaryFile = $temporaryFileManager->handleUpload('uploadedFile', $user->getId())) {
			$json = new JSONMessage(true);
			$json->setAdditionalAttributes(array(
				'temporaryFileId' => $temporaryFile->getId()
			));
			return $json;
		} else {
			return new JSONMessage(false, __('manager.plugins.uploadError'));
		}
	}

	/**
	 * Save upload plugin file form.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function saveUploadPlugin($args, $request) {
		$function = $request->getUserVar('function');
		$uploadPluginForm = new UploadPluginForm($function);
		$uploadPluginForm->readInputData();

		if($uploadPluginForm->validate()) {
			if($uploadPluginForm->execute($request)) {
				return DAO::getDataChangedEvent();
			}
		}

		return new JSONMessage(false);
	}

	/**
	 * Delete plugin.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function deletePlugin($args, $request) {
		if (!$request->checkCSRF()) return new JSONMessage(false);

		$plugin = $this->getAuthorizedContextObject(ASSOC_TYPE_PLUGIN);
		$category = $plugin->getCategory();
		$productName = basename($plugin->getPluginPath());

		$versionDao = DAORegistry::getDAO('VersionDAO'); /* @var $versionDao VersionDAO */
		$installedPlugin = $versionDao->getCurrentVersion('plugins.'.$category, $productName, true);

		$notificationMgr = new NotificationManager();
		$user = $request->getUser();

		if ($installedPlugin) {
			$pluginDest = Core::getBaseDir() . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR . $productName;
			$pluginLibDest = Core::getBaseDir() . DIRECTORY_SEPARATOR . PKP_LIB_PATH . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR . $productName;

			// make sure plugin type is valid and then delete the files
			if (in_array($category, PluginRegistry::getCategories())) {
				// Delete the plugin from the file system.
				$fileManager = new FileManager();
				$fileManager->rmtree($pluginDest);
				$fileManager->rmtree($pluginLibDest);
			}

			if(is_dir($pluginDest) || is_dir($pluginLibDest)) {
				$notificationMgr->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_ERROR, array('contents' => __('manager.plugins.deleteError', array('pluginName' => $plugin->getDisplayName()))));
			} else {
				$versionDao->disableVersion('plugins.'.$category, $productName);
				$notificationMgr->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __('manager.plugins.deleteSuccess', array('pluginName' => $plugin->getDisplayName()))));
			}
		} else {
			$notificationMgr->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_ERROR, array('contents' => __('manager.plugins.doesNotExist', array('pluginName' => $plugin->getDisplayName()))));
		}

		return DAO::getDataChangedEvent($plugin->getName());
	}

	/**
	 * Fetch upload plugin form.
	 * @param $function string
	 * @param $request PKPRequest Request object
	 * @return JSONMessage JSON object
	 */
	function _showUploadPluginForm($function, $request) {
		import('lib.pkp.controllers.grid.plugins.form.UploadPluginForm');
		$uploadPluginForm = new UploadPluginForm($function);
		$uploadPluginForm->initData();

		return new JSONMessage(true, $uploadPluginForm->fetch($request));
	}
}

?>
