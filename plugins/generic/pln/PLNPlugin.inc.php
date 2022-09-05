<?php

/**
 * @file PLNPlugin.inc.php
 *
 * Copyright (c) 2013-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class PLNPlugin
 * @brief PLN plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');
import('lib.pkp.classes.config.Config');
import('classes.publication.Publication');
import('classes.issue.Issue');

define('PLN_PLUGIN_NAME', 'plnplugin');

// defined here in case an upgrade doesn't pick up the default value.
define('PLN_DEFAULT_NETWORK', 'http://pkp-pln.lib.sfu.ca');

define('PLN_DEFAULT_STATUS_SUFFIX', '/docs/status');

define('PLN_PLUGIN_HTTP_STATUS_OK', 200);
define('PLN_PLUGIN_HTTP_STATUS_CREATED', 201);

define('PLN_PLUGIN_XML_NAMESPACE', 'http://pkp.sfu.ca/SWORD');

// base IRI for the SWORD server. IRIs are constructed by appending to
// this constant.
define('PLN_PLUGIN_BASE_IRI', '/api/sword/2.0');
// used to retrieve the service document
define('PLN_PLUGIN_SD_IRI', PLN_PLUGIN_BASE_IRI . '/sd-iri');
// used to submit a deposit
define('PLN_PLUGIN_COL_IRI', PLN_PLUGIN_BASE_IRI . '/col-iri');
// used to edit and query the state of a deposit
define('PLN_PLUGIN_CONT_IRI', PLN_PLUGIN_BASE_IRI . '/cont-iri');

define('PLN_PLUGIN_ARCHIVE_FOLDER', 'pln');

// local statuses
define('PLN_PLUGIN_DEPOSIT_STATUS_NEW',					0x00);
define('PLN_PLUGIN_DEPOSIT_STATUS_PACKAGED',			0x01);
define('PLN_PLUGIN_DEPOSIT_STATUS_TRANSFERRED',			0x02);
define('PLN_PLUGIN_DEPOSIT_STATUS_PACKAGING_FAILED',	0x200);

// status on the processing server
define('PLN_PLUGIN_DEPOSIT_STATUS_RECEIVED',			0x04);
define('PLN_PLUGIN_DEPOSIT_STATUS_VALIDATED',			0x08); // was SYNCING
define('PLN_PLUGIN_DEPOSIT_STATUS_SENT',				0x10); // was SYNCED

// status in the LOCKSS PLN
define('PLN_PLUGIN_DEPOSIT_STATUS_LOCKSS_RECEIVED',		0x20); // was REMOTE_FAILURE
define('PLN_PLUGIN_DEPOSIT_STATUS_LOCKSS_SYNCING',		0x40); // was LOCAL_FAILURE
define('PLN_PLUGIN_DEPOSIT_STATUS_LOCKSS_AGREEMENT',	0x80); // was UPDATE

define('PLN_PLUGIN_DEPOSIT_STATUS_UPDATE',				0x100);

define('PLN_PLUGIN_DEPOSIT_OBJECT_SUBMISSION', 'Submission');
define('PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE', 'Issue');

define('PLN_PLUGIN_NOTIFICATION_TYPE_PLUGIN_BASE',	NOTIFICATION_TYPE_PLUGIN_BASE + 0x10000000);
define('PLN_PLUGIN_NOTIFICATION_TYPE_TERMS_UPDATED',	PLN_PLUGIN_NOTIFICATION_TYPE_PLUGIN_BASE + 0x0000001);
define('PLN_PLUGIN_NOTIFICATION_TYPE_ISSN_MISSING',	PLN_PLUGIN_NOTIFICATION_TYPE_PLUGIN_BASE + 0x0000002);
define('PLN_PLUGIN_NOTIFICATION_TYPE_HTTP_ERROR',	PLN_PLUGIN_NOTIFICATION_TYPE_PLUGIN_BASE + 0x0000003);
// define('PLN_PLUGIN_NOTIFICATION_TYPE_CURL_MISSING',	PLN_PLUGIN_NOTIFICATION_TYPE_PLUGIN_BASE + 0x0000004); DEPRECATED
define('PLN_PLUGIN_NOTIFICATION_TYPE_ZIP_MISSING',	PLN_PLUGIN_NOTIFICATION_TYPE_PLUGIN_BASE + 0x0000005);
define('PLN_PLUGIN_NOTIFICATION_TYPE_TAR_MISSING',	PLN_PLUGIN_NOTIFICATION_TYPE_PLUGIN_BASE + 0x0000006);

class PLNPlugin extends GenericPlugin {
	/**
	 * @copydoc LazyLoadPlugin::register()
	 */
	public function register($category, $path, $mainContextId = null) {
		if (!parent::register($category, $path, $mainContextId)) return false;
		if ($this->getEnabled()) {
			$this->registerDAOs();
			$this->import('classes.Deposit');
			$this->import('classes.DepositObject');
			$this->import('classes.DepositPackage');

			HookRegistry::register('PluginRegistry::loadCategory', array($this, 'callbackLoadCategory'));
			HookRegistry::register('JournalDAO::deleteJournalById', array($this, 'callbackDeleteJournalById'));
			HookRegistry::register('LoadHandler', array($this, 'callbackLoadHandler'));
			HookRegistry::register('NotificationManager::getNotificationContents', array($this, 'callbackNotificationContents'));
			HookRegistry::register('LoadComponentHandler', array($this, 'setupComponentHandlers'));
		}

		HookRegistry::register('AcronPlugin::parseCronTab', array($this, 'callbackParseCronTab'));
		return true;
	}

	/**
	 * Permit requests to the static pages grid handler
	 * @param $hookName string The name of the hook being invoked
	 * @param $args array The parameters to the invoked hook
	 */
	public function setupComponentHandlers($hookName, $params) {
		$component = $params[0];
		switch ($component) {
			case 'plugins.generic.pln.controllers.grid.PLNStatusGridHandler':
				// Allow the PLN status grid handler to get the plugin object
				import($component);
				$componentPieces = explode('.', $component);
				$className = array_pop($componentPieces);
				$className::setPlugin($this);
				return true;
		}
		return false;
	}

	/**
	 * @copydoc Plugin::getActions()
	 */
	public function getActions($request, $verb) {
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
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
				new LinkAction(
					'status',
					new AjaxModal(
						$router->url($request, null, null, 'manage', null, array('verb' => 'status', 'plugin' => $this->getName(), 'category' => 'generic')),
						$this->getDisplayName()
					),
					__('common.status'),
					null
				)
			):array(),
			parent::getActions($request, $verb)
		);
	}

	/**
	 * Register this plugin's DAOs with the application
	 */
	public function registerDAOs() {

		$this->import('classes.DepositDAO');
		$this->import('classes.DepositObjectDAO');

		$depositDao = new DepositDAO();
		DAORegistry::registerDAO('DepositDAO', $depositDao);

		$depositObjectDao = new DepositObjectDAO();
		DAORegistry::registerDAO('DepositObjectDAO', $depositObjectDao);
	}

	/**
	 * @copydoc PKPPlugin::getDisplayName()
	 */
	public function getDisplayName() {
		return __('plugins.generic.pln');
	}

	/**
	 * @copydoc PKPPlugin::getDescription()
	 */
	public function getDescription() {
		return __('plugins.generic.pln.description');
	}

	/**
	 * @copydoc Plugin::getInstallMigration()
	 */
	function getInstallMigration() {
		$this->import('PLNPluginSchemaMigration');
		return new PLNPluginSchemaMigration();
	}

	/**
	 * @copydoc PKPPlugin::getHandlerPath()
	 */
	public function getHandlerPath() {
		return $this->getPluginPath() . DIRECTORY_SEPARATOR . 'pages';
	}

	/**
	 * @copydoc PKPPlugin::getContextSpecificPluginSettingsFile()
	 */
	public function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'settings.xml';
	}

	/**
	 * @see PKPPlugin::getSetting()
	 * @param $journalId int
	 * @param $settingName string
	 */
	public function getSetting($journalId, $settingName) {
		// if there isn't a journal_uuid, make one
		switch ($settingName) {
			case 'journal_uuid':
				$uuid = parent::getSetting($journalId, $settingName);
				if (!is_null($uuid) && $uuid != '')
					return $uuid;
				$this->updateSetting($journalId, $settingName, $this->newUUID());
				break;
			case 'object_type':
				$type = parent::getSetting($journalId, $settingName);
				if( ! is_null($type))
					return $type;
				$this->updateSetting($journalId, $settingName, PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE);
				break;
			case 'pln_network':
				return Config::getVar('lockss', 'pln_url', PLN_DEFAULT_NETWORK);
			case 'pln_status_docs':
				return Config::getVar('lockss', 'pln_status_docs', Config::getVar('lockss', 'pln_url', PLN_DEFAULT_NETWORK) . PLN_DEFAULT_STATUS_SUFFIX);
		}
		return parent::getSetting($journalId, $settingName);
	}

	/**
	 * Register as a gateway plugin.
	 * @param $hookName string
	 * @param $args array
	 */
	public function callbackLoadCategory($hookName, $args) {
		$category =& $args[0];
		$plugins =& $args[1];
		switch ($category) {
			case 'gateways':
				$this->import('PLNGatewayPlugin');
				$gatewayPlugin = new PLNGatewayPlugin($this->getName());
				$plugins[$gatewayPlugin->getSeq()][$gatewayPlugin->getPluginPath()] =& $gatewayPlugin;
				break;
		}

		return false;
	}

	/**
	 * Delete all plug-in data for a journal when the journal is deleted
	 * @param $hookName string (JournalDAO::deleteJournalById)
	 * @param $args array (JournalDAO, journalId)
	 * @return boolean false to continue processing subsequent hooks
	 */
	public function callbackDeleteJournalById($hookName, $params) {
		$journalId = $params[1];
		$depositDao = DAORegistry::getDAO('DepositDAO');
		$depositDao->deleteByJournalId($journalId);
		$depositObjectDao = DAORegistry::getDAO('DepositObjectDAO');
		$depositObjectDao->deleteByJournalId($journalId);
		return false;
	}

	/**
	 * @copydoc AcronPlugin::parseCronTab()
	 */
	public function callbackParseCronTab($hookName, $args) {
		$taskFilesPath =& $args[0];
		$taskFilesPath[] = $this->getPluginPath() . '/xml/scheduledTasks.xml';
		return false;
	}

	/**
	 * Hook registry function to provide notification messages
	 * @param $hookName string (NotificationManager::getNotificationContents)
	 * @param $args array ($notification, $message)
	 * @return boolean false to continue processing subsequent hooks
	 */
	public function callbackNotificationContents($hookName, $args) {
		$notification = $args[0];
		$message = $args[1];

		$type = $notification->getType();
		assert(isset($type));
		switch ($type) {
			case PLN_PLUGIN_NOTIFICATION_TYPE_TERMS_UPDATED:
				$message = __('plugins.generic.pln.notifications.terms_updated');
				break;
			case PLN_PLUGIN_NOTIFICATION_TYPE_ISSN_MISSING:
				$message = __('plugins.generic.pln.notifications.issn_missing');
				break;
			case PLN_PLUGIN_NOTIFICATION_TYPE_HTTP_ERROR:
				$message = __('plugins.generic.pln.notifications.http_error');
				break;
		}
	}

	/**
	 * Callback for the LoadHandler hook
	 */
	public function callbackLoadHandler($hookName, $args) {
		$page =& $args[0];
		if ($page == 'pln') {
			$op = $args[1];
			if ($op) {
				if (in_array($op, array('deposits'))) {
					define('HANDLER_CLASS', 'PLNHandler');
					AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON);
					$handlerFile =& $args[2];
					$handlerFile = $this->getHandlerPath() . '/' . 'PLNHandler.inc.php';
				}
			}
		}
	}

	/**
	 * @copydoc PKPPlugin::manage()
	 */
	public function manage($args, $request) {
		$journal = $request->getJournal();

		switch($request->getUserVar('verb')) {
			case 'settings':
				$context = $request->getContext();
				AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON,  LOCALE_COMPONENT_PKP_MANAGER);
				$this->import('classes.form.PLNSettingsForm');
				$form = new PLNSettingsForm($this, $context->getId());

				if ($request->getUserVar('refresh')) {
					$this->getServiceDocument($context->getId(), $request);
				} else {
					if ($request->getUserVar('save')) {

						$form->readInputData();
						if ($form->validate()) {
							$form->execute();

							// Add notification: Changes saved
							$notificationContent = __('plugins.generic.pln.settings.saved');
							$currentUser = $request->getUser();
							$notificationMgr = new NotificationManager();
							$notificationMgr->createTrivialNotification($currentUser->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => $notificationContent));

							return new JSONMessage(true);
						}
					}
				}

				$form->initData();

				return new JSONMessage(true, $form->fetch($request));
			case 'status':
				$depositDao = DAORegistry::getDAO('DepositDAO');

				$context = $request->getContext();
				AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON,  LOCALE_COMPONENT_PKP_MANAGER);
				$this->import('classes.form.PLNStatusForm');
				$form = new PLNStatusForm($this, $context->getId());

				if ($request->getUserVar('reset')) {
					$deposit_ids = array_keys($request->getUserVar('reset'));
					$depositDao = DAORegistry::getDAO('DepositDAO');
					foreach ($deposit_ids as $deposit_id) {
						$deposit = $depositDao->getById($deposit_id); /** @var $deposit Deposit */

						$deposit->reset();
						
						$depositDao->updateObject($deposit);
					}
				}

				return new JSONMessage(true, $form->fetch($request));
			case 'enable':
				if(!$this->zipInstalled()) {
					$message = NOTIFICATION_TYPE_ERROR;
					$messageParams = array('contents' => __('plugins.generic.pln.notifications.zip_missing'));
					break;
				}
				if(!$this->tarInstalled()) {
					$message = NOTIFICATION_TYPE_ERROR;
					$messageParams = array('contents' => __('plugins.generic.pln.notifications.tar_missing'));
					break;
				}
				$message = NOTIFICATION_TYPE_SUCCESS;
				$messageParams = array('contents' => __('plugins.generic.pln.enabled'));
				$this->updateSetting($journal->getId(), 'enabled', true);
				break;
			case 'disable':
				$message = NOTIFICATION_TYPE_SUCCESS;
				$messageParams = array('contents' => __('plugins.generic.pln.disabled'));
				$this->updateSetting($journal->getId(), 'enabled', false);
				break;
			default:
				return parent::manage($verb, $args, $message, $messageParams);
		}

	}

	/**
	 * @copydoc GenericPlugin::getManagementVerbs()
	 */
	public function getManagementVerbs() {
		$verbs = parent::getManagementVerbs();
		if ($this->getEnabled()) {
			$verbs[] = array('settings', __('plugins.generic.pln.settings'));
			$verbs[] = array('status', __('plugins.generic.pln.status'));
		}
		return $verbs;
	}

	/**
	 * Check to see whether the PLN's terms have been agreed to
	 * to append.
	 * @param $journalId int
	 * @return boolean
	 */
	public function termsAgreed($journalId) {

		$terms = unserialize($this->getSetting($journalId, 'terms_of_use'));
		$termsAgreed = unserialize($this->getSetting($journalId, 'terms_of_use_agreement'));

		foreach (array_keys($terms) as $term) {
			if (!isset($termsAgreed[$term]) || (!$termsAgreed[$term]))
				return false;
		}

		return true;
	}

	/**
	 * Request service document at specified URL
	 * @param $contextId int The journal id for the service document we wish to fetch
	 * @return int The HTTP response status or FALSE for a network error.
	 */
	public function getServiceDocument($contextId) {
		$application = Application::getApplication();
		$request = $application->getRequest();
		$contextDao = Application::getContextDAO();
		$context = $contextDao->getById($contextId);

		// get the journal and determine the language.
		$locale = $context->getPrimaryLocale();
		$language = strtolower(str_replace('_', '-', $locale));
		$network = $this->getSetting($context->getId(), 'pln_network');
		$application = Application::getApplication();
		$dispatcher = $application->getDispatcher();

		// retrieve the service document
		$result = $this->curlGet(
			$network . PLN_PLUGIN_SD_IRI,
			[
				'On-Behalf-Of' => $this->getSetting($contextId, 'journal_uuid'),
				'Journal-URL' => $dispatcher->url($request, ROUTE_PAGE, $context->getPath()),
				'Accept-language' => $language,
			]
		);

		// stop here if we didn't get an OK
		if ($result['status'] != PLN_PLUGIN_HTTP_STATUS_OK) {
			if($result['status'] === FALSE) {
				error_log(__('plugins.generic.pln.error.network.servicedocument', array('error' => $result['error'])));
			} else {
				error_log(__('plugins.generic.pln.error.http.servicedocument', array('error' => $result['status'])));
			}
			return $result['status'];
		}

		$serviceDocument = new DOMDocument();
		$serviceDocument->preserveWhiteSpace = false;
		$serviceDocument->loadXML($result['result']);

		// update the max upload size
		$element = $serviceDocument->getElementsByTagName('maxUploadSize')->item(0);
		$this->updateSetting($contextId, 'max_upload_size', $element->nodeValue);

		// update the checksum type
		$element = $serviceDocument->getElementsByTagName('uploadChecksumType')->item(0);
		$this->updateSetting($contextId, 'checksum_type', $element->nodeValue);

		// update the network status
		$element = $serviceDocument->getElementsByTagName('pln_accepting')->item(0);
		$this->updateSetting($contextId, 'pln_accepting', (($element->getAttribute('is_accepting') == 'Yes') ? true : false));
		$this->updateSetting($contextId, 'pln_accepting_message', $element->nodeValue);

		// update the terms of use
		$termElements = $serviceDocument->getElementsByTagName('terms_of_use')->item(0)->childNodes;
		$terms = array();
		foreach($termElements as $termElement) {
			$terms[$termElement->tagName] = array('updated' => $termElement->getAttribute('updated'), 'term' => $termElement->nodeValue);
		}

		$newTerms = serialize($terms);
		$oldTerms = $this->getSetting($contextId,'terms_of_use');

		// if the new terms don't match the exiting ones we need to reset agreement
		if ($newTerms != $oldTerms) {
			$termAgreements = array();
			foreach($terms as $termName => $termText) {
				$termAgreements[$termName] = null;
			}

			$this->updateSetting($contextId, 'terms_of_use', $newTerms, 'object');
			$this->updateSetting($contextId, 'terms_of_use_agreement', serialize($termAgreements), 'object');
			$this->createJournalManagerNotification($contextId, PLN_PLUGIN_NOTIFICATION_TYPE_TERMS_UPDATED);
		}

		return $result['status'];
	}

	/**
	 * Create notification for all journal managers
	 * @param $contextId int
	 * @param $notificationType int
	 */
	public function createJournalManagerNotification($contextId, $notificationType) {
		$roleDao = DAORegistry::getDAO('RoleDAO');
		$journalManagers = $roleDao->getUsersByRoleId(ROLE_ID_MANAGER, $contextId);
		import('classes.notification.NotificationManager');
		$notificationManager = new NotificationManager();
		// TODO: this currently gets sent to all journal managers - perhaps only limit to the technical contact's account?
		while ($journalManager = $journalManagers->next()) {
			$notificationManager->createTrivialNotification($journalManager->getId(), $notificationType);
			unset($journalManager);
		}
	}

	/**
	 * Get whether zip archive support is present
	 * @return boolean
	 */
	public function zipInstalled() {
		return class_exists('ZipArchive');
	}

	/**
	 * Check if the Archive_Tar extension is installed and available. BagIt
	 * requires it, and will not function without it.
	 *
	 * @return boolean
	 */
	public function tarInstalled() {
		@include_once('Archive/Tar.php');
		return class_exists('Archive_Tar');
	}

	/**
	 * Check if acron is enabled, or if the scheduled_tasks config var is set.
	 * The plugin needs to run periodically through one of those systems.
	 *
	 * @return boolean
	 */
	public function cronEnabled() {
		$application = PKPApplication::getApplication();
		$products = $application->getEnabledProducts('plugins.generic');
		return isset($products['acron']) || Config::getVar('general', 'scheduled_tasks', false);
	}

	/**
	 * Get resource
	 * @param $url string
	 * @param $headers array
	 * @return array
	 */
	public function curlGet($url, $headers=[]) {
		$httpClient = Application::get()->getHttpClient();
		try {
			$response = $httpClient->request('GET', $url, [
				'headers' => $headers,
			]);
		} catch (GuzzleHttp\Exception\RequestException $e) {
			return ['error' => $e->getMessage(), 'status' => null];
		}
		return array(
			'status' => $response->getStatusCode(),
			'result' => (string) $response->getBody(),
		);
	}

	/**
	 * Post a file to a resource
	 * @param $url string
	 * @param $headers array
	 * @return array
	 */
	public function curlPostFile($url, $filename) {
		return $this->_sendFile('POST', $url, $filename);
	}

	/**
	 * Put a file to a resource
	 * @param $url string
	 * @param $filename string
	 * @return array
	 */
	public function curlPutFile($url, $filename) {
		return $this->_sendFile('PUT', $url, $filename);
	}

	/**
	 * Create a new UUID
	 * @return string
	 */
	public function newUUID() {
		return PKPString::generateUUID();
	}

	/**
	 * Transfer a file to a resource.
	 * @param $method string PUT or POST
	 * @param $url string
	 * @param $headers array
	 * @return array
	 */
	protected function _sendFile($method, $url, $filename) {
		$httpClient = Application::get()->getHttpClient();
		try {
			$response = $httpClient->request($method, $url, [
				'headers' => [
					'Content-Type' => mime_content_type($filename),
					'Content-Length' => filesize($filename),
				],
				'body' => fopen($filename, 'r'),
			]);
		} catch (GuzzleHttp\Exception\RequestException $e) {
			return ['error' => $e->getMessage()];
		}
		return array(
			'status' => $response->getStatusCode(),
			'result' => (string) $response->getBody(),
		);
	}
}
