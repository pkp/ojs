<?php

/**
 * @file plugins/generic/pln/PLNPlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PLNPlugin
 * @ingroup plugins_generic_pln
 *
 * @brief PLN plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');
import('classes.article.PublishedArticle');
import('classes.issue.Issue');

define('PLN_PLUGIN_NAME','plnplugin');

define('PLN_PLUGIN_NETWORKS', serialize(array(
	'PKP' => 'pkp-pln.lib.sfu.ca'
)));

define('PLN_PLUGIN_HTTP_STATUS_OK', 200);
define('PLN_PLUGIN_HTTP_STATUS_CREATED', 201);

define('PLN_PLUGIN_XML_NAMESPACE','http://pkp.sfu.ca/SWORD');
// used to retrieve the service document
define('PLN_PLUGIN_SD_IRI','/api/sword/2.0/sd-iri');
// used to submit a deposit
define('PLN_PLUGIN_COL_IRI','/api/sword/2.0/col-iri');
// used to edit and query the state of a deposit
define('PLN_PLUGIN_CONT_IRI','/api/sword/2.0/cont-iri');

define('PLN_PLUGIN_ARCHIVE_FOLDER','pln');

define('PLN_PLUGIN_DEPOSIT_STATUS_NEW',				0x00);
define('PLN_PLUGIN_DEPOSIT_STATUS_PACKAGED',		0x01);
define('PLN_PLUGIN_DEPOSIT_STATUS_TRANSFERRED',		0x02);
define('PLN_PLUGIN_DEPOSIT_STATUS_RECEIVED',		0x04);
define('PLN_PLUGIN_DEPOSIT_STATUS_SYNCING',			0x08);
define('PLN_PLUGIN_DEPOSIT_STATUS_SYNCED',			0x10);
define('PLN_PLUGIN_DEPOSIT_STATUS_REMOTE_FAILURE',	0x20);
define('PLN_PLUGIN_DEPOSIT_STATUS_LOCAL_FAILURE',	0x40);
define('PLN_PLUGIN_DEPOSIT_STATUS_UPDATE',			0x80);

define('PLN_PLUGIN_DEPOSIT_OBJECT_ARTICLE', 'PublishedArticle');
define('PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE', 'Issue');

define('PLN_PLUGIN_NOTIFICATION_TYPE_PLUGIN_BASE',		NOTIFICATION_TYPE_PLUGIN_BASE + 0x10000000);
define('PLN_PLUGIN_NOTIFICATION_TYPE_TERMS_UPDATED',	PLN_PLUGIN_NOTIFICATION_TYPE_PLUGIN_BASE + 0x0000001);
define('PLN_PLUGIN_NOTIFICATION_TYPE_ISSN_MISSING',		PLN_PLUGIN_NOTIFICATION_TYPE_PLUGIN_BASE + 0x0000002);
define('PLN_PLUGIN_NOTIFICATION_TYPE_HTTP_ERROR',		PLN_PLUGIN_NOTIFICATION_TYPE_PLUGIN_BASE + 0x0000003);
define('PLN_PLUGIN_NOTIFICATION_TYPE_CURL_MISSING',		PLN_PLUGIN_NOTIFICATION_TYPE_PLUGIN_BASE + 0x0000004);
define('PLN_PLUGIN_NOTIFICATION_TYPE_ZIP_MISSING',		PLN_PLUGIN_NOTIFICATION_TYPE_PLUGIN_BASE + 0x0000005);

class PLNPlugin extends GenericPlugin {

	/**
	 * Constructor
	 */
	function PLNPlugin() {
		parent::GenericPlugin();
	}
	
	/**
	 * @copydoc LazyLoadPlugin::register()
	 */
	function register($category, $path) {
	
		if (!$this->php5Installed()) return false;
	
		$success = parent::register($category, $path);
		
		if ($success) {
			
			HookRegistry::register('TemplateManager::display',array($this, 'callbackTemplateDisplay'));
			HookRegistry::register('Templates::Manager::Setup::JournalArchiving', array($this, 'callbackJournalArchivingSetup'));
			HookRegistry::register('AcronPlugin::parseCronTab', array($this, 'callbackParseCronTab'));
		
			if ($this->getEnabled()) {
			
				$this->registerDAOs();
				$this->import('classes.Deposit');
				$this->import('classes.DepositObject');
				$this->import('classes.DepositPackage');
			
				HookRegistry::register('JournalDAO::deleteJournalById', array($this, 'callbackDeleteJournalById'));
				HookRegistry::register('LoadHandler', array($this, 'callbackLoadHandler'));
				HookRegistry::register('NotificationManager::getNotificationContents', array($this, 'callbackNotificationContents'));
			}
		}

		return $success;
	}
	
	/**
	 * Register this plugin's DAOs with the application
	 */	
	function registerDAOs() {
		
		$this->import('classes.DepositDAO');
		$this->import('classes.DepositObjectDAO');
		
		$depositDao = new DepositDAO($this->getName());
		DAORegistry::registerDAO('DepositDAO', $depositDao);
			
		$depositObjectDao = new DepositObjectDAO($this->getName());
		DAORegistry::registerDAO('DepositObjectDAO', $depositObjectDao);
		
	}
	
	/**
	 * @see PKPPlugin::getDisplayName()
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.pln');
	}
	
	/**
	 * @see PKPPlugin::getDescription()
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.pln.description');
	}
	
	/**
	 * @see PKPPlugin::getInstallSchemaFile()
	 * @return string
	 */
	function getInstallSchemaFile() {
		return $this->getPluginPath() . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'schema.xml';
	}
	
	/**
	 * @see PKPPlugin::getHandlerPath()
	 * @return string
	 */
	function getHandlerPath() {
		return $this->getPluginPath() . DIRECTORY_SEPARATOR . 'pages';
	}
	
	/**
	 * @see PKPPlugin::getTemplatePath()
	 * @return string
	 */
	function getTemplatePath() {
		return parent::getTemplatePath() . DIRECTORY_SEPARATOR . 'templates';
	}
	
	/**
	 * @see PKPPlugin::getContextSpecificPluginSettingsFile()
	 * @return string
	 */
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'settings.xml';
	}
	
	/**
	 * Return the location of the plugin's CSS file
	 * @return string
	 */
	function getStyleSheet() {
		return $this->getPluginPath() . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'pln.css';
	}
	
	/**
	 * @see PKPPlugin::getSetting()
	 * @param $journalId string
	 * @param $settingName string
	 */
	function getSetting($journalId,$settingName) {

		// if there isn't a journal_uuid, make one
		if ($settingName == 'journal_uuid') {
			$uuid = parent::getSetting($journalId, $settingName);
			if ($uuid) return $uuid;
			$this->updateSetting($journalId, $settingName, $this->newUUID());
		}
		
		return parent::getSetting($journalId,$settingName);
	}
	
	/**
	 * Delete all plug-in data for a journal when the journal is deleted
	 * @param $hookName string (JournalDAO::deleteJournalById)
	 * @param $args array (JournalDAO, journalId)
	 * @return boolean false to continue processing subsequent hooks
	 */
	function callbackDeleteJournalById($hookName, $params) {
		$journalId = $params[1];
		$depositDao =& DAORegistry::getDAO('DepositDAO');
		$depositDao->deleteByJournalId($journalId);
		$depositObjectDao =& DAORegistry::getDAO('DepositObjectDAO');
		$depositObjectDao->deleteByJournalId($journalId);
		return false;
	}
	
	/**
	 * @copydoc TemplateManager::display()
	 */
	function callbackTemplateDisplay($hookName, $params) {
		// Get request and context.
		$request =& PKPApplication::getRequest();
		$journal =& $request->getContext();
		
		// Assign our private stylesheet.
		$templateMgr =& $params[0];
		$templateMgr->addStylesheet($request->getBaseUrl() . '/' . $this->getStyleSheet());
		
		return false;
	}
	
	/**
	 * @copydoc AcronPlugin::parseCronTab()
	 */
	function callbackParseCronTab($hookName, $args) {
		$taskFilesPath =& $args[0];
		$taskFilesPath[] = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'scheduledTasks.xml';
		return false;
	}
	
	/**
	 * A callback used to populate journal setup step 2.6 with PLN preservation info
	 * @param $hookName string (Templates::Manager::Setup::JournalArchiving)
	 * @param $args array
	 * @return boolean false to continue processing subsequent hooks
	 */
	function callbackJournalArchivingSetup($hookName, $args) {
		$smarty =& $args[1];
		$output =& $args[2];
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
		$output .= $templateMgr->fetch($this->getTemplatePath() . DIRECTORY_SEPARATOR . 'setup.tpl');
		return false;
	}
	
	/**
	 * Hook registry function to provide notification messages
	 * @param $hookName string (NotificationManager::getNotificationContents)
	 * @param $args array ($notification, $message)
	 * @return boolean false to continue processing subsequent hooks
	 */
	function callbackNotificationContents($hookName, $args) {
		$notification =& $args[0];
		$message =& $args[1];

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
	 * @copydoc PKPPageRouter::route()
	 */
	function callbackLoadHandler($hookName, $args) {
		$page =& $args[0];
		if ($page == 'pln') {
			$op =& $args[1];
			if ($op) {
				if (in_array($op, array('deposits'))) {
					define('HANDLER_CLASS', 'PLNHandler');
					AppLocale::requireComponents(LOCALE_COMPONENT_APPLICATION_COMMON);
					$handlerFile =& $args[2];
					$handlerFile = $this->getHandlerPath() . DIRECTORY_SEPARATOR . 'PLNHandler.inc.php';
				}
			}
		}
	}
	
	/**
	 * @copydoc PKPPlugin::manage()
	 */
	function manage($verb, $args, &$message, &$messageParams) {

		$journal =& Request::getJournal();

		switch($verb) {
			case 'settings':
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
				$this->import('classes.form.PLNSettingsForm');
				$form = new PLNSettingsForm($this, $journal->getId());
				
				if (Request::getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						$message = NOTIFICATION_TYPE_SUCCESS;
						$messageParams = array('contents' => __('plugins.generic.pln.settings.saved'));
						return false;
					} else {
						$this->setBreadcrumbs('settings');
						$form->display();
					}
				} else {
					if (Request::getUserVar('refresh')) {
						$this->getServiceDocument($journal->getId());
					} 
					$this->setBreadcrumbs('settings');
					$form->initData();
					$form->display();
				}
				return true;
			case 'status':
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
				$this->import('classes.form.PLNStatusForm');
				$form = new PLNStatusForm($this, $journal->getId());
				
				if (Request::getUserVar('reset')) {
					$journal =& Request::getJournal();
					$deposit_ids = array_keys(Request::getUserVar('reset'));
					$depositDao =& DAORegistry::getDAO('DepositDAO');
					foreach ($deposit_ids as $deposit_id) {
						$deposit =& $depositDao->getDepositById($journal->getId(),$deposit_id);
						$deposit->setStatus(PLN_PLUGIN_DEPOSIT_STATUS_NEW);
						$depositDao->updateDeposit($deposit);
					}
				}
				
				$this->setBreadCrumbs('status');
				$form->display();
				return true;
			default:
				return parent::manage($verb, $args, $message, $messageParams);
;
		}

	}
	
	/**
	 * @copydoc GenericPlugin::getManagementVerbs()
	 */
	function getManagementVerbs() {
		$verbs = parent::getManagementVerbs();
		if ($this->getEnabled()) {
			$verbs[] = array('settings', __('plugins.generic.pln.settings'));
			$verbs[] = array('status', __('plugins.generic.pln.status'));
		}
		return $verbs;
	}
	
	/**
	 * Extend the {url ...} smarty to support this plugin.
	 */
	function smartyPluginUrl($params, &$smarty) {
		$path = array($this->getCategory(), $this->getName());
		if (is_array($params['path'])) {
			$params['path'] = array_merge($path, $params['path']);
		} elseif (!empty($params['path'])) {
			$params['path'] = array_merge($path, array($params['path']));
		} else {
			$params['path'] = $path;
		}

		if (!empty($params['id'])) {
			$params['path'] = array_merge($params['path'], array($params['id']));
			unset($params['id']);
		}
		return $smarty->smartyUrl($params, $smarty);
	}
	
	/**
	 * Set the page's breadcrumbs, given the plugin's tree of items
	 * to append.
	 * @param $page string
	 */
	function setBreadcrumbs($page) {
		
		$templateMgr =& TemplateManager::getManager();
		$pageCrumbs = array(
			array(
				Request::url(null, 'user'),
				'navigation.user'
			),
			array(
				Request::url(null, 'manager'),
				'manager.journalManagement'
			),
			array(
				Request::url(null, 'manager', 'plugins'),
				'manager.plugins.pluginManagement'
			),
			array(
				Request::url(null, 'manager', 'plugins', 'generic'),
				'plugins.categories.generic'
			)
		);

		$templateMgr->assign('pageHierarchy', $pageCrumbs);
	}
	
	/**
	 * Check to see whether the PLN's terms have been agreed to
	 * to append.
	 * @param $journalId int
	 * @return boolean
	 */
	function termsAgreed($journalId) {
		
		$terms = unserialize($this->getSetting($journalId, 'terms_of_use'));
		$termsAgreed = unserialize($this->getSetting($journalId, 'terms_of_use_agreement'));
		
		foreach (array_keys($terms) as $term) {
			if (!isset($termsAgreed[$term]) || ($termsAgreed[$term] == false)) return false;
		}
		
		return true;
	}
	
	/**
	 * Request service document at specified URL
	 * @param $journalId int The journal id for the service document we wish to fetch
	 * @return int The HTTP response status
	 */
	function getServiceDocument($journalId) {
			
		$plnNetworks = unserialize(PLN_PLUGIN_NETWORKS);
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journal =& $journalDao->getById($journalId);
		
		// retrieve the service document
		$result = $this->_curlGet(
			'http://' . $plnNetworks[$this->getSetting($journalId, 'pln_network')] . PLN_PLUGIN_SD_IRI,
			array(
				'On-Behalf-Of: '.$this->getSetting($journalId, 'journal_uuid'),
				'Journal-URL: '.$journal->getUrl()
			)
		);
		
		// stop here if we didn't get an OK
		if ($result['status'] != PLN_PLUGIN_HTTP_STATUS_OK) return $result['status'];

		$serviceDocument = new DOMDocument();
		$serviceDocument->preserveWhiteSpace = false;
		$serviceDocument->loadXML($result['result']);
		
		// update the max upload size
		$element = $serviceDocument->getElementsByTagName('maxUploadSize')->item(0);
		$this->updateSetting($journalId, 'max_upload_size', $element->nodeValue);
		
		// update the checksum type
		$element = $serviceDocument->getElementsByTagName('uploadChecksumType')->item(0);
		$this->updateSetting($journalId, 'checksum_type', $element->nodeValue);
		
		// update the network status
		$element = $serviceDocument->getElementsByTagName('pln_accepting')->item(0);
		$this->updateSetting($journalId, 'pln_accepting', (($element->getAttribute('is_accepting')=='Yes')?true:false));
		$this->updateSetting($journalId, 'pln_accepting_message', $element->nodeValue);
		
		// update the terms of use
		$termElements = $serviceDocument->getElementsByTagName('terms_of_use')->item(0)->childNodes;
		$terms = array();
		foreach($termElements as $termElement) {
			$terms[$termElement->tagName] = array('updated' => $termElement->getAttribute('updated'), 'term' => $termElement->nodeValue);
		}
		
		$newTerms = serialize($terms);
		$oldTerms = $this->getSetting($journalId,'terms_of_use');
		
		// if the new terms don't match the exiting ones we need to reset agreement
		if ($newTerms != $oldTerms) {
			$termAgreements = array();
			foreach($terms as $termName => $termText) {
				$termAgreements[$termName] = false;
			}
		
			$this->updateSetting($journalId, 'terms_of_use', $newTerms, 'object');
			$this->updateSetting($journalId, 'terms_of_use_agreement', serialize($termAgreements), 'object');
			$this->createJournalManagerNotification($journalId,PLN_PLUGIN_NOTIFICATION_TYPE_TERMS_UPDATED);
		}
		
		return $result['status'];
	}
	
	/**
	 * Create notification for all journal managers
	 * @param $journalId int
	 * @param $notificationType int
	 */
	function createJournalManagerNotification($journalId, $notificationType) {
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$journalManagers = $roleDao->getUsersByRoleId(ROLE_ID_JOURNAL_MANAGER,$journalId);
		import('classes.notification.NotificationManager');
		$notificationManager =& new NotificationManager();
		// TODO: this currently gets sent to all journal managers - perhaps only limit to the technical contact's account?
		while ($journalManager =& $journalManagers->next()) {
			$notificationManager->createTrivialNotification($journalManager->getId(), $notificationType);
			unset($journalManager);
		}
	}
	
	/**
	 * Get whether we're running php 5
	 * @return boolean
	 */
	function php5Installed() {
		return version_compare(PHP_VERSION, '5.0.0', '>=');
	}
	
	/**
	 * Get whether curl is available
	 * @return boolean
	 */
	function curlInstalled() {
		return function_exists('curl_version');
	}
	
	/**
	 * Get whether zip archive support is present
	 * @return boolean
	 */
	function zipInstalled() {
		return class_exists('ZipArchive');
	}
	
	/**
	 * Get resource using CURL
	 * @param $url string
	 * @param $headers array
	 * @return array
	 */
	function _curlGet($url,$headers=array()) {
			
		$curl = curl_init(); 
		
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_URL => $url
		));
		
		$httpResult = curl_exec($curl);
		$httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$httpError = curl_error($curl);
		curl_close ($curl);
				
		return array(
			'status' => $httpStatus,
			'result' => $httpResult,
			'error'  => $httpError
		);
	}
	
	/**
	 * Post a file to a resource using CURL
	 * @param $url string
	 * @param $headers array
	 * @return array
	 */
	function _curlPostFile($url,$filename) {
			
		$curl = curl_init(); 
		
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_HTTPHEADER => array("Content-Length: ".filesize($filename)),
			CURLOPT_INFILE => fopen($filename, "r"),
			CURLOPT_INFILESIZE => filesize($filename),
			CURLOPT_URL => $url
		));
		
		$httpResult = curl_exec($curl);
		$httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$httpError = curl_error($curl);
		curl_close ($curl);
		
		return array(
			'status' => $httpStatus,
			'result' => $httpResult,
			'error'  => $httpError
		);
	}
	
	/**
	 * Put a file to a resource using CURL
	 * @param $url string
	 * @param $filename string
	 * @return array
	 */
	function _curlPutFile($url,$filename) {
			
		$headers = array (
			"Content-Type: ".mime_content_type($filename),
			"Content-Length: ".filesize($filename)
		);
		
		$curl = curl_init(); 
		
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_PUT => true,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_INFILE => fopen($filename, "r"),
			CURLOPT_INFILESIZE => filesize($filename),
			CURLOPT_URL => $url
		));
		
		$httpResult = curl_exec($curl);
		$httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$httpError = curl_error($curl);
		curl_close ($curl);
		
		return array(
			'status' => $httpStatus,
			'result' => $httpResult,
			'error'  => $httpError
		);
	}
	
	/**
	 * Create a new UUID
	 * @return string
	 */
	function newUUID() {
		
		mt_srand((double)microtime()*10000);
		$charid = strtoupper(md5(uniqid(rand(), true)));
		$hyphen = '-';
		$uuid = substr($charid, 0, 8).$hyphen
				.substr($charid, 8, 4).$hyphen
				.substr($charid,12, 4).$hyphen
				.substr($charid,16, 4).$hyphen
				.substr($charid,20,12);
		return $uuid;
	}
	

	
}

?>
