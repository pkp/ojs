<?php

/**
 * @file plugins/generic/lucene/SolrWebService.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SolrWebService
 * @ingroup plugins_generic_lucene
 *
 * @brief Implements the communication protocol with the solr search server.
 *
 * This class relies on the PHP curl extension. Please activate the
 * extension before trying to access a solr server through this class.
 */


define('SOLR_STATUS_ONLINE', 0x01);
define('SOLR_STATUS_OFFLINE', 0x02);

// FIXME: Move to plug-in settings.
define('SOLR_ADMIN_USER', 'admin');
define('SOLR_ADMIN_PASSWORD', 'ojsojs');
define('SOLR_SEARCH_HANDLER', 'http://localhost:8983/solr/ojs/search');

import('lib.pkp.classes.webservice.WebServiceRequest');
import('lib.pkp.classes.webservice.XmlWebService');

class SolrWebService extends XmlWebService {

	/** @var string The solr search handler name we place our searches on. */
	var $_solrSearchHandler;

	/** @var string The solr core we get our data from. */
	var $_solrCore;

	/** @var string The base URL of the solr server without core and search handler. */
	var $_solrServer;


	//
 	// Constructor
 	//
	function SolrWebService() {
		parent::XmlWebService();

		// Configure the web service.
		$this->setReturnType(XSL_TRANSFORMER_DOCTYPE_DOM);
		$this->setAuthUsername(SOLR_ADMIN_USER);
		$this->setAuthPassword(SOLR_ADMIN_PASSWORD);

		// Parse the search handler.
		$searchHandler = SOLR_SEARCH_HANDLER; // FIXME: Get from plug-in settings.

		// Remove trailing slashes.
		$searchHandler = rtrim($searchHandler, '/');

		// Parse the search handler URL.
		$searchHandlerParts = explode('/', $searchHandler);
		$this->_solrSearchHandler = array_pop($searchHandlerParts);
		$this->_solrCore = array_pop($searchHandlerParts);
		$this->_solrServer = implode('/', $searchHandlerParts) . '/';
	}


	//
	// Public API
	//
	/**
	 * Execute a search against the Solr search server.
	 *
	 * @param $search FIXME: not yet specified.
	 *
	 * @return array An array of search results. The keys are
	 *  article IDs and the values the corresponding ranking.
	 */
	function retrieveResults($search) {
		// FIXME: Not yet implemented.
	}

	/**
	 * (Re-)indexes the given article in Solr.
	 *
	 * In Solr we cannot partially (re-)index an article. We always
	 * have to refresh the whole document if parts of it change.
	 *
	 * @param $articleId integer The article to be (re-)indexed.
	 */
	function indexArticle($articleId) {
		// FIXME: Not yet implemented.
	}

	/**
	 * Deletes the given article from the Solr index.
	 *
	 * @param $articleId integer The article to be deleted.
	 */
	function deleteArticleFromIndex($articleId) {
		// FIXME: Not yet implemented.
	}

	/**
	 * Checks the solr server status.
	 *
	 * @return integer One of the SOLR_STATUS_* constants.
	 */
	function getServerStatus() {
		// Make status request.
		$url = $this->_getAdminUrl() . 'cores';
		$params = array(
			'action' => 'STATUS',
			'core' => $this->_solrCore
		);
		$webServiceRequest = new WebServiceRequest($url, $params);
		$response = $this->call($webServiceRequest);

		// Did we get a response at all?
		if (!$response) {
			return array(
				'status' => SOLR_STATUS_OFFLINE,
				'message' => 'Solr server not reachable. Is the solr server running? Does the configured search handler point to the right URL?' // FIXME: Translate.
			);
		}

		// Did we get a 200OK response?
		$status = $this->getLastResponseStatus();
		if ($status !== WEBSERVICE_RESPONSE_OK) {
			$errorMessage = $status. ' - ' . $response->saveXML();
			return array(
				'status' => SOLR_STATUS_OFFLINE,
				'message' => $errorMessage
			);
		}

		// Is the core online?
		assert(is_a($response, 'DOMDocument'));
		$xPath = new DOMXPath($response);
		$nodeList = $xPath->query('//response/lst[@name="status"]/lst[@name="ojs"]/lst[@name="index"]/int[@name="numDocs"]');

		// Check whether the core is active.
		if ($nodeList->length != 1) {
			return array(
				'status' => SOLR_STATUS_OFFLINE,
				'message' => 'The requested core "' . $this->_solrCore . '" was not found on the solr server. Is it online?' // FIXME: Translate.
			);
		}

		$result = array(
			'status' => SOLR_STATUS_ONLINE,
			'message' => 'Index with ' . $nodeList->item(0)->textContent . ' documents online.'
		);

		return $result;
	}

	//
	// Private helper methods
	//
	/**
	 * Identifies the solr admin endpoint from the
	 * search handler URL.
	 *
	 * @return string
	 */
	function _getAdminUrl() {
		$adminUrl = $this->_solrServer . 'admin/';
		return $adminUrl;
	}
}

?>
