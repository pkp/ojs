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

require_once('lib/bagit.php');

define('PLN_PLUGIN_NETWORKS', serialize(array(
	'PKP' => '127.0.0.1:9999'
)));

define('PLN_PLUGIN_HTTP_STATUS_OK', 200);
define('PLN_PLUGIN_HTTP_STATUS_CREATED', 201);

// used to retrieve the service document
define('PLN_PLUGIN_SD_IRI','/api/sword/2.0/sd-iri');
// used to submit a deposit
define('PLN_PLUGIN_COL_IRI','/api/sword/2.0/col-iri');
// used to edit and query the state of a deposit
define('PLN_PLUGIN_CONT_IRI','/api/sword/2.0/cont-iri');

define('PLN_PLUGIN_DEPOSIT_CHECKSUM_SHA1', 'SHA-1');
define('PLN_PLUGIN_DEPOSIT_CHECKSUM_MD5', 'MD5');

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

define('PLN_PLUGIN_DEPOSIT_OBJECT_ARTICLE', 'plugins.generic.pln.objects.article');
define('PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE', 'plugins.generic.pln.objects.issue');

define('PLN_PLUGIN_DEPOSIT_SUPPORTED_OBJECTS', serialize(array(
	PLN_PLUGIN_DEPOSIT_OBJECT_ARTICLE => get_class(new PublishedArticle()),
	PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE => get_class(new Issue())
)));

define('PLN_PLUGIN_NOTIFICATION_TERMS_UPDATED','plugins.generic.pln.notifications.terms_updated');

class PLNPlugin extends GenericPlugin {

	/**
	 * Constructor
	 */
	function PLNPlugin() {
		parent::GenericPlugin();
	}

	/**
	* @see LazyLoadPlugin::register()
	*/
	function register($category, $path) {
		
		if (parent::register($category, $path)) {
		
			$this->registerDAOs();
			
			$export_plugin =& PluginRegistry::loadPlugin('importexport','native');
			
			// Handler for public (?) access to plugin-related information
			HookRegistry::register('LoadHandler', array(&$this, 'setupPublicHandler'));
			
			// Delete all plug-in data for a journal when the journal is deleted
			HookRegistry::register('JournalDAO::deleteJournalById', array(&$this, 'callbackDeleteJournalById'));

			HookRegistry::register('AcronPlugin::parseCronTab', array(&$this, 'callbackParseCronTab'));
			
			HookRegistry::register('TemplateManager::display',array(&$this, 'callbackTemplateDisplay'));
			
			HookRegistry::register('NotificationManager::getNotificationContents', array(&$this, 'callbackNotificationContents'));
			
			return true;

		}
		
		return false;
	}

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
	*/
	function getDisplayName() {
		return __('plugins.generic.pln');
	}

	/**
	 * @see PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.pln.description');
	}

	/**
	 * @see PKPPlugin::getInstallSchemaFile()
	 */
	function getInstallSchemaFile() {
		return $this->getPluginPath() . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'schema.xml';
	}

	/**
	* @see PKPPlugin::getHandlerPath()
	*/
	function getHandlerPath() {
		return $this->getPluginPath() . DIRECTORY_SEPARATOR . 'pages';
	}

	/**
	* @see PKPPlugin::getTemplatePath()
	*/
	function getTemplatePath() {
		return parent::getTemplatePath() . DIRECTORY_SEPARATOR . 'templates';
	}
	
	/**
	* @see PKPPlugin::getContextSpecificPluginSettingsFile()
	*/
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'settings.xml';
	}
	
	function getStyleSheet() {
		return $this->getPluginPath() . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'pln.css';
	}
	
	/**
	* @see PKPPlugin::getSetting()
	*/
	function getSetting($journal_id,$setting_name) {

		// if there isn't a journal_uuid, make one
		if ($setting_name == 'journal_uuid') {
			$uuid = parent::getSetting($journal_id, $setting_name);
			if ($uuid) return $uuid;
			$this->updateSetting($journal_id, $setting_name, $this->_newUUID());
		}
		
		return parent::getSetting($journal_id,$setting_name);
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
     * @see TemplateManager::display()
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
	 * @see AcronPlugin::parseCronTab()
	 */
	function callbackParseCronTab($hookName, $args) {
		
		$taskFilesPath =& $args[0];
		$taskFilesPath[] = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'scheduledTasks.xml';

		return false;
	}

	/**
	* @see PKPPlugin::manage()
	*/
	function manage($verb, $args, &$message, &$messageParams) {
		
		$returner = parent::manage($verb, $args, $message, $messageParams);
		if (!$returner) return false;
		
		$journal =& Request::getJournal();
		
		$this->import('classes.form.SettingsForm');

		switch($verb) {
			case 'settings':
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
				$settingsForm = new SettingsForm($this, $journal->getId());
				
				if (Request::getUserVar('save')) {

					$settingsForm->readInputData();
					
					if ($settingsForm->validate()) {
						$settingsForm->execute();
						$message = NOTIFICATION_TYPE_SUCCESS;
						$messageParams = array('contents' => __('plugins.generic.pln.settings.saved'));
						return false;
					} else {
						$settingsForm->display();
						return false;
					}
				} else {
					if (Request::getUserVar('refresh')) {
						$this->getServiceDocument($journal->getId());
					}
					$settingsForm->initData();
					$settingsForm->validate();
				}
				$this->setBreadCrumbs('settings');
				$settingsForm->display();
				break;
			default:
				return false;
		}
		return true;
	}
	
	/**
	* @see GenericPlugin::getManagementVerbs()
	*/
	function getManagementVerbs() {
		
		$verbs = parent::getManagementVerbs();
		if ($this->getEnabled()) {
			$verbs[] = array('settings', __('plugins.generic.pln.settings'));
		}
		return $verbs;
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
	 * Hook callback: register pages to display terms of use & data policy
	 * @see PKPPageRouter::route()
	 */
	function setupPublicHandler($hookName, $params) {
		
		$page =& $params[0];
		if ($page == 'pln') {
			$op =& $params[1];
			if ($op) {
				$publicPages = array(
					'status',
					'deposits'
				);
				if (in_array($op, $publicPages)) {
					define('HANDLER_CLASS', 'PLNHandler');
					define('PLN_PLUGIN_NAME', $this->getName());
					AppLocale::requireComponents(LOCALE_COMPONENT_APPLICATION_COMMON);
					$handlerFile =& $params[2];
					$handlerFile = $this->getHandlerPath() . DIRECTORY_SEPARATOR . 'PLNHandler.inc.php';
				}
			}
		}
	}

	function termsAgreed($journal_id) {
		
		$terms = unserialize($this->getSetting($journal_id, 'terms_of_use'));
		$terms_agreed = unserialize($this->getSetting($journal_id, 'terms_of_use_agreement'));
		
		foreach (array_keys($terms) as $term) {
			if (!isset($terms_agreed[$term]) || ($terms_agreed[$term] == FALSE)) return FALSE;
		}
		
		return TRUE;
	}

	/**
	 * Request service document at specified URL
	 * @param $journal_id int The journal id for the service document we wish to fetch
	 */
	function getServiceDocument($journal_id) {
			
		$pln_networks = unserialize(PLN_PLUGIN_NETWORKS);
		
		// retrieve the service document
		$result = $this->_curlGet(
			'http://' . $pln_networks[$this->getSetting($journal_id, 'pln_network')] . PLN_PLUGIN_SD_IRI,
			array('On-Behalf-Of: '.$this->getSetting($journal_id, 'journal_uuid'))
		);
		
		// stop here if we didn't get an OK
		if ($result['status'] != PLN_PLUGIN_HTTP_STATUS_OK) return $result['status'];

		$service_document = new DOMDocument();
		$service_document->preserveWhiteSpace = FALSE;
		$service_document->loadXML($result['result']);
		
		// update the max upload size
		$element = $service_document->getElementsByTagName('maxUploadSize')->item(0);
		$this->updateSetting($journal_id, 'max_upload_size', $element->nodeValue);
		
		// update the checksum type
		$element = $service_document->getElementsByTagName('uploadChecksumType')->item(0);
		$this->updateSetting($journal_id, 'checksum_type', $element->nodeValue);
		
		// update the terms of use
		$term_elements = $service_document->getElementsByTagName('terms_of_use')->item(0)->childNodes;
		$terms = array();
		foreach($term_elements as $term_element) {
			$terms[$term_element->tagName] = $term_element->nodeValue;
		}
		
		$new_terms = serialize($terms);
		$old_terms = $this->getSetting($journal_id,'terms_of_use');
		
		// if the new terms don't match the exiting ones we need to reset agreement
		if ($new_terms != $old_terms) {
			$term_agreements = array();
			foreach($terms as $term_name => $term_text) {
				$term_agreements[$term_name] = FALSE;
			}
		
			$this->updateSetting($journal_id, 'terms_of_use', $new_terms, 'object');
			$this->updateSetting($journal_id, 'terms_of_use_agreement', serialize($term_agreements), 'object');
			$this->createJournalManagerNotification($journal_id,PLN_PLUGIN_NOTIFICATION_TERMS_UPDATED);			
		}
		
		return $result['status'];
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
			case PLN_PLUGIN_NOTIFICATION_TERMS_UPDATED:
				$message = __(PLN_PLUGIN_NOTIFICATION_TERMS_UPDATED);
				break;
		}
	}

	/**
	 * Create notification for all journal managers
	 * @param $journal_id int
	 * @param $notificationType int
	 */
	function createJournalManagerNotification($journal_id, $notificationType) {
		$role_dao =& DAORegistry::getDAO('RoleDAO');
		$journal_managers = $role_dao->getUsersByRoleId(ROLE_ID_JOURNAL_MANAGER,$journal_id);
		import('classes.notification.NotificationManager');
		$notificationManager = new NotificationManager();
		foreach ($journal_managers as $journal_manager) {
			$notificationManager->createTrivialNotification($journal_manager->getId(), $notificationType);
		}
	}

	/**
	 * Transfer a deposit document to the appropriate collection url
	 * @param $deposit Deposit The deposit to transfer
	 */
	function postAtomDocument($deposit,$atom_file) {
		
		$pln_networks = unserialize(PLN_PLUGIN_NETWORKS);
		$url = 'http://' . $pln_networks[$this->getSetting($deposit->getJournalID(), 'pln_network')] . PLN_PLUGIN_COL_IRI;
		
		$result = $this->_curlPostFile(
			$url . '/' . $this->getSetting($deposit->getJournalID(), 'journal_uuid'),
			$atom_file
		);
		
		return $result['status'];
	}

	function _curlGet($url,$headers=array()) {
			
		$curl = curl_init(); 
		
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_URL => $url
		));
		
		$http_result = curl_exec($curl);
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$http_error = curl_error($curl);
		curl_close ($curl);
		
		return array(
			'status' => $http_status,
			'result' => $http_result,
			'error'  => $http_error
		);
	}
	
	function _curlPostFile($url,$filename) {
			
		$curl = curl_init(); 
		
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_POST => TRUE,
			CURLOPT_HTTPHEADER => array("Content-Length: ".filesize($filename)),
			CURLOPT_INFILE => fopen($filename, "r"),
			CURLOPT_INFILESIZE => filesize($filename),
			CURLOPT_URL => $url
		));
		
		$http_result = curl_exec($curl);
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$http_error = curl_error($curl);
		curl_close ($curl);
		
		return array(
			'status' => $http_status,
			'result' => $http_result,
			'error'  => $http_error
		);
	}
	
	function _curlPutFile($url,$filename) {
			
		$headers = array (
			"Content-Type: ".mime_content_type($filename),
			"Content-Length: ".filesize($filename)
		);
		
		$curl = curl_init(); 
		
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_PUT => TRUE,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_INFILE => fopen($filename, "r"),
			CURLOPT_INFILESIZE => filesize($filename),
			CURLOPT_URL => $url
		));
		
		$http_result = curl_exec($curl);
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$http_error = curl_error($curl);
		curl_close ($curl);
		
		return array(
			'status' => $http_status,
			'result' => $http_result,
			'error'  => $http_error
		);
	}
	
	/**
	 * Create a new UUID
	 */
	function _newUUID() {
		
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
